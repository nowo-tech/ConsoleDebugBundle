<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Twig\Node;

use Nowo\ConsoleDebugBundle\Twig\ConsoleDebugRuntime;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

use function sprintf;

/**
 * Compiles {% cdbg %} tags into runtime calls with template name and line.
 */
#[YieldReady]
final class CdbgNode extends Node
{
    public function __construct(
        ?Node $values,
        int $lineno,
    ) {
        $nodes = [];
        if ($values instanceof Node) {
            $nodes['values'] = $values;
        }

        parent::__construct($nodes, [], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $runtimeClass = ConsoleDebugRuntime::class;
        $templateLine = $this->getTemplateLine();

        if (!$this->hasNode('values')) {
            $compiler
                ->addDebugInfo($this)
                ->write(sprintf(
                    '$this->env->getRuntime(%s::class)->cdbgTag($context, $this->getTemplateName(), %d);' . "\n",
                    $runtimeClass,
                    $templateLine,
                ));

            return;
        }

        $values = $this->getNode('values');

        if ($values->count() === 1) {
            foreach ($values as $node) {
                $compiler
                    ->addDebugInfo($this)
                    ->write(sprintf(
                        '$this->env->getRuntime(%1$s::class)->cdbgTag($context, $this->getTemplateName(), %2$d, [',
                        $runtimeClass,
                        $templateLine,
                    ))
                    ->subcompile($node)
                    ->raw("]);\n");
                break;
            }

            return;
        }

        $compiler
            ->addDebugInfo($this)
            ->write(sprintf(
                '$this->env->getRuntime(%1$s::class)->cdbgTag($context, $this->getTemplateName(), %2$d, [',
                $runtimeClass,
                $templateLine,
            ))
            ->raw("\n")
            ->indent();

        foreach ($values as $node) {
            if ($node->hasAttribute('name')) {
                $compiler
                    ->write('')
                    ->string($node->getAttribute('name'))
                    ->raw(' => ');
            }

            $compiler
                ->subcompile($node)
                ->raw(",\n");
        }

        $compiler
            ->outdent()
            ->write("]);\n");
    }
}
