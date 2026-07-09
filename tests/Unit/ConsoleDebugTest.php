<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit;

use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugEntry;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\Gate\EnabledConsoleDebugGate;
use Nowo\ConsoleDebugBundle\Serializer\DebugValueNormalizer;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ConsoleDebugTest extends TestCase
{
    public function testLogStoresEntryWhenGateIsEnabled(): void
    {
        $registry = new ConsoleDebugRegistry();
        $service  = new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            $registry,
            new DebugValueNormalizer(),
            projectDir: sys_get_temp_dir(),
            shortenPaths: false,
        );

        $service->log('label', ['foo' => 'bar']);

        self::assertCount(1, $registry->all());
        $entry = $registry->all()[0];
        self::assertInstanceOf(ConsoleDebugEntry::class, $entry);
        self::assertSame('label', $entry->label);
        self::assertSame(['foo' => 'bar'], $entry->variables[0]);
    }

    public function testLogIsIgnoredWhenGateIsDisabled(): void
    {
        $registry = new ConsoleDebugRegistry();
        $service  = new ConsoleDebug(
            new EnabledConsoleDebugGate(false),
            $registry,
            new DebugValueNormalizer(),
            projectDir: null,
            shortenPaths: false,
        );

        $service->log('ignored');

        self::assertTrue($registry->isEmpty());
    }

    public function testShortensPathsRelativeToProjectDir(): void
    {
        $projectDir = sys_get_temp_dir();
        $registry   = new ConsoleDebugRegistry();
        $service    = new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            $registry,
            new DebugValueNormalizer(),
            projectDir: $projectDir,
            shortenPaths: true,
        );

        // Trigger log from known path by calling from this test file - we assert shortening logic indirectly
        $service->log('probe');
        $entry = $registry->all()[0];
        self::assertStringNotContainsString($projectDir, $entry->file);
    }

    public function testKeepsAbsolutePathWhenOutsideProjectDir(): void
    {
        $registry = new ConsoleDebugRegistry();
        $service  = new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            $registry,
            new DebugValueNormalizer(),
            projectDir: '/totally/different/project',
            shortenPaths: true,
        );

        $service->log('outside');
        $entry = $registry->all()[0];
        self::assertStringContainsString('ConsoleDebugTest.php', $entry->file);
    }

    public function testLogAtUsesProvidedSourceLocation(): void
    {
        $registry = new ConsoleDebugRegistry();
        $service  = new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            $registry,
            new DebugValueNormalizer(),
            projectDir: null,
            shortenPaths: false,
        );

        $service->logAt('templates/demo.html.twig', 18, 'twig context', ['user' => 'debugger']);

        $entry = $registry->all()[0];
        self::assertSame('templates/demo.html.twig', $entry->file);
        self::assertSame(18, $entry->line);
        self::assertSame('twig context', $entry->label);
    }

    public function testFormatPathStripsConfiguredProjectDirectory(): void
    {
        $service = new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            new ConsoleDebugRegistry(),
            new DebugValueNormalizer(),
            projectDir: '/var/www/app',
            shortenPaths: true,
        );

        $method = new ReflectionMethod(ConsoleDebug::class, 'formatPath');
        $method->setAccessible(true);

        self::assertSame('src/Controller/Demo.php', $method->invoke($service, '/var/www/app/src/Controller/Demo.php'));
    }
}
