<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function array_map;
use function is_numeric;
use function is_string;

class ChangeMethodCallArgumentsVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Expr\MethodCall
            && isset($this->fix['old_args'], $this->fix['new_args'])
        ) {
            // Ersetze die Argumente des Methodenaufrufs
            $node->args = array_map(function ($arg) {
                return new Node\Arg(
                    is_string($arg)
                        ? new Node\Scalar\String_($arg)
                        : (is_numeric($arg) ? new Node\Scalar\LNumber($arg)
                        : $arg)
                );
            }, $this->fix['new_args']);
        }

        return null;
    }
}
