<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\Twig;

use Nowo\ConsoleDebugBundle\Twig\TwigContextExtractor;
use PHPUnit\Framework\TestCase;
use Twig\Template;

final class TwigContextExtractorTest extends TestCase
{
    public function testExtractsContextWithoutEmbeddedTemplates(): void
    {
        $template = $this->createMock(Template::class);
        $env      = new \Twig\Environment(new \Twig\Loader\ArrayLoader(['x' => '']));
        $wrapper  = $env->load('x');

        $context = [
            'user'  => 'debugger',
            'items' => [1, 2],
            'macro' => $template,
            'wrap'  => $wrapper,
        ];

        self::assertSame(
            ['user' => 'debugger', 'items' => [1, 2]],
            TwigContextExtractor::extract($context),
        );
    }
}
