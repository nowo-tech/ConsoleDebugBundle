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
