<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\DependencyInjection;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;
use Nowo\ConsoleDebugBundle\Gate\EnabledConsoleDebugGate;
use Nowo\ConsoleDebugBundle\Gate\QueryParamConsoleDebugGate;
use Nowo\ConsoleDebugBundle\Gate\RoleBasedConsoleDebugGate;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

use function is_string;

/**
 * Loads bundle configuration and wires the default or custom debug gate.
 */
final class ConsoleDebugExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $container->setParameter('nowo.console_debug.enabled', $config['enabled']);
        $container->setParameter('nowo.console_debug.roles', $config['roles']);
        $container->setParameter('nowo.console_debug.console_method', $config['console_method']);
        $container->setParameter('nowo.console_debug.label_prefix', $config['label_prefix']);
        $container->setParameter('nowo.console_debug.shorten_paths', $config['shorten_paths']);
        $container->setParameter('nowo.console_debug.query_param', $config['query_param']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        if (class_exists(\Twig\Extension\AbstractExtension::class)) {
            $loader->load('twig.yaml');
        }

        $this->configureGate($container, $config);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function configureGate(ContainerBuilder $container, array $config): void
    {
        if (is_string($config['gate_service']) && $config['gate_service'] !== '') {
            $container->setAlias(ConsoleDebugGateInterface::class, $config['gate_service']);

            return;
        }

        $enabledGate = new Definition(EnabledConsoleDebugGate::class, ['$enabled' => $config['enabled']]);
        $enabledGate->setAutowired(false);
        $enabledGate->setPublic(false);
        $container->setDefinition('nowo.console_debug.gate.enabled', $enabledGate);

        $roleGate = new Definition(RoleBasedConsoleDebugGate::class, [
            '$innerGate'            => new Reference('nowo.console_debug.gate.enabled'),
            '$authorizationChecker' => new Reference('security.authorization_checker'),
            '$roles'                => $config['roles'],
        ]);
        $roleGate->setAutowired(false);
        $roleGate->setPublic(false);
        $container->setDefinition('nowo.console_debug.gate.roles', $roleGate);

        $innerGateId = 'nowo.console_debug.gate.roles';

        if (is_string($config['query_param']) && $config['query_param'] !== '') {
            $queryGate = new Definition(QueryParamConsoleDebugGate::class, [
                '$innerGate'    => new Reference($innerGateId),
                '$requestStack' => new Reference('request_stack'),
                '$queryParam'   => $config['query_param'],
            ]);
            $queryGate->setAutowired(false);
            $queryGate->setPublic(false);
            $container->setDefinition('nowo.console_debug.gate.query_param', $queryGate);
            $innerGateId = 'nowo.console_debug.gate.query_param';
        }

        $container->setAlias(ConsoleDebugGateInterface::class, $innerGateId);
    }

    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
