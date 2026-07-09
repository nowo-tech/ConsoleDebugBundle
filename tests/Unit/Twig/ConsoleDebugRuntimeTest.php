<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\Twig;

use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\Gate\EnabledConsoleDebugGate;
use Nowo\ConsoleDebugBundle\Serializer\DebugValueNormalizer;
use Nowo\ConsoleDebugBundle\Twig\ConsoleDebugRuntime;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class ConsoleDebugRuntimeTest extends TestCase
{
    private ConsoleDebugRegistry $registry;

    private ConsoleDebugRuntime $runtime;

    protected function setUp(): void
    {
        $this->registry = new ConsoleDebugRegistry();
        $this->runtime  = new ConsoleDebugRuntime(new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            $this->registry,
            new DebugValueNormalizer(),
            projectDir: null,
            shortenPaths: false,
        ));
    }

    public function testFunctionDumpsFullContextWhenCalledWithoutArguments(): void
    {
        $env = new Environment(new ArrayLoader(['tpl' => '']));

        self::assertSame('', $this->runtime->cdbg($env, ['user' => 'debugger', 'count' => 2]));
        self::assertCount(1, $this->registry->all());
        self::assertSame('twig context', $this->registry->all()[0]->label);
        self::assertSame(['user' => 'debugger', 'count' => 2], $this->registry->all()[0]->variables[0]);
    }

    public function testFunctionDumpsProvidedVariables(): void
    {
        $env = new Environment(new ArrayLoader(['tpl' => '']));

        self::assertSame('', $this->runtime->cdbg($env, [], ['status' => 'ok']));

        self::assertSame(['status' => 'ok'], $this->registry->all()[0]->variables[0]);
    }

    public function testTagDumpsFullContextWhenValuesAreEmpty(): void
    {
        $this->runtime->cdbgTag(['role' => 'admin'], 'demo/debug.html.twig', 12);

        $entry = $this->registry->all()[0];
        self::assertSame('demo/debug.html.twig', $entry->file);
        self::assertSame(12, $entry->line);
        self::assertSame('twig context', $entry->label);
        self::assertSame(['role' => 'admin'], $entry->variables[0]);
    }

    public function testTagDumpsListValues(): void
    {
        $this->runtime->cdbgTag([], 'demo/debug.html.twig', 3, [['id' => 7]]);

        self::assertSame(['id' => 7], $this->registry->all()[0]->variables[0]);
    }

    public function testTagDumpsAssociativeValues(): void
    {
        $this->runtime->cdbgTag([], 'demo/debug.html.twig', 3, ['user' => 'debugger']);

        self::assertSame(['user' => 'debugger'], $this->registry->all()[0]->variables[0]);
    }
}
