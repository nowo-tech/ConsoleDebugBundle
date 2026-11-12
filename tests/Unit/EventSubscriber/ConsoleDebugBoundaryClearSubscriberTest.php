<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\EventSubscriber;

use Nowo\ConsoleDebugBundle\ConsoleDebugEntry;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\EventSubscriber\ConsoleDebugBoundaryClearSubscriber;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelEvents;

use const PHP_INT_MAX;

final class ConsoleDebugBoundaryClearSubscriberTest extends TestCase
{
    public function testSubscribesToTerminateMessengerAndConsoleBoundaries(): void
    {
        self::assertSame(
            [
                KernelEvents::TERMINATE                                            => ['clearRegistry', -4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageReceivedEvent' => [['onMessageReceivedStarted', PHP_INT_MAX], ['onMessageReceived', 4096]],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageHandledEvent'  => [['onMessageFinishedStarted', PHP_INT_MAX], ['onMessageFinished', -4096]],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageFailedEvent'   => [['onMessageFinishedStarted', PHP_INT_MAX], ['onMessageFinished', -4096]],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageSkipEvent'     => ['onMessageFinishedStarted', PHP_INT_MAX],
                'Symfony\\Component\\Messenger\\Event\\WorkerStoppedEvent'         => ['onWorkerStopped', PHP_INT_MAX],
                'console.command'                                                  => ['onConsoleCommand', PHP_INT_MAX],
                'console.error'                                                    => ['onConsoleError', PHP_INT_MAX],
                'console.terminate'                                                => [['onConsoleTerminateStarted', PHP_INT_MAX], ['onConsoleTerminate', -4096]],
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

    public function testUnknownTerminateNeverClearsARunningUnitAndResetForgetsTheFrames(): void
    {
        $registry   = new ConsoleDebugRegistry();
        $subscriber = new ConsoleDebugBoundaryClearSubscriber([$registry]);
        $outer      = new ConsoleCommandEvent(new Command('app:outer'), new ArrayInput([]), new NullOutput());

        $subscriber->onConsoleCommand($outer);
        $registry->add(new ConsoleDebugEntry('src/Handler.php', 12, 'outer', [], 1.0));

        // A terminate of a run without a frame (its console.command was skipped) never ends the running outer unit.
        $unknown = new ConsoleTerminateEvent(new Command('app:unknown'), new ArrayInput([]), new NullOutput(), 0);
        $subscriber->onConsoleTerminateStarted($unknown);
        $subscriber->onConsoleTerminate($unknown);
        self::assertFalse($registry->isEmpty());

        // A failing run is marked; its own terminate still ends the outermost unit.
        $subscriber->onConsoleError(new ConsoleErrorEvent($outer->getInput(), $outer->getOutput(), new RuntimeException('failed'), $outer->getCommand()));
        $subscriber->onMessageReceivedStarted();
        $subscriber->onMessageFinishedStarted();
        $subscriber->onWorkerStopped();
        $subscriber->reset();

        // After reset() nothing runs: an unknown terminate is the outermost unit.
        $subscriber->onConsoleTerminateStarted($unknown);
        $subscriber->onConsoleTerminate($unknown);
        self::assertTrue($registry->isEmpty());
    }
}
