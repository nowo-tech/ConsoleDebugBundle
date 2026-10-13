<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function is_string;

/**
 * Defines the configuration tree for nowo_console_debug.
 */
final class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'nowo_console_debug';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('enabled')
                    ->info('Master switch for console debug collection and injection.')
                    ->defaultTrue()
                ->end()
                ->arrayNode('roles')
                    ->info('At least one role is required for the default gate (e.g. ROLE_CONSOLE_DEBUG).')
                    ->defaultValue(['ROLE_CONSOLE_DEBUG'])
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('console_method')
                    ->info('Browser console method: log, info, warn, debug, or error.')
                    ->defaultValue('log')
                    ->validate()
                        ->ifNotInArray(['log', 'info', 'warn', 'debug', 'error'])
                        ->thenInvalid('console_method must be one of: log, info, warn, debug, error.')
                    ->end()
                ->end()
                ->scalarNode('label_prefix')
                    ->info('Prefix shown in grouped console output.')
                    ->defaultValue('[cdbg]')
                ->end()
                ->scalarNode('csp_nonce_attribute')
                    ->info('Request attribute holding the CSP nonce stamped on the injected <script> tags (shared nowo-tech convention: csp_nonce). Null or empty disables it.')
                    ->defaultValue('csp_nonce')
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => $v !== null && !is_string($v))
                        ->thenInvalid('csp_nonce_attribute must be a string (request attribute name) or null.')
                    ->end()
                ->end()
                ->booleanNode('shorten_paths')
                    ->info('When true, file paths are shortened relative to kernel.project_dir.')
                    ->defaultTrue()
                ->end()
                ->scalarNode('query_param')
                    ->info('Optional query parameter required by the default gate (e.g. console_debug).')
                    ->defaultNull()
                ->end()
                ->scalarNode('gate_service')
                    ->info('Custom gate service id implementing ConsoleDebugGateInterface. Replaces the default gate when set.')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
