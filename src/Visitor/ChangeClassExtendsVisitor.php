<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangeClassExtendsVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Stmt\Class_
            && isset($this->fix['class'], $this->fix['extends'])
            && $node->name
            && $node->name->toString() === $this->fix['class']
        ) {
            $node->extends = new Node\Name($this->fix['extends']);
        }

        return null;
    }
}
