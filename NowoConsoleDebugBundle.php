<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

use Nowo\ConsoleDebugBundle\DependencyInjection\ConsoleDebugExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle that outputs cdbg() calls to the browser console for authorized users.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class NowoConsoleDebugBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            $this->extension = new ConsoleDebugExtension();
        }

        return $this->extension instanceof ExtensionInterface ? $this->extension : null;
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->container !== null && $this->container->has('nowo.console_debug')) {
            ConsoleDebugHolder::set($this->container->get('nowo.console_debug'));
        }
    }
}
