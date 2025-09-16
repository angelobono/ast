<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RenameVariableVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Expr\Variable
            && isset($this->fix['old_name'], $this->fix['new_name'])
            && $node->name === $this->fix['old_name']
        ) {
            $node->name = $this->fix['new_name'];
        }
        return null;
    }
}
