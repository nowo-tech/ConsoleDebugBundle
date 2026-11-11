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
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageReceivedEvent' => ['onMessageReceived', 4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageHandledEvent'  => ['onMessageFinished', -4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageFailedEvent'   => ['onMessageFinished', -4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerStoppedEvent'         => ['onWorkerStopped', -4096],
                'console.command'                                                  => ['onConsoleCommand', 4096],
                'console.terminate'                                                => ['onConsoleTerminate', -4096],
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
