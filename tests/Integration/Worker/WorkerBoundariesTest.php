<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Integration\Worker;

use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\EventListener\ResetServicesListener;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Worker;

use function count;
use function is_array;
use function sprintf;

/**
 * Kept-alive kernel with the services resetter replaced by a no-op (a host that removes or bypasses
 * services_resetter), plus `messenger:consume --no-reset` and long-running console processes: one booted kernel,
 * the same ConsoleDebugRegistry, many units of work for users A and B in alternation, with an always-open custom gate.
 *
 * User A records a private cdbg() entry in every unit; user B records nothing and must never see A's entries.
 *
 * N = 500 per path: HTTP through a KernelBrowser with disableReboot() (A alternates JSON, where entries are never
 * injected, and HTML), Messenger through a real Worker without ResetServicesListener (every 7th handler fails), and
 * console Application runs. Memory margin: 1 MiB after a 50-unit warm-up.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class WorkerBoundariesTest extends WebTestCase
{
    private const ITERATIONS    = 500;
    private const WARMUP        = 50;
    private const MEMORY_MARGIN = 1024 * 1024;

    /** @var list<string> */
    private array $leaks = [];

    private int $memoryAtWarmup = 0;

    private int $memoryAtEnd = 0;

    protected static function getKernelClass(): string
    {
        return WorkerTestKernel::class;
    }

    public function testManyRequestsForTwoUsersNeverShowAnotherUsersEntries(): void
    {
        $client = self::createClient(['debug' => false]);
        $client->disableReboot();
        $registry = $this->registry();

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $this->memoryMark($i);
            $user   = $i % 2 === 0 ? 'A' : 'B';
            $format = $user === 'A' && $i % 4 === 0 ? 'json' : 'html';

            $client->request('GET', sprintf('/page/%s.%s', $user, $format));
            $client->getHistory()->clear();
            $content = (string) $client->getResponse()->getContent();

            self::assertSame(200, $client->getResponse()->getStatusCode());
            if ($user === 'B' && str_contains($content, 'private-entry-of-user-A')) {
                $this->leaks[] = sprintf('request %d (user B) shows user A entries', $i);
            }
            if ($user === 'A' && $format === 'html' && substr_count($content, 'private-entry-of-user-A') !== 1) {
                $this->leaks[] = sprintf('request %d (user A) shows %d entries instead of its own one', $i, substr_count($content, 'private-entry-of-user-A'));
            }
            if (!$registry->isEmpty()) {
                $this->leaks[] = sprintf('request %d (user %s) left %d entries after kernel.terminate', $i, $user, count($registry->all()));
            }
        }

        $resetter = self::getContainer()->get('services_resetter');
        self::assertInstanceOf(NoopServicesResetter::class, $resetter, 'The real services resetter must not run.');
        $this->assertNoLeakAndFlatMemory('HTTP requests');
    }

    public function testManyMessengerMessagesForTwoUsersStartWithAnEmptyRegistry(): void
    {
        self::bootKernel(['debug' => false]);
        $registry     = $this->registry();
        $consoleDebug = $this->consoleDebug();
        $dispatcher   = $this->dispatcher();

        foreach ($dispatcher->getListeners(WorkerRunningEvent::class) as $listener) {
            self::assertFalse(
                is_array($listener) && $listener[0] instanceof ResetServicesListener,
                'The worker must run without ResetServicesListener (messenger:consume --no-reset).',
            );
        }

        $receiver = new TenantDebugReceiver(self::ITERATIONS);
        $bus      = new MessageBus([new HandleMessageMiddleware(new HandlersLocator([
            TenantDebugMessage::class => [function (TenantDebugMessage $message) use ($registry, $consoleDebug): void {
                $this->unitOfWork($registry, $consoleDebug, $message->user, $message->index);
                if ($message->fail) {
                    throw new RuntimeException('Handler failed for user ' . $message->user);
                }
            }],
        ]))]);

        $worker     = new Worker(['async' => $receiver], $bus, $dispatcher);
        $stopOnIdle = static function (WorkerRunningEvent $event): void {
            if ($event->isWorkerIdle()) {
                $event->getWorker()->stop();
            }
        };
        $dispatcher->addListener(WorkerRunningEvent::class, $stopOnIdle);

        try {
            $worker->run(['sleep' => 0]);
        } finally {
            $dispatcher->removeListener(WorkerRunningEvent::class, $stopOnIdle);
        }

        self::assertSame(self::ITERATIONS, $receiver->acknowledged + $receiver->rejected);
        self::assertGreaterThan(0, $receiver->rejected);
        self::assertTrue($registry->isEmpty(), 'Entries survive the last WorkerMessageHandled / Failed.');
        $this->assertNoLeakAndFlatMemory('Messenger messages');
    }

    public function testManyConsoleRunsForTwoUsersStartWithAnEmptyRegistry(): void
    {
        self::bootKernel(['debug' => false]);
        $registry     = $this->registry();
        $consoleDebug = $this->consoleDebug();

        $command = (new Command('app:debug-user'))
            ->addArgument('user', InputArgument::REQUIRED)
            ->addArgument('index', InputArgument::REQUIRED)
            ->setCode(function (InputInterface $input, OutputInterface $output) use ($registry, $consoleDebug): int {
                $this->unitOfWork($registry, $consoleDebug, (string) $input->getArgument('user'), (int) $input->getArgument('index'));

                return Command::SUCCESS;
            });

        $application = new Application('worker-test');
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $application->setDispatcher($this->dispatcher());
        $application->setCommandLoader(new FactoryCommandLoader(['app:debug-user' => static fn (): Command => $command]));

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $exitCode = $application->run(
                new ArrayInput(['command' => 'app:debug-user', 'user' => $i % 2 === 0 ? 'A' : 'B', 'index' => (string) $i]),
                new NullOutput(),
            );
            self::assertSame(Command::SUCCESS, $exitCode);
            self::assertTrue($registry->isEmpty(), sprintf('Entries survive console.terminate of run %d.', $i));
        }

        $this->assertNoLeakAndFlatMemory('console command runs');
    }

    /**
     * MI-C-I3: a command run in-process from another command or from a Messenger handler (Application::doRun())
     * keeps the outer unit's entries; the outermost console.terminate and the message boundaries still clear them.
     */
    public function testNestedCommandsKeepTheOuterUnitsEntries(): void
    {
        self::bootKernel(['debug' => false]);
        $registry     = $this->registry();
        $consoleDebug = $this->consoleDebug();
        $dispatcher   = $this->dispatcher();
        $application  = new Application('worker-test');
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $application->setDispatcher($dispatcher);

        $counts = [];
        $inner  = (new Command('app:inner'))->setCode(static fn (): int => Command::SUCCESS);
        $outer  = (new Command('app:outer'))->setCode(static function (InputInterface $input, OutputInterface $output) use ($application, $registry, $consoleDebug, &$counts): int {
            $consoleDebug->log('outer-entry');
            $application->doRun(new ArrayInput(['command' => 'app:inner']), $output);
            $counts[] = count($registry->all());

            return Command::SUCCESS;
        });
        $application->setCommandLoader(new FactoryCommandLoader([
            'app:outer' => static fn (): Command => $outer,
            'app:inner' => static fn (): Command => $inner,
        ]));

        self::assertSame(Command::SUCCESS, $application->run(new ArrayInput(['command' => 'app:outer']), new NullOutput()));
        self::assertSame([1], $counts, 'A nested command dropped the outer command\'s entries.');
        self::assertTrue($registry->isEmpty(), 'The outermost console.terminate clears the registry.');

        $bus = new MessageBus([new HandleMessageMiddleware(new HandlersLocator([
            TenantDebugMessage::class => [static function () use ($application, $registry, $consoleDebug, &$counts): void {
                $consoleDebug->log('handler-entry');
                $application->doRun(new ArrayInput(['command' => 'app:inner']), new NullOutput());
                $counts[] = count($registry->all());
            }],
        ]))]);
        $worker     = new Worker(['async' => new TenantDebugReceiver(3)], $bus, $dispatcher);
        $stopOnIdle = static function (WorkerRunningEvent $event): void {
            if ($event->isWorkerIdle()) {
                $event->getWorker()->stop();
            }
        };
        $dispatcher->addListener(WorkerRunningEvent::class, $stopOnIdle);

        try {
            $worker->run(['sleep' => 0]);
        } finally {
            $dispatcher->removeListener(WorkerRunningEvent::class, $stopOnIdle);
        }

        self::assertSame([1, 1, 1, 1], $counts, 'A command run from the handler dropped its entries.');
        self::assertTrue($registry->isEmpty(), 'The message boundaries clear the registry.');
    }

    /**
     * One non-HTTP unit of work: the registry must be empty; user A then records a private entry.
     */
    private function unitOfWork(ConsoleDebugRegistry $registry, ConsoleDebug $consoleDebug, string $user, int $index): void
    {
        $this->memoryMark($index);
        if (!$registry->isEmpty()) {
            $this->leaks[] = sprintf('unit %d (user %s) started with %d entries', $index, $user, count($registry->all()));
        }

        if ($user === 'A') {
            $consoleDebug->log('private-entry-of-user-A', ['email' => 'user-a@example.test']);
        }
    }

    private function memoryMark(int $index): void
    {
        if ($index === self::WARMUP) {
            gc_collect_cycles();
            $this->memoryAtWarmup = memory_get_usage();
        }
        if ($index === self::ITERATIONS - 1) {
            gc_collect_cycles();
            $this->memoryAtEnd = memory_get_usage();
        }
    }

    private function assertNoLeakAndFlatMemory(string $path): void
    {
        self::assertSame([], $this->leaks, sprintf('Debug entries leaked between %s.', $path));
        self::assertGreaterThan(0, $this->memoryAtWarmup);
        self::assertLessThan(self::MEMORY_MARGIN, $this->memoryAtEnd - $this->memoryAtWarmup, sprintf('Memory grew across %s.', $path));
    }

    private function registry(): ConsoleDebugRegistry
    {
        $registry = self::getContainer()->get(WorkerTestKernel::REGISTRY);
        self::assertInstanceOf(ConsoleDebugRegistry::class, $registry);

        return $registry;
    }

    private function consoleDebug(): ConsoleDebug
    {
        $consoleDebug = self::getContainer()->get(WorkerTestKernel::CONSOLE_DEBUG);
        self::assertInstanceOf(ConsoleDebug::class, $consoleDebug);

        return $consoleDebug;
    }

    private function dispatcher(): EventDispatcherInterface
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        return $dispatcher;
    }
}
