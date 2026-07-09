<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit;

use LogicException;
use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugHolder;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\DependencyInjection\ConsoleDebugExtension;
use Nowo\ConsoleDebugBundle\Gate\CompositeConsoleDebugGate;
use Nowo\ConsoleDebugBundle\Gate\EnabledConsoleDebugGate;
use Nowo\ConsoleDebugBundle\NowoConsoleDebugBundle;
use Nowo\ConsoleDebugBundle\Serializer\DebugValueNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

use function Nowo\ConsoleDebugBundle\cdbg;

final class ConsoleDebugHolderAndBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        ConsoleDebugHolder::reset();
    }

    public function testHolderStoresAndReturnsInstance(): void
    {
        $service = new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            new ConsoleDebugRegistry(),
            new DebugValueNormalizer(),
            null,
            false,
        );

        ConsoleDebugHolder::set($service);
        self::assertSame($service, ConsoleDebugHolder::get());
    }

    public function testHolderThrowsWhenNotBooted(): void
    {
        $this->expectException(LogicException::class);
        ConsoleDebugHolder::get();
    }

    public function testGlobalCdbgUsesHolder(): void
    {
        $registry = new ConsoleDebugRegistry();
        ConsoleDebugHolder::set(new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            $registry,
            new DebugValueNormalizer(),
            null,
            false,
        ));

        cdbg('from helper', 123);

        self::assertCount(1, $registry->all());
    }

    public function testGlobalCdbgSilentlyIgnoresWhenHolderMissing(): void
    {
        cdbg('ignored');
        $this->expectNotToPerformAssertions();
    }

    public function testCompositeGateRequiresAllInnerGates(): void
    {
        $gate = new CompositeConsoleDebugGate([
            new EnabledConsoleDebugGate(true),
            new EnabledConsoleDebugGate(false),
        ]);
        self::assertFalse($gate->isEnabled());

        $gateOk = new CompositeConsoleDebugGate([
            new EnabledConsoleDebugGate(true),
            new EnabledConsoleDebugGate(true),
        ]);
        self::assertTrue($gateOk->isEnabled());
    }

    public function testBundleBootSetsHolder(): void
    {
        $registry = new ConsoleDebugRegistry();
        $service  = new ConsoleDebug(
            new EnabledConsoleDebugGate(true),
            $registry,
            new DebugValueNormalizer(),
            null,
            false,
        );

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('nowo.console_debug')->willReturn(true);
        $container->method('get')->with('nowo.console_debug')->willReturn($service);

        $bundle = new NowoConsoleDebugBundle();
        $bundle->setContainer($container);
        $bundle->boot();

        cdbg('booted');
        self::assertCount(1, $registry->all());
    }

    public function testBundleExtensionAndAlias(): void
    {
        $bundle    = new NowoConsoleDebugBundle();
        $extension = $bundle->getContainerExtension();
        self::assertInstanceOf(ConsoleDebugExtension::class, $extension);
        self::assertInstanceOf(ExtensionInterface::class, $extension);
        self::assertSame('nowo_console_debug', $extension->getAlias());
    }
}
