<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangeVariableTypeVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        // Ändert den Typ eines Parameters in einer Methode
        if (
            $node instanceof Node\Stmt\ClassMethod
            && isset($this->fix['method'], $this->fix['variable'], $this->fix['type'])
            && $node->name->toString() === $this->fix['method']
        ) {
            foreach ($node->params as $param) {
                if (
                    $param->var instanceof Node\Expr\Variable
                    && $param->var->name === $this->fix['variable']
                ) {
                    $param->type = new Node\Identifier($this->fix['type']);
                }
            }
        }
        return null;
    }
}
