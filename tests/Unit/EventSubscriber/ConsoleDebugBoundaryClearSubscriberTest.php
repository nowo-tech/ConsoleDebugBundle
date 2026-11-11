<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\EventSubscriber;

use Nowo\ConsoleDebugBundle\ConsoleDebugEntry;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\EventSubscriber\ConsoleDebugBoundaryClearSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

final class ConsoleDebugBoundaryClearSubscriberTest extends TestCase
{
    public function testSubscribesToTerminateMessengerAndConsoleBoundaries(): void
    {
        self::assertSame(
            [
                KernelEvents::TERMINATE                                            => ['clearRegistry', -4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageReceivedEvent' => ['clearRegistry', 4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageHandledEvent'  => ['clearRegistry', -4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageFailedEvent'   => ['clearRegistry', -4096],
                'console.terminate'                                                => ['clearRegistry', -4096],
            ],
            ConsoleDebugBoundaryClearSubscriber::getSubscribedEvents(),
        );
    }

    public function testClearRegistryEmptiesEveryGivenRegistryDirectly(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('src/Handler.php', 12, 'user A', [], 1.0));

        (new ConsoleDebugBoundaryClearSubscriber([$registry]))->clearRegistry();

        self::assertTrue($registry->isEmpty());
    }

    public function testNoRegistryBuiltMeansNothingToClear(): void
    {
        (new ConsoleDebugBoundaryClearSubscriber([]))->clearRegistry();

        $this->addToAssertionCount(1);
    }
}
