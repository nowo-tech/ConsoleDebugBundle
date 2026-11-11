<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Integration\Worker;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;

/**
 * Custom gate of the worker test (`gate_service`): always open, including in Messenger consumers and console
 * commands, which is the case where entries can pile up outside HTTP.
 */
final class OpenConsoleDebugGate implements ConsoleDebugGateInterface
{
    public function isEnabled(): bool
    {
        return true;
    }
}
