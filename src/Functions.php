<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

use LogicException;

/**
 * Global helper similar to dd(), but non-blocking and browser-console oriented.
 *
 * @param mixed ...$variables Values to log in the browser console
 */
function cdbg(mixed ...$variables): void
{
    try {
        ConsoleDebugHolder::get()->log(...$variables);
    } catch (LogicException) {
        // Bundle not booted: ignore silently so production code stays safe.
    }
}
