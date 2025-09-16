<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RenameConstantVisitor extends NodeVisitorAbstract
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
            && isset($this->fix['class'], $this->fix['old_name'], $this->fix['new_name'])
            && $node->name
            && $node->name->toString() === $this->fix['class']
        ) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\ClassConst) {
                    foreach ($stmt->consts as $const) {
                        if (
                            $const->name->toString()
                            === $this->fix['old_name']
                        ) {
                            $const->name = new Node\Identifier(
                                $this->fix['new_name']
                            );
                        }
                    }
                }
            }
        }
        return null;
    }
}
