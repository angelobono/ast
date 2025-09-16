<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;

use function array_filter;

class RemovePropertyVisitor extends NodeVisitorAbstract
{
    private string $propertyName;

    public function __construct(string $propertyName)
    {
        $this->propertyName = $propertyName;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $node->stmts = array_filter($node->stmts, function ($stmt) {
                return ! ($stmt instanceof Property
                    && $stmt->props[0]->name->toString()
                    === $this->propertyName);
            });
        }
        return null;
    }
}
