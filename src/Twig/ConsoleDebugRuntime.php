<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Twig;

use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Runtime for {{ cdbg() }} and {% cdbg %} Twig integration.
 */
final class ConsoleDebugRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly ConsoleDebug $consoleDebug,
    ) {
    }

    /**
     * Twig function: {{ cdbg() }} dumps the full context; {{ cdbg(user) }} dumps given values.
     *
     * @param array<string, mixed> $context
     */
    public function cdbg(Environment $env, array $context, mixed ...$variables): string
    {
        if ($variables === []) {
            $this->consoleDebug->log('twig context', TwigContextExtractor::extract($context));

            return '';
        }

        $this->consoleDebug->log(...$variables);

        return '';
    }

    /**
     * Twig tag: {% cdbg %} or {% cdbg foo, bar %}.
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed>|list<mixed>|null $values
     */
    public function cdbgTag(array $context, string $template, int $line, ?array $values = null): void
    {
        if ($values === null || $values === []) {
            $this->consoleDebug->logAt($template, $line, 'twig context', TwigContextExtractor::extract($context));

            return;
        }

        if (array_is_list($values)) {
            $this->consoleDebug->logAt($template, $line, ...$values);

            return;
        }

        $this->consoleDebug->logAt($template, $line, $values);
    }
}
