<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

class RenameClassVisitor extends NodeVisitorAbstract
{
    private string $oldName;
    private string $newName;

    public function __construct(string $oldName, string $newName)
    {
        $this->oldName = $oldName;
        $this->newName = $newName;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Class_
            && $node->name->toString() === $this->oldName
        ) {
            $node->name->name = $this->newName;
        }
        return null;
    }
}
