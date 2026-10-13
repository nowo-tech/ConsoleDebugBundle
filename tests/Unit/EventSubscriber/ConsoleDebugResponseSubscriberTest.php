<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\EventSubscriber;

use Nowo\ConsoleDebugBundle\ConsoleDebugEntry;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\EventSubscriber\ConsoleDebugResponseSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use const INF;
use const JSON_THROW_ON_ERROR;
use const NAN;

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
        self::assertTrue($registry->isEmpty(), 'Entries of a non-HTML response must not survive to the next request');
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

        $sub = new Response('<html><body>x</body></html>', 200, ['Content-Type' => 'text/html']);
        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::SUB_REQUEST, $sub));
        self::assertStringNotContainsString('data-nowo-console-debug', (string) $sub->getContent());
        self::assertCount(1, $registry->all(), 'Sub-requests keep entries for the main response');

        $empty = new Response('', 200, ['Content-Type' => 'text/html']);
        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $empty));
        self::assertSame('', $empty->getContent());
        self::assertTrue($registry->isEmpty());
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
        self::assertTrue($registry->isEmpty());
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

    public function testInjectsNonUtf8AndNonFiniteValuesWithoutThrowing(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry(
            file: 'src/Demo/Binary.php',
            line: 3,
            label: "bad \xB1 label",
            variables: ["caf\xE9", INF, NAN, -INF, ['nested' => "\xFF"]],
            timestamp: 1.0,
        ));

        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'log', '[cdbg]');
        $kernel     = $this->createMock(HttpKernelInterface::class);
        $response   = new Response('<html><body></body></html>', 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));

        $content = (string) $response->getContent();
        self::assertSame(1, preg_match('/<script type="application\/json" data-nowo-console-debug-data>(.*?)<\/script>/s', $content, $m));
        $decoded = json_decode($m[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame("bad \u{FFFD} label", $decoded[0]['label']);
        self::assertSame(["caf\u{FFFD}", 0, 0, 0, ['nested' => "\u{FFFD}"]], $decoded[0]['variables']);
        self::assertTrue($registry->isEmpty());
    }

    public function testStampsCspNonceFromRequestAttribute(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 1, null, ['x'], 1.0));
        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'log', '[cdbg]');
        $request    = Request::create('/');
        $request->attributes->set('csp_nonce', 'abc123"x');
        $response = new Response('<html><body></body></html>', 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response));

        $content = (string) $response->getContent();
        self::assertStringContainsString('<script type="application/json" data-nowo-console-debug-data nonce="abc123&quot;x">', $content);
        self::assertStringContainsString('<script data-nowo-console-debug nonce="abc123&quot;x">', $content);
    }

    public function testStampsCspNonceFromConfiguredAttributeName(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 1, null, ['x'], 1.0));
        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'log', '[cdbg]', 'app_nonce');
        $request    = Request::create('/');
        $request->attributes->set('csp_nonce', 'ignored');
        $request->attributes->set('app_nonce', 'n0nce');
        $response = new Response('<html><body></body></html>', 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response));

        $content = (string) $response->getContent();
        self::assertStringContainsString('<script data-nowo-console-debug nonce="n0nce">', $content);
        self::assertStringNotContainsString('ignored', $content);
    }

    /**
     * @return iterable<string, array{?string, mixed}>
     */
    public static function noNonceProvider(): iterable
    {
        yield 'attribute missing' => ['csp_nonce', null];
        yield 'empty nonce' => ['csp_nonce', ''];
        yield 'non-string nonce' => ['csp_nonce', ['x']];
        yield 'feature disabled (null)' => [null, 'abc'];
        yield 'feature disabled (empty)' => ['', 'abc'];
    }

    #[DataProvider('noNonceProvider')]
    public function testOmitsNonceWhenUnavailableOrDisabled(?string $attributeName, mixed $nonce): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 1, null, ['x'], 1.0));
        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'log', '[cdbg]', $attributeName);
        $request    = Request::create('/');
        if ($nonce !== null) {
            $request->attributes->set('csp_nonce', $nonce);
        }
        $response = new Response('<html><body></body></html>', 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response));

        $content = (string) $response->getContent();
        self::assertStringContainsString('<script data-nowo-console-debug>', $content);
        self::assertStringNotContainsString('nonce=', $content);
    }

    public function testLabelPrefixCannotCloseTheInlineScript(): void
    {
        $registry = new ConsoleDebugRegistry();
        $registry->add(new ConsoleDebugEntry('file.php', 1, null, ['x'], 1.0));
        $subscriber = new ConsoleDebugResponseSubscriber($registry, 'log', '</script><script>alert(1)</script>');
        $response   = new Response('<html><body></body></html>', 200, ['Content-Type' => 'text/html']);

        $subscriber->onKernelResponse(new ResponseEvent($this->createMock(HttpKernelInterface::class), Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));

        $content = (string) $response->getContent();
        self::assertStringNotContainsString('alert(1)</script>', $content);
        $lt = '\\u003C';
        $gt = '\\u003E';
        self::assertStringContainsString('var prefix = "' . $lt . '\\/script' . $gt . $lt . 'script' . $gt . 'alert(1)' . $lt . '\\/script' . $gt . '";', $content);
        self::assertSame(2, substr_count($content, '</script>'));
    }
}
