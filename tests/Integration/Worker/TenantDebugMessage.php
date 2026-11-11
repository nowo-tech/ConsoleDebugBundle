<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Integration\Worker;

/**
 * Messenger message of the worker test: one unit of work for one user (every 7th handler fails afterwards).
 */
final readonly class TenantDebugMessage
{
    public function __construct(
        public string $user,
        public int $index,
        public bool $fail,
    ) {
    }
}
