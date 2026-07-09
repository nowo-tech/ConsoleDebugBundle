<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\EventSubscriber;

use Nowo\ConsoleDebugBundle\ConsoleDebugEntry;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\EventSubscriber\ConsoleDebugResponseSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ConsoleDebugResponseSubscriberTest extends TestCase
{
    public function testInjectsScriptIntoHtmlResponse(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry(
            file: 'src/Controller/DemoController.php',
            line: 42,
            label: 'payload',
            variables: [['id' => 7]],
            timestamp: 1.0,
        ));

        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'info', '[cdbg]');
        $kernel     = $this->createMock(HttpKernelInterface::class);
        $request    = Request::create('/');
        $response   = new Response('<html><body><h1>Demo</h1></body></html>', 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertStringContainsString('data-nowo-console-debug', (string) $response->getContent());
        self::assertStringContainsString('DemoController.php', (string) $response->getContent());
        self::assertTrue($registry->isEmpty());
    }

    public function testSkipsNonHtmlResponses(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 1, null, ['x'], 1.0));

        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'info', '[cdbg]');
        $kernel     = $this->createMock(HttpKernelInterface::class);
        $request    = Request::create('/');
        $response   = new Response('{"ok":true}', 200, ['Content-Type' => 'application/json']);

        $subscriber->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertSame('{"ok":true}', $response->getContent());
        self::assertCount(1, $registry->all());
    }

    public function testAppendsScriptWhenBodyTagIsMissing(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 9, null, ['value'], 1.0));

        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'log', '[cdbg]');
        $kernel     = $this->createMock(HttpKernelInterface::class);
        $response   = new Response('<html><h1>No body tag</h1></html>', 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertStringContainsString('data-nowo-console-debug', (string) $response->getContent());
        self::assertStringContainsString('console.log', (string) $response->getContent());
    }

    public function testSkipsSubRequestsAndEmptyResponses(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 1, null, [], 1.0));
        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'info', '[cdbg]');
        $kernel     = $this->createMock(HttpKernelInterface::class);

        $empty = new Response('', 200, ['Content-Type' => 'text/html']);
        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $empty));
        self::assertCount(1, $registry->all());

        $sub = new Response('<html><body>x</body></html>', 200, ['Content-Type' => 'text/html']);
        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::SUB_REQUEST, $sub));
        self::assertStringNotContainsString('data-nowo-console-debug', (string) $sub->getContent());
    }

    public function testSkipsWhenScriptAlreadyPresent(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 1, null, ['x'], 1.0));
        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'warn', '[cdbg]');
        $kernel     = $this->createMock(HttpKernelInterface::class);
        $html       = '<html><body><script data-nowo-console-debug></script></body></html>';
        $response   = new Response($html, 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertSame($html, $response->getContent());
    }

    public function testInjectsWhenMarkerAppearsOnlyInPageCopy(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 1, null, ['x'], 1.0));
        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'info', '[cdbg]');
        $kernel     = $this->createMock(HttpKernelInterface::class);
        $response   = new Response(
            '<html><body><p>Docs mention data-nowo-console-debug as text.</p></body></html>',
            200,
            ['Content-Type' => 'text/html'],
        );

        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertStringContainsString('<script data-nowo-console-debug>', (string) $response->getContent());
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertArrayHasKey('kernel.response', ConsoleDebugResponseSubscriber::getSubscribedEvents());
        self::assertSame(-4096, ConsoleDebugResponseSubscriber::getSubscribedEvents()['kernel.response'][1]);
    }

    public function testUsesInvalidConsoleMethodFallback(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 1, null, ['only'], 1.0));
        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'trace', '[cdbg]');
        $kernel     = $this->createMock(HttpKernelInterface::class);
        $response   = new Response('<html><body></body></html>', 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertStringContainsString('console.log', (string) $response->getContent());
    }

    public function testEmbedsPayloadWithJsonParseForSafeEscaping(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry(
            file: 'src/Demo/DemoPriority.php',
            line: 12,
            label: 'Backed enum',
            variables: [['enum' => 'App/Demo/DemoPriority', 'value' => 'high']],
            timestamp: 1.0,
        ));

        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'log', '[cdbg]');
        $kernel     = $this->createMock(HttpKernelInterface::class);
        $response   = new Response('<html><body></body></html>', 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));

        $content = (string) $response->getContent();
        self::assertStringContainsString('type="application/json"', $content);
        self::assertStringContainsString('data-nowo-console-debug-data', $content);
        self::assertStringContainsString('App/Demo/DemoPriority', $content);
        self::assertStringContainsString('console.group(', $content);
        self::assertStringNotContainsString('console.groupCollapsed(', $content);
    }
}
