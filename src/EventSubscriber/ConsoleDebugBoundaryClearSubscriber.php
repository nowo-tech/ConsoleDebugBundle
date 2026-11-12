<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\EventSubscriber;

use Nowo\ConsoleDebugBundle\Worker\UnitOfWorkFrames;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

use const PHP_INT_MAX;

/**
 * Empties the debug registry at the end of every unit of work that is not an HTML response.
 *
 * {@see ConsoleDebugHolderRequestSubscriber} clears it at the start of each main request and
 * {@see ConsoleDebugResponseSubscriber} after each main response with entries. Messenger consumers and console
 * commands have neither, so cdbg() entries recorded there (custom gate, impersonated token) were only dropped by
 * kernel.reset, and they piled up where the services resetter does not run (a host that removes or bypasses
 * services_resetter, `messenger:consume --no-reset`, long-running console processes). This subscriber clears the
 * registry at:
 *
 * - kernel.terminate (entries recorded after the response was sent);
 * - Messenger WorkerMessageReceivedEvent, WorkerMessageHandledEvent and WorkerMessageFailedEvent;
 * - console.terminate, only when the OUTERMOST command ends and no Messenger message is in flight: a command run
 *   in-process from another command or from a message handler (`Application::doRun()`) keeps the outer unit's
 *   entries. The in-flight flag is set when a message is received and unset when it is handled, failed or the worker
 *   stops (same pattern as BeaconBundle 1.24.0).
 *
 * Which unit is outermost is tracked by {@see UnitOfWorkFrames} (1.2.5, audits NM / NL2-v), fed at priority
 * PHP_INT_MAX so another listener that throws or stops propagation cannot unbalance it: one frame per console run
 * (Command + input) that saves and restores the outer Messenger state, a frame left failing by a throwing console.error
 * listener healed at the next message, command or worker stop. The clears keep their priorities.
 *
 * The Messenger and console events are subscribed by name, so symfony/messenger and symfony/console stay optional.
 * The registry is injected as an iterator of IGNORE_ON_UNINITIALIZED_REFERENCE references: a boundary never builds it.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class ConsoleDebugBoundaryClearSubscriber implements EventSubscriberInterface, ResetInterface
{
    /** Messenger event names (class names), subscribed without requiring symfony/messenger. */
    public const MESSENGER_WORKER_MESSAGE_RECEIVED = 'Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent';
    public const MESSENGER_WORKER_MESSAGE_HANDLED  = 'Symfony\Component\Messenger\Event\WorkerMessageHandledEvent';
    public const MESSENGER_WORKER_MESSAGE_FAILED   = 'Symfony\Component\Messenger\Event\WorkerMessageFailedEvent';

    /** Messenger skip event (only dispatched by `messenger:failed:retry`): the message is no longer in flight. */
    public const MESSENGER_WORKER_MESSAGE_SKIPPED = 'Symfony\Component\Messenger\Event\WorkerMessageSkipEvent';

    /** Messenger event name of a stopped worker (a listener may skip a received message: no handled / failed follows). */
    public const MESSENGER_WORKER_STOPPED = 'Symfony\Component\Messenger\Event\WorkerStoppedEvent';

    /** Console event names (ConsoleEvents::COMMAND / ERROR / TERMINATE), subscribed without requiring symfony/console. */
    public const CONSOLE_COMMAND   = 'console.command';
    public const CONSOLE_ERROR     = 'console.error';
    public const CONSOLE_TERMINATE = 'console.terminate';

    /** Units of work running in this process (deliberately not tagged kernel.reset; the service is not autoconfigured). */
    private readonly UnitOfWorkFrames $frames;

    /**
     * @param iterable<ResetInterface> $registries Already-built debug registries
     */
    public function __construct(
        private readonly iterable $registries,
    ) {
        $this->frames = new UnitOfWorkFrames();
    }

    /**
     * Subscribes to kernel.terminate, the Messenger worker events and the console events.
     *
     * @return array<string, array{0: string, 1: int}|list<array{0: string, 1: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE                 => ['clearRegistry', -4096],
            self::MESSENGER_WORKER_MESSAGE_RECEIVED => [['onMessageReceivedStarted', PHP_INT_MAX], ['onMessageReceived', 4096]],
            self::MESSENGER_WORKER_MESSAGE_HANDLED  => [['onMessageFinishedStarted', PHP_INT_MAX], ['onMessageFinished', -4096]],
            self::MESSENGER_WORKER_MESSAGE_FAILED   => [['onMessageFinishedStarted', PHP_INT_MAX], ['onMessageFinished', -4096]],
            self::MESSENGER_WORKER_MESSAGE_SKIPPED  => ['onMessageFinishedStarted', PHP_INT_MAX],
            self::MESSENGER_WORKER_STOPPED          => ['onWorkerStopped', PHP_INT_MAX],
            // Bookkeeping first (PHP_INT_MAX), the clear late: a listener in between cannot skip the bookkeeping.
            self::CONSOLE_COMMAND   => ['onConsoleCommand', PHP_INT_MAX],
            self::CONSOLE_ERROR     => ['onConsoleError', PHP_INT_MAX],
            self::CONSOLE_TERMINATE => [['onConsoleTerminateStarted', PHP_INT_MAX], ['onConsoleTerminate', -4096]],
        ];
    }

    /** A Messenger message is received (bookkeeping). */
    public function onMessageReceivedStarted(): void
    {
        $this->frames->messageReceived();
    }

    /** Start of a Messenger message: drops what the worker recorded before it. */
    public function onMessageReceived(): void
    {
        $this->clearRegistry();
    }

    /** A Messenger message is handled, failed or skipped (bookkeeping). */
    public function onMessageFinishedStarted(): void
    {
        $this->frames->messageFinished();
    }

    /** End of a Messenger message (handled or failed). */
    public function onMessageFinished(): void
    {
        $this->clearRegistry();
    }

    /** The worker stopped (possibly after a skipped message): no message is in flight any more. */
    public function onWorkerStopped(): void
    {
        $this->frames->workerStopped();
    }

    /** A console run starts (bookkeeping). */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $this->frames->commandStarted($event);
    }

    /** A console run failed (bookkeeping: its frame is marked failing until its own terminate). */
    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $this->frames->commandFailed($event);
    }

    /** A console run ends (bookkeeping: its frame is removed, the outermost verdict kept for the late clear). */
    public function onConsoleTerminateStarted(ConsoleTerminateEvent $event): void
    {
        $this->frames->commandTerminated($event);
    }

    /** End of a command: clears only when the outermost command ends outside a Messenger message. */
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if ($this->frames->isOutermost($event)) {
            $this->clearRegistry();
        }
    }

    /** Drops every queued entry of the already-built registries. */
    public function clearRegistry(): void
    {
        foreach ($this->registries as $registry) {
            $registry->reset();
        }
    }

    /** Forgets the unit-of-work bookkeeping. Not tagged kernel.reset and not relied on in worker mode. */
    public function reset(): void
    {
        $this->frames->reset();
    }
}
