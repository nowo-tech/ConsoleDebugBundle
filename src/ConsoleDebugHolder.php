<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

use LogicException;

/**
 * Process bridge used by the global cdbg() helper.
 *
 * Avoids mutable static properties (FrankenPHP worker-safe). The ConsoleDebug
 * service reference is stored under a dedicated $_SERVER key and re-bound on
 * each kernel.request (FrankenPHP resets $_SERVER between worker iterations).
 * Request payloads live in ConsoleDebugRegistry (ResetInterface / kernel.reset).
 */
final class ConsoleDebugHolder
{
    private const SERVER_KEY = '__NOWO_CONSOLE_DEBUG';

    public static function set(ConsoleDebug $consoleDebug): void
    {
        $_SERVER[self::SERVER_KEY] = $consoleDebug;
    }

    public static function get(): ConsoleDebug
    {
        $instance = $_SERVER[self::SERVER_KEY] ?? null;
        if (!$instance instanceof ConsoleDebug) {
            throw new LogicException('ConsoleDebugBundle is not booted or the nowo.console_debug service is unavailable.');
        }

        return $instance;
    }

    public static function reset(): void
    {
        unset($_SERVER[self::SERVER_KEY]);
    }
}
