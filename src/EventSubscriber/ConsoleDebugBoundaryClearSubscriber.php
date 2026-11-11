<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

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
 * - console.terminate.
 *
 * The Messenger and console events are subscribed by name, so symfony/messenger and symfony/console stay optional.
 * The registry is injected as an iterator of IGNORE_ON_UNINITIALIZED_REFERENCE references: a boundary never builds it.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class ConsoleDebugBoundaryClearSubscriber implements EventSubscriberInterface
{
    /** Messenger event names (class names), subscribed without requiring symfony/messenger. */
    public const MESSENGER_WORKER_MESSAGE_RECEIVED = 'Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent';
    public const MESSENGER_WORKER_MESSAGE_HANDLED  = 'Symfony\Component\Messenger\Event\WorkerMessageHandledEvent';
    public const MESSENGER_WORKER_MESSAGE_FAILED   = 'Symfony\Component\Messenger\Event\WorkerMessageFailedEvent';

    /** Console event name (ConsoleEvents::TERMINATE), subscribed without requiring symfony/console. */
    public const CONSOLE_TERMINATE = 'console.terminate';

    /**
     * @param iterable<ResetInterface> $registries Already-built debug registries
     */
    public function __construct(
        private iterable $registries,
    ) {
    }

    /** Subscribes to kernel.terminate, the Messenger worker events and console.terminate. */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE                 => ['clearRegistry', -4096],
            self::MESSENGER_WORKER_MESSAGE_RECEIVED => ['clearRegistry', 4096],
            self::MESSENGER_WORKER_MESSAGE_HANDLED  => ['clearRegistry', -4096],
            self::MESSENGER_WORKER_MESSAGE_FAILED   => ['clearRegistry', -4096],
            self::CONSOLE_TERMINATE                 => ['clearRegistry', -4096],
        ];
    }

    /** Drops every queued entry of the already-built registries. */
    public function clearRegistry(): void
    {
        foreach ($this->registries as $registry) {
            $registry->reset();
        }
    }
}
