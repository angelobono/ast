<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveMethodCallVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        $params = $this->fix['params'] ?? [];
        if (
            $node instanceof Node\Stmt\ClassMethod
            && isset($params['method'], $params['call'])
        ) {
            foreach ($node->stmts as $i => $stmt) {
                if (
                    $stmt instanceof Node\Stmt\Expression
                    && $stmt->expr instanceof Node\Expr\MethodCall
                    && $stmt->expr->name instanceof Node\Identifier
                    && $stmt->expr->name->toString() === $params['call']
                ) {
                    unset($node->stmts[$i]);
                }
            }
        }
        return null;
    }
}
