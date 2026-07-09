<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Twig;

use Twig\Template;
use Twig\TemplateWrapper;

/**
 * Extracts Twig template variables for cdbg(), mirroring native dump() context behaviour.
 */
final class TwigContextExtractor
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function extract(array $context): array
    {
        $variables = [];

        foreach ($context as $key => $value) {
            if ($value instanceof Template || $value instanceof TemplateWrapper) {
                continue;
            }

            $variables[$key] = $value;
        }

        return $variables;
    }
}
