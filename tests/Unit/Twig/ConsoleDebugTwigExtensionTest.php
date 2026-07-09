<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\Twig;

use Nowo\ConsoleDebugBundle\Twig\ConsoleDebugRuntime;
use Nowo\ConsoleDebugBundle\Twig\ConsoleDebugTwigExtension;
use Nowo\ConsoleDebugBundle\Twig\TokenParser\CdbgTokenParser;
use PHPUnit\Framework\TestCase;

final class ConsoleDebugTwigExtensionTest extends TestCase
{
    public function testRegistersFunctionAndTokenParser(): void
    {
        $extension = new ConsoleDebugTwigExtension();

        self::assertSame('cdbg', $extension->getFunctions()[0]->getName());
        self::assertSame([ConsoleDebugRuntime::class, 'cdbg'], $extension->getFunctions()[0]->getCallable());
        self::assertTrue($extension->getFunctions()[0]->needsContext());
        self::assertTrue($extension->getFunctions()[0]->needsEnvironment());
        self::assertInstanceOf(CdbgTokenParser::class, $extension->getTokenParsers()[0]);
        self::assertSame('cdbg', $extension->getTokenParsers()[0]->getTag());
    }
}
