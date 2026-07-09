<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Contract;

/**
 * Decides whether cdbg() output is collected and injected for the current request.
 */
interface ConsoleDebugGateInterface
{
    public function isEnabled(): bool;
}
