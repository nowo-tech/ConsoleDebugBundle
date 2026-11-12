<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\EventSubscriber;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

use function array_slice;
use function count;

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
 * Console bookkeeping (1.2.5, audit NM): the running commands are a stack pushed on console.command and popped on
 * console.terminate, both at PHP_INT_MAX, so another listener that throws or stops propagation cannot skip it. A
 * terminate pops its own command and every command started after it (a nested run whose terminate was skipped); a
 * terminate of a command that is not on the stack changes nothing (never a blind decrement). Whether the command was
 * the outermost unit is decided there; the clear itself still runs at the late priority (-4096).
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

    /** Messenger event name of a stopped worker (a listener may skip a received message: no handled / failed follows). */
    public const MESSENGER_WORKER_STOPPED = 'Symfony\Component\Messenger\Event\WorkerStoppedEvent';

    /** Console event names (ConsoleEvents::COMMAND / TERMINATE), subscribed without requiring symfony/console. */
    public const CONSOLE_COMMAND   = 'console.command';
    public const CONSOLE_TERMINATE = 'console.terminate';

    /**
     * Console commands currently running in this process, outermost first (nested in-process runs count). Bounded by
     * the nesting depth; deliberately not tagged kernel.reset (the service is not autoconfigured).
     *
     * @var list<Command>
     */
    private array $runningCommands = [];

    /** The outermost command whose console.terminate is under way: cleared at the late priority. */
    private ?Command $terminatingOutermost = null;

    /** A Messenger message is being handled (between received and handled / failed / worker stopped). */
    private bool $messageInFlight = false;

    /**
     * @param iterable<ResetInterface> $registries Already-built debug registries
     */
    public function __construct(
        private readonly iterable $registries,
    ) {
    }

    /**
     * Subscribes to kernel.terminate, the Messenger worker events and console.terminate.
     *
     * @return array<string, array{0: string, 1: int}|list<array{0: string, 1: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE                 => ['clearRegistry', -4096],
            self::MESSENGER_WORKER_MESSAGE_RECEIVED => ['onMessageReceived', 4096],
            self::MESSENGER_WORKER_MESSAGE_HANDLED  => ['onMessageFinished', -4096],
            self::MESSENGER_WORKER_MESSAGE_FAILED   => ['onMessageFinished', -4096],
            self::MESSENGER_WORKER_STOPPED          => ['onWorkerStopped', -4096],
            // Bookkeeping first (PHP_INT_MAX), the clear late: a listener in between cannot skip the bookkeeping.
            self::CONSOLE_COMMAND   => ['onConsoleCommand', PHP_INT_MAX],
            self::CONSOLE_TERMINATE => [['onConsoleTerminateStart', PHP_INT_MAX], ['onConsoleTerminate', -4096]],
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

    /** Start of a command: pushed on the running-command stack (nested in-process runs count). */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof Command) {
            return;
        }

        // @igor-ignore - Nesting stack of the running commands (no request data).
        $this->runningCommands[] = $command;
    }

    /**
     * End of a command: pops it and every command started after it, and remembers whether it was the outermost unit
     * (outside a Messenger message). A command that is not on the stack changes nothing.
     */
    public function onConsoleTerminateStart(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        $index   = null;
        for ($i = count($this->runningCommands) - 1; $i >= 0; --$i) {
            if ($this->runningCommands[$i] === $command) {
                $index = $i;

                break;
            }
        }

        if ($index === null) {
            return;
        }

        // @igor-ignore - Nesting stack of the running commands (no request data).
        $this->runningCommands = array_slice($this->runningCommands, 0, $index);
        // @igor-ignore - Outermost command whose terminate is under way (cleared at the late priority).
        $this->terminatingOutermost = $index === 0 && !$this->messageInFlight ? $command : null;
    }

    /** End of a command: clears only when the outermost command ends outside a Messenger message. */
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if (!$this->terminatingOutermost instanceof Command || $this->terminatingOutermost !== $event->getCommand()) {
            return;
        }

        // @igor-ignore - Outermost command whose terminate is under way (cleared at the late priority).
        $this->terminatingOutermost = null;
        $this->clearRegistry();
    }

    /** Forgets the console and Messenger bookkeeping. Not tagged kernel.reset and not relied on in worker mode. */
    public function reset(): void
    {
        $this->runningCommands      = [];
        $this->terminatingOutermost = null;
        $this->messageInFlight      = false;
    }

    /** Drops every queued entry of the already-built registries. */
    public function clearRegistry(): void
    {
        foreach ($this->registries as $registry) {
            $registry->reset();
        }
    }
}
