<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\EventSubscriber;

use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugHolder;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Re-binds the global cdbg() bridge and starts an empty debug registry on every main request.
 *
 * FrankenPHP worker mode resets $_SERVER between iterations; Bundle::boot()
 * alone would only run once per worker process. Clearing the registry here keeps
 * entries of one request (e.g. a JSON call) out of the next user's HTML page even
 * when the services resetter does not run between requests.
 */
final class ConsoleDebugHolderRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConsoleDebug $consoleDebug,
        private readonly ?ConsoleDebugRegistry $registry = null,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->registry?->clear();
        ConsoleDebugHolder::set($this->consoleDebug);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
        ];
    }
}
