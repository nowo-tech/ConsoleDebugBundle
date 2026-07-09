<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugGateInterface;
use Nowo\ConsoleDebugBundle\Serializer\DebugValueNormalizer;

use function array_slice;
use function is_string;
use function strlen;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

/**
 * Collects cdbg() calls when the configured gate allows it.
 */
final class ConsoleDebug
{
    public function __construct(
        private readonly ConsoleDebugGateInterface $gate,
        private readonly ConsoleDebugRegistry $registry,
        private readonly DebugValueNormalizer $normalizer,
        private readonly ?string $projectDir,
        private readonly bool $shortenPaths,
    ) {
    }

    /**
     * @param mixed ...$variables Values to expose in the browser console
     */
    public function log(mixed ...$variables): void
    {
        if (!$this->gate->isEnabled()) {
            return;
        }

        $trace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
        $caller = $this->resolveCallerFrame($trace);
        $file   = $caller['file'] ?? 'unknown';
        $line   = (int) ($caller['line'] ?? 0);

        $this->record($file, $line, ...$variables);
    }

    /**
     * @param mixed ...$variables Values to expose in the browser console
     */
    public function logAt(string $file, int $line, mixed ...$variables): void
    {
        $this->record($file, $line, ...$variables);
    }

    /**
     * @param mixed ...$variables Values to expose in the browser console
     */
    private function record(string $file, int $line, mixed ...$variables): void
    {
        if (!$this->gate->isEnabled()) {
            return;
        }

        $label = null;
        if ($variables !== [] && is_string($variables[0])) {
            $label     = $variables[0];
            $variables = array_values(array_slice($variables, 1));
        }

        $this->registry->add(new ConsoleDebugEntry(
            file: $this->formatPath($file),
            line: $line,
            label: $label,
            variables: $this->normalizer->normalizeMany(array_values($variables)),
            timestamp: microtime(true),
        ));
    }

    private function formatPath(string $file): string
    {
        if (!$this->shortenPaths || $this->projectDir === null || $this->projectDir === '') {
            return $file;
        }

        $projectDir = rtrim(str_replace('\\', '/', $this->projectDir), '/') . '/';
        $file       = str_replace('\\', '/', $file);

        if (str_starts_with($file, $projectDir)) {
            return substr($file, strlen($projectDir));
        }

        return $file;
    }

    /**
     * @param list<array<string, mixed>> $trace
     *
     * @return array<string, mixed>
     */
    private function resolveCallerFrame(array $trace): array
    {
        foreach ($trace as $frame) {
            $file = $frame['file'] ?? null;
            if (!is_string($file) || str_contains($file, 'ConsoleDebug.php')) {
                continue;
            }

            return $frame;
        }

        return $trace[1] ?? $trace[0];
    }
}
