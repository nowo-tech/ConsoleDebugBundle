<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit;

use LogicException;
use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugHolder;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\EventSubscriber\ConsoleDebugHolderRequestSubscriber;
use Nowo\ConsoleDebugBundle\Gate\EnabledConsoleDebugGate;
use Nowo\ConsoleDebugBundle\Serializer\DebugValueNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ConsoleDebugHolderRequestSubscriberTest extends TestCase
{
    protected function tearDown(): void
    {
        ConsoleDebugHolder::reset();
    }

    public function testSubscribesToEarlyKernelRequest(): void
    {
        $events = ConsoleDebugHolderRequestSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(KernelEvents::REQUEST, $events);
        self::assertSame(['onKernelRequest', 1024], $events[KernelEvents::REQUEST]);
    }

    public function testMainRequestBindsHolder(): void
    {
        $service = new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            new ConsoleDebugRegistry(),
            new DebugValueNormalizer(),
            null,
            false,
        );
        $subscriber = new ConsoleDebugHolderRequestSubscriber($service);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event  = new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        self::assertSame($service, ConsoleDebugHolder::get());
    }

    public function testSubRequestDoesNotBindHolder(): void
    {
        $service = new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            new ConsoleDebugRegistry(),
            new DebugValueNormalizer(),
            null,
            false,
        );
        $subscriber = new ConsoleDebugHolderRequestSubscriber($service);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event  = new RequestEvent($kernel, new Request(), HttpKernelInterface::SUB_REQUEST);

        $subscriber->onKernelRequest($event);

        $this->expectException(LogicException::class);
        ConsoleDebugHolder::get();
    }
}
