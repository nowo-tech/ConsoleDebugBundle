<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Gate;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;

/**
 * Runs multiple gates; all must pass.
 */
final class CompositeConsoleDebugGate implements ConsoleDebugGateInterface
{
    /**
     * @param iterable<ConsoleDebugGateInterface> $gates
     */
    public function __construct(
        private readonly iterable $gates,
    ) {
    }

    public function isEnabled(): bool
    {
        foreach ($this->gates as $gate) {
            if (!$gate->isEnabled()) {
                return false;
            }
        }

        return true;
    }
}
