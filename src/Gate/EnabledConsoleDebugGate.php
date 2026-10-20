<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Gate;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;

/**
 * Master switch gate driven by bundle configuration.
 */
final class EnabledConsoleDebugGate implements ConsoleDebugGateInterface
{
    /** Gate driven by the `enabled` configuration flag. */
    public function __construct(
        private readonly bool $enabled,
    ) {
    }

    /** Returns the configured flag. */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
