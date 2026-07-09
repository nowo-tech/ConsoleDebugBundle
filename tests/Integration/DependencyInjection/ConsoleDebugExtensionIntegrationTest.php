<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Integration\DependencyInjection;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;
use Nowo\ConsoleDebugBundle\DependencyInjection\ConsoleDebugExtension;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ConsoleDebugExtensionIntegrationTest extends TestCase
{
    public function testExtensionRegistersParametersAndDefaultGate(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->register('security.authorization_checker', stdClass::class);
        $container->register('request_stack', stdClass::class);

        $extension = new ConsoleDebugExtension();
        $extension->load([[
            'enabled'        => true,
            'roles'          => ['ROLE_CONSOLE_DEBUG'],
            'console_method' => 'info',
            'label_prefix'   => '[cdbg]',
            'shorten_paths'  => true,
            'query_param'    => null,
            'gate_service'   => null,
        ]], $container);

        self::assertTrue($container->getParameter('nowo.console_debug.enabled'));
        self::assertTrue($container->hasAlias(ConsoleDebugGateInterface::class));
        self::assertTrue($container->hasDefinition('nowo.console_debug'));
    }

    public function testExtensionUsesCustomGateServiceAlias(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->register('security.authorization_checker', stdClass::class);
        $container->register('request_stack', stdClass::class);

        $extension = new ConsoleDebugExtension();
        $extension->load([[
            'enabled'        => true,
            'roles'          => ['ROLE_CONSOLE_DEBUG'],
            'console_method' => 'info',
            'label_prefix'   => '[cdbg]',
            'shorten_paths'  => true,
            'query_param'    => null,
            'gate_service'   => 'app.custom_gate',
        ]], $container);

        self::assertSame('app.custom_gate', (string) $container->getAlias(ConsoleDebugGateInterface::class));
    }

    public function testExtensionWrapsQueryParamGateWhenConfigured(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->register('security.authorization_checker', stdClass::class);
        $container->register('request_stack', stdClass::class);

        $extension = new ConsoleDebugExtension();
        $extension->load([[
            'enabled'        => true,
            'roles'          => ['ROLE_CONSOLE_DEBUG'],
            'console_method' => 'info',
            'label_prefix'   => '[cdbg]',
            'shorten_paths'  => true,
            'query_param'    => 'console_debug',
            'gate_service'   => null,
        ]], $container);

        self::assertTrue($container->hasDefinition('nowo.console_debug.gate.query_param'));
    }

    public function testExtensionRegistersTwigIntegrationWhenTwigIsAvailable(): void
    {
        if (!class_exists(\Twig\Extension\AbstractExtension::class)) {
            self::markTestSkipped('Twig is not installed.');
        }

        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->register('security.authorization_checker', stdClass::class);
        $container->register('request_stack', stdClass::class);

        $extension = new ConsoleDebugExtension();
        $extension->load([[
            'enabled'        => true,
            'roles'          => ['ROLE_CONSOLE_DEBUG'],
            'console_method' => 'info',
            'label_prefix'   => '[cdbg]',
            'shorten_paths'  => true,
            'query_param'    => null,
            'gate_service'   => null,
        ]], $container);

        self::assertTrue($container->hasDefinition('Nowo\\ConsoleDebugBundle\\Twig\\ConsoleDebugTwigExtension'));
        self::assertTrue($container->hasDefinition('Nowo\\ConsoleDebugBundle\\Twig\\ConsoleDebugRuntime'));
    }
}
