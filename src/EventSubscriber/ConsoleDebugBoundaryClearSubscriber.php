<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

use function max;

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
 *   entries. The depth is counted on console.command / console.terminate; the in-flight flag is set when a message is
 *   received and unset when it is handled, failed or the worker stops (same pattern as BeaconBundle 1.24.0).
 *
 * The Messenger and console events are subscribed by name, so symfony/messenger and symfony/console stay optional.
 * The registry is injected as an iterator of IGNORE_ON_UNINITIALIZED_REFERENCE references: a boundary never builds it.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class ConsoleDebugBoundaryClearSubscriber implements EventSubscriberInterface
{
    /** Messenger event names (class names), subscribed without requiring symfony/messenger. */
    public const MESSENGER_WORKER_MESSAGE_RECEIVED = 'Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent';
    public const MESSENGER_WORKER_MESSAGE_HANDLED  = 'Symfony\Component\Messenger\Event\WorkerMessageHandledEvent';
    public const MESSENGER_WORKER_MESSAGE_FAILED   = 'Symfony\Component\Messenger\Event\WorkerMessageFailedEvent';

    /** Messenger event name of a stopped worker (a listener may skip a received message: no handled / failed follows). */
    public const MESSENGER_WORKER_STOPPED = 'Symfony\Component\Messenger\Event\WorkerStoppedEvent';

    /** Console event names (ConsoleEvents::COMMAND / TERMINATE), subscribed without requiring symfony/console. */
    public const CONSOLE_COMMAND   = 'console.command';
    public const CONSOLE_TERMINATE = 'console.terminate';

    /**
     * Console commands currently running in this process (nested in-process runs count). Process-lifetime counter,
     * bounded by the nesting depth; intentionally not reset by kernel.reset.
     */
    private int $consoleDepth = 0;

    /** A Messenger message is being handled (between received and handled / failed / worker stopped). */
    private bool $messageInFlight = false;

    /**
     * @param iterable<ResetInterface> $registries Already-built debug registries
     */
    public function __construct(
        private readonly iterable $registries,
    ) {
    }

    /** Subscribes to kernel.terminate, the Messenger worker events and console.terminate. */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE                 => ['clearRegistry', -4096],
            self::MESSENGER_WORKER_MESSAGE_RECEIVED => ['onMessageReceived', 4096],
            self::MESSENGER_WORKER_MESSAGE_HANDLED  => ['onMessageFinished', -4096],
            self::MESSENGER_WORKER_MESSAGE_FAILED   => ['onMessageFinished', -4096],
            self::MESSENGER_WORKER_STOPPED          => ['onWorkerStopped', -4096],
            self::CONSOLE_COMMAND                   => ['onConsoleCommand', 4096],
            self::CONSOLE_TERMINATE                 => ['onConsoleTerminate', -4096],
        ];
    }

    /** Start of a Messenger message: drops what the worker recorded before it. */
    public function onMessageReceived(): void
    {
        // @igor-ignore - Unit-of-work flag, set and unset at the Messenger boundaries.
        $this->messageInFlight = true;
        $this->clearRegistry();
    }

    /** End of a Messenger message (handled or failed). */
    public function onMessageFinished(): void
    {
        // @igor-ignore - Unit-of-work flag, set and unset at the Messenger boundaries.
        $this->messageInFlight = false;
        $this->clearRegistry();
    }

    /** The worker stopped (possibly after a skipped message): no message is in flight any more. */
    public function onWorkerStopped(): void
    {
        // @igor-ignore - Unit-of-work flag, set and unset at the Messenger boundaries.
        $this->messageInFlight = false;
    }

    /** Start of a command: only counts the nesting depth. */
    public function onConsoleCommand(): void
    {
        // @igor-ignore - Nesting counter of the running commands (no request data).
        ++$this->consoleDepth;
    }

    /** End of a command: clears only when the outermost command ends outside a Messenger message. */
    public function onConsoleTerminate(): void
    {
        // @igor-ignore - Nesting counter of the running commands (no request data).
        $this->consoleDepth = max(0, $this->consoleDepth - 1);
        if ($this->consoleDepth === 0 && !$this->messageInFlight) {
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
}
