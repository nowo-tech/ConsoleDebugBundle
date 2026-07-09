<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Twig\TokenParser;

use Nowo\ConsoleDebugBundle\Twig\Node\CdbgNode;
use Twig\Node\Nodes;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Parses {% cdbg %}, {% cdbg user %} and {% cdbg user, roles %} tags.
 */
final class CdbgTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): CdbgNode
    {
        $values = null;
        if (!$this->parser->getStream()->test(Token::BLOCK_END_TYPE)) {
            $values = $this->parseMultitargetExpression();
        }

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new CdbgNode($values, $token->getLine());
    }

    private function parseMultitargetExpression(): Nodes
    {
        $targets = [];
        while (true) {
            $targets[] = $this->parser->parseExpression();
            if (!$this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }

        return new Nodes($targets);
    }

    public function getTag(): string
    {
        return 'cdbg';
    }
}
