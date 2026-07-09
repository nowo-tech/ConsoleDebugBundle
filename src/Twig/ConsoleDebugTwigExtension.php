<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Twig;

use Nowo\ConsoleDebugBundle\Twig\TokenParser\CdbgTokenParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Registers {{ cdbg() }} and {% cdbg %} for browser console debugging from Twig templates.
 */
final class ConsoleDebugTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cdbg', [ConsoleDebugRuntime::class, 'cdbg'], [
                'needs_context'     => true,
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
        ];
    }

    public function getTokenParsers(): array
    {
        return [new CdbgTokenParser()];
    }
}
