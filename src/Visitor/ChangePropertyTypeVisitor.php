<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;

class ChangePropertyTypeVisitor extends NodeVisitorAbstract
{
    private string $propertyName;
    private string $type;

    public function __construct(string $propertyName, string $type)
    {
        $this->propertyName = $propertyName;
        $this->type         = $type;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof Property
                    && $stmt->props[0]->name->toString() === $this->propertyName
                ) {
                    $stmt->type = new Identifier($this->type);
                }
            }
        }
        return null;
    }
}
