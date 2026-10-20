<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Contract;

/**
 * Decides whether cdbg() output is collected and injected for the current request.
 */
interface ConsoleDebugGateInterface
{
    /** Whether console debugging is allowed for the current request. */
    public function isEnabled(): bool;
}
