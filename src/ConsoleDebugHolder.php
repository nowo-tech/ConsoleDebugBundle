<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

use LogicException;

/**
 * Static bridge used by the global cdbg() helper.
 */
final class ConsoleDebugHolder
{
    private static ?ConsoleDebug $instance = null;

    public static function set(ConsoleDebug $consoleDebug): void
    {
        self::$instance = $consoleDebug;
    }

    public static function get(): ConsoleDebug
    {
        if (!self::$instance instanceof ConsoleDebug) {
            throw new LogicException('ConsoleDebugBundle is not booted or the nowo.console_debug service is unavailable.');
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
