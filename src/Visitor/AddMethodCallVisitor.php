<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddMethodCallVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Stmt\ClassMethod
            && isset($this->fix['method'])
            && $node->name->toString() === $this->fix['method']
        ) {
            $callName = $this->fix['call'] ?? null;
            if ($callName) {
                $callNode      = new Node\Expr\MethodCall(
                    new Node\Expr\Variable('this'),
                    $callName
                );
                $node->stmts[] = new Node\Stmt\Expression($callNode);
            }
        }

        return null;
    }
}
