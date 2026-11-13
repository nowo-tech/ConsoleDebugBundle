<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle;

use LogicException;
use Nowo\ConsoleDebugBundle\Contract\ConsoleDebugInterface;

/**
 * Process bridge used by the global cdbg() helper.
 *
 * Avoids mutable static properties (FrankenPHP worker-safe). The ConsoleDebug
 * service reference is stored under a dedicated $_SERVER key and re-bound on
 * each kernel.request (FrankenPHP resets $_SERVER between worker iterations).
 * Request payloads live in ConsoleDebugRegistry (cleared on each main request/response;
 * also ResetInterface / kernel.reset when the services resetter runs).
 */
final class ConsoleDebugHolder
{
    private const SERVER_KEY = '__NOWO_CONSOLE_DEBUG';

    /** Stores the request-scoped ConsoleDebug service for the static cdbg() helpers. */
    public static function set(ConsoleDebugInterface $consoleDebug): void
    {
        // @igor-ignore - Holds the shared ConsoleDebug service (no request data); FrankenPHP resets $_SERVER per request and kernel.request re-binds it.
        $_SERVER[self::SERVER_KEY] = $consoleDebug;
    }

    /** Returns the stored ConsoleDebug service; throws when the bundle is not booted. */
    public static function get(): ConsoleDebugInterface
    {
        // @igor-ignore - Holds the shared ConsoleDebug service (no request data); FrankenPHP resets $_SERVER per request and kernel.request re-binds it.
        $instance = $_SERVER[self::SERVER_KEY] ?? null;
        if (!$instance instanceof ConsoleDebugInterface) {
            throw new LogicException('ConsoleDebugBundle is not booted or the nowo.console_debug service is unavailable.');
        }

        return $instance;
    }

    /** Forgets the stored service (worker reset / tests). */
    public static function reset(): void
    {
        // @igor-ignore - Holds the shared ConsoleDebug service (no request data); FrankenPHP resets $_SERVER per request and kernel.request re-binds it.
        unset($_SERVER[self::SERVER_KEY]);
    }
}
