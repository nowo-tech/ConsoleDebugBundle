<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\EventSubscriber;

use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugHolder;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;
use Nowo\ConsoleDebugBundle\EventSubscriber\ConsoleDebugHolderRequestSubscriber;
use Nowo\ConsoleDebugBundle\EventSubscriber\ConsoleDebugResponseSubscriber;
use Nowo\ConsoleDebugBundle\Serializer\DebugValueNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Long-running worker (FrankenPHP) without kernel.reset: the same registry and subscribers
 * serve consecutive requests of different users.
 */
final class ConsoleDebugWorkerModeTest extends TestCase
{
    private ConsoleDebugRegistry $registry;

    private ConsoleDebug $consoleDebug;

    private EventDispatcher $dispatcher;

    private bool $gateEnabled = false;

    protected function setUp(): void
    {
        $gateEnabled = &$this->gateEnabled;
        $gate        = new class($gateEnabled) implements ConsoleDebugGateInterface {
            public function __construct(private bool &$enabled)
            {
            }

            public function isEnabled(): bool
            {
                return $this->enabled;
            }
        };
        $this->registry     = new ConsoleDebugRegistry();
        $this->consoleDebug = new ConsoleDebug($gate, $this->registry, new DebugValueNormalizer(), null, false);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber(new ConsoleDebugHolderRequestSubscriber($this->consoleDebug, $this->registry));
        $this->dispatcher->addSubscriber(new ConsoleDebugResponseSubscriber($this->registry, 'log', '[cdbg]'));
    }

    protected function tearDown(): void
    {
        ConsoleDebugHolder::reset();
    }

    public function testEntriesOfJsonRequestAreNotInjectedIntoNextUsersHtmlPage(): void
    {
        // Request 1: authorized developer calls cdbg() on a JSON endpoint.
        $this->gateEnabled = true;
        $this->startRequest();
        $this->consoleDebug->log('secret', ['email' => 'dev@example.com']);
        $json = $this->finishRequest(new Response('{"ok":true}', 200, ['Content-Type' => 'application/json']));
        self::assertSame('{"ok":true}', $json->getContent());

        // Request 2: anonymous visitor gets an HTML page from the same worker.
        $this->gateEnabled = false;
        $this->startRequest();
        $html = $this->finishRequest(new Response('<html><body>Home</body></html>', 200, ['Content-Type' => 'text/html']));

        self::assertStringNotContainsString('data-nowo-console-debug', (string) $html->getContent());
        self::assertStringNotContainsString('dev@example.com', (string) $html->getContent());
        self::assertTrue($this->registry->isEmpty());
    }

    public function testEntriesRecordedAfterResponseAreClearedAtNextMainRequest(): void
    {
        $this->gateEnabled = true;
        $this->startRequest();
        $this->finishRequest(new Response('<html><body>A</body></html>', 200, ['Content-Type' => 'text/html']));
        // e.g. cdbg() from a kernel.terminate listener or a streamed response callback
        $this->consoleDebug->log('late', 'user A data');
        self::assertCount(1, $this->registry->all());

        $this->startRequest();
        self::assertTrue($this->registry->isEmpty());

        $this->consoleDebug->log('own', 'user B data');
        $html = $this->finishRequest(new Response('<html><body>B</body></html>', 200, ['Content-Type' => 'text/html']));

        self::assertStringContainsString('user B data', (string) $html->getContent());
        self::assertStringNotContainsString('user A data', (string) $html->getContent());
    }

    public function testSubRequestDoesNotClearMainRequestEntries(): void
    {
        $this->gateEnabled = true;
        $this->startRequest();
        $this->consoleDebug->log('main', 'value');

        $this->dispatcher->dispatch(
            new RequestEvent($this->createStub(HttpKernelInterface::class), Request::create('/_fragment'), HttpKernelInterface::SUB_REQUEST),
            KernelEvents::REQUEST,
        );

        self::assertCount(1, $this->registry->all());
    }

    private function startRequest(): void
    {
        $this->dispatcher->dispatch(
            new RequestEvent($this->createStub(HttpKernelInterface::class), Request::create('/'), HttpKernelInterface::MAIN_REQUEST),
            KernelEvents::REQUEST,
        );
    }

    private function finishRequest(Response $response): Response
    {
        $this->dispatcher->dispatch(
            new ResponseEvent($this->createStub(HttpKernelInterface::class), Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response),
            KernelEvents::RESPONSE,
        );

        return $response;
    }
}
