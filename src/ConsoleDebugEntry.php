<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

/**
 * Immutable snapshot of a single cdbg() call.
 */
final class ConsoleDebugEntry
{
    /**
     * @param list<mixed> $variables Values passed to cdbg()
     */
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly ?string $label,
        public readonly array $variables,
        public readonly float $timestamp,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file'      => $this->file,
            'line'      => $this->line,
            'label'     => $this->label,
            'variables' => $this->variables,
            'timestamp' => $this->timestamp,
        ];
    }
}
