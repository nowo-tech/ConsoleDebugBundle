<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\Twig;

use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\Gate\EnabledConsoleDebugGate;
use Nowo\ConsoleDebugBundle\Serializer\DebugValueNormalizer;
use Nowo\ConsoleDebugBundle\Twig\ConsoleDebugRuntime;
use Nowo\ConsoleDebugBundle\Twig\ConsoleDebugTwigExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

final class CdbgTokenParserAndNodeTest extends TestCase
{
    private ConsoleDebugRegistry $registry;

    private Environment $twig;

    protected function setUp(): void
    {
        $this->registry = new ConsoleDebugRegistry();
        $runtime        = new ConsoleDebugRuntime(new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            $this->registry,
            new DebugValueNormalizer(),
            projectDir: null,
            shortenPaths: false,
        ));

        $this->twig = new Environment(new ArrayLoader([
            'empty.twig'  => '{% cdbg %}',
            'single.twig' => '{% cdbg user %}',
            'multi.twig'  => '{% cdbg user, roles %}',
        ]));
        $this->twig->addExtension(new ConsoleDebugTwigExtension());
        $this->twig->addRuntimeLoader(new FactoryRuntimeLoader([
            ConsoleDebugRuntime::class => static fn (): ConsoleDebugRuntime => $runtime,
        ]));
    }

    public function testEmptyTagDumpsFullContext(): void
    {
        self::assertSame('', $this->twig->render('empty.twig', ['user' => 'debugger']));

        self::assertCount(1, $this->registry->all());
        self::assertSame('twig context', $this->registry->all()[0]->label);
        self::assertSame(['user' => 'debugger'], $this->registry->all()[0]->variables[0]);
    }

    public function testSingleExpressionTag(): void
    {
        self::assertSame('', $this->twig->render('single.twig', ['user' => ['id' => 7]]));

        self::assertNull($this->registry->all()[0]->label);
        self::assertSame(['id' => 7], $this->registry->all()[0]->variables[0]);
    }

    public function testMultiExpressionTagUsesNamedKeys(): void
    {
        self::assertSame('', $this->twig->render('multi.twig', [
            'user'  => 'debugger',
            'roles' => ['ROLE_CONSOLE_DEBUG'],
        ]));

        self::assertSame([
            'user'  => 'debugger',
            'roles' => ['ROLE_CONSOLE_DEBUG'],
        ], $this->registry->all()[0]->variables[0]);
    }
}
