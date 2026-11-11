<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Integration\Worker;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Replaces services_resetter with a no-op, as a host that removes or bypasses the resetter does.
 */
final class ReplaceServicesResetterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->setDefinition('services_resetter', (new Definition(NoopServicesResetter::class))->setPublic(true));
    }
}
