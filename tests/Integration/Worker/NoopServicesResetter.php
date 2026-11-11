<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Integration\Worker;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Replaces services_resetter in the worker test kernel: a host that removes or bypasses the resetter. Kernel::boot()
 * still calls it before each request after the first; it resets nothing.
 */
final class NoopServicesResetter implements ResetInterface
{
    public int $calls = 0;

    public function reset(): void
    {
        ++$this->calls;
    }
}
