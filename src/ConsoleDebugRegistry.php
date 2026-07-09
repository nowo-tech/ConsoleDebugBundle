<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

/**
 * Request-scoped storage for console debug entries.
 */
final class ConsoleDebugRegistry
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
}
