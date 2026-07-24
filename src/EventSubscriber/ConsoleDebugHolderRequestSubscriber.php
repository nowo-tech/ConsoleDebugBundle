<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\EventSubscriber;

use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugHolder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Re-binds the global cdbg() bridge on every request.
 *
 * FrankenPHP worker mode resets $_SERVER between iterations; Bundle::boot()
 * alone would only run once per worker process.
 */
final class ConsoleDebugHolderRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConsoleDebug $consoleDebug,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        ConsoleDebugHolder::set($this->consoleDebug);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
        ];
    }
}
