<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\DependencyInjection;

use Nowo\ConsoleDebugBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $processor = new Processor();
        $config    = $processor->processConfiguration(new Configuration(), []);

        self::assertTrue($config['enabled']);
        self::assertSame(['ROLE_CONSOLE_DEBUG'], $config['roles']);
        self::assertSame('log', $config['console_method']);
        self::assertSame('[cdbg]', $config['label_prefix']);
        self::assertTrue($config['shorten_paths']);
        self::assertNull($config['query_param']);
        self::assertNull($config['gate_service']);
    }

    public function testInvalidConsoleMethodIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [['console_method' => 'trace']]);
    }
}
