<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

use function array_filter;

class RemoveMethodVisitor extends NodeVisitorAbstract
{
    private string $methodName;

    public function __construct(string $methodName)
    {
        $this->methodName = $methodName;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $node->stmts = array_filter($node->stmts, function ($stmt) {
                return ! ($stmt instanceof ClassMethod
                    && $stmt->name->toString() === $this->methodName);
            });
        }
        return null;
    }
}
