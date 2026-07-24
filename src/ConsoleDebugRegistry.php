<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Request-scoped storage for console debug entries.
 *
 * Implements ResetInterface so FrankenPHP worker (and long-lived FPM) clear
 * entries between requests even if the response subscriber path is skipped.
 */
final class ConsoleDebugRegistry implements ResetInterface
{
    /** @var list<ConsoleDebugEntry> */
    private array $entries = [];

    public function add(ConsoleDebugEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    /**
     * @return list<ConsoleDebugEntry>
     */
    public function all(): array
    {
        return $this->entries;
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function clear(): void
    {
        $this->entries = [];
    }

    public function reset(): void
    {
        $this->clear();
    }
}
