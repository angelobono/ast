<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class MakeMethodStaticVisitor extends NodeVisitorAbstract
{
    private string $className;
    private string $methodName;

    public function __construct(array $data)
    {
        $this->className  = $data['class'] ?? '';
        $this->methodName = $data['method'] ?? '';
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Class_
            && $node->name->toString() === $this->className
        ) {
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof ClassMethod
                    && $stmt->name->toString() === $this->methodName
                ) {
                    $stmt->flags |= Class_::MODIFIER_STATIC;
                }
            }
        }
        return null;
    }
}
