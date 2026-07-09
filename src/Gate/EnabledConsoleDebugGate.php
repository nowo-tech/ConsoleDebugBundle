<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Gate;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;

/**
 * Master switch gate driven by bundle configuration.
 */
final class EnabledConsoleDebugGate implements ConsoleDebugGateInterface
{
    public function __construct(
        private readonly bool $enabled,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
