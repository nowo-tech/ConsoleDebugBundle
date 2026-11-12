<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\EventSubscriber;

use Nowo\ConsoleDebugBundle\ConsoleDebugEntry;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\EventSubscriber\ConsoleDebugBoundaryClearSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
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
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageReceivedEvent' => ['onMessageReceived', 4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageHandledEvent'  => ['onMessageFinished', -4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerMessageFailedEvent'   => ['onMessageFinished', -4096],
                'Symfony\\Component\\Messenger\\Event\\WorkerStoppedEvent'         => ['onWorkerStopped', -4096],
                'console.command'                                                  => ['onConsoleCommand', PHP_INT_MAX],
                'console.terminate'                                                => [['onConsoleTerminateStart', PHP_INT_MAX], ['onConsoleTerminate', -4096]],
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

    public function testUnknownTerminateNeverClearsAndResetForgetsTheStack(): void
    {
        $registry   = new ConsoleDebugRegistry();
        $subscriber = new ConsoleDebugBoundaryClearSubscriber([$registry]);
        $outer      = new Command('app:outer');
        $input      = new ArrayInput([]);
        $output     = new NullOutput();

        $subscriber->onConsoleCommand(new ConsoleCommandEvent($outer, $input, $output));
        $registry->add(new ConsoleDebugEntry('src/Handler.php', 12, 'outer', [], 1.0));

        // A terminate of a command that never started (its console.command was skipped) changes nothing.
        $unknown = new ConsoleTerminateEvent(new Command('app:unknown'), $input, $output, 0);
        $subscriber->onConsoleTerminateStart($unknown);
        $subscriber->onConsoleTerminate($unknown);
        self::assertFalse($registry->isEmpty());

        // A console.command event without a command is ignored.
        $subscriber->onConsoleCommand(new ConsoleCommandEvent(null, $input, $output));

        $subscriber->reset();
        $terminate = new ConsoleTerminateEvent($outer, $input, $output, 0);
        $subscriber->onConsoleTerminateStart($terminate);
        $subscriber->onConsoleTerminate($terminate);
        self::assertFalse($registry->isEmpty(), 'After reset() the outer command is not on the stack any more.');
    }
}
