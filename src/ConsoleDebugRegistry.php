<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Request-scoped storage for console debug entries.
 *
 * Cleared on every main kernel.request and after every main kernel.response so
 * entries never leak across requests when FrankenPHP runs without kernel reset.
 * Also implements ResetInterface (kernel.reset) when the services resetter runs.
 */
final class ConsoleDebugRegistry implements ResetInterface
{
    /** @var list<ConsoleDebugEntry> */
    private array $entries = [];

    /** Queues a debug entry for the current response. */
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

    /** Whether no entry is queued. */
    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    /** Drops every queued entry. */
    public function clear(): void
    {
        $this->entries = [];
    }

    /** Clears the entries between requests (kernel.reset, FrankenPHP worker mode). */
    public function reset(): void
    {
        $this->clear();
    }
}
