<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Contract;

/**
 * Public contract of the browser-console debug collector.
 *
 * Type-hint this interface instead of the concrete (final) service. A host may replace the
 * implementation by re-aliasing it in its own container.
 */
interface ConsoleDebugInterface
{
    /**
     * Records the values for the browser console when the configured gate allows it.
     *
     * @param mixed ...$variables Values to expose in the browser console
     */
    public function log(mixed ...$variables): void;

    /**
     * Same as log(), with an explicit caller location.
     *
     * @param mixed ...$variables Values to expose in the browser console
     */
    public function logAt(string $file, int $line, mixed ...$variables): void;
}
