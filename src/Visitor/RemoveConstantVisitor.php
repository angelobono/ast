<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveConstantVisitor extends NodeVisitorAbstract
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
            && isset($this->fix['class'], $this->fix['constant'])
            && $node->name
            && $node->name->toString() === $this->fix['class']
        ) {
            foreach ($node->stmts as $i => $stmt) {
                if ($stmt instanceof Node\Stmt\ClassConst) {
                    foreach ($stmt->consts as $j => $const) {
                        if (
                            $const->name->toString()
                            === $this->fix['constant']
                        ) {
                            unset($stmt->consts[$j]);
                        }
                    }
                    if (empty($stmt->consts)) {
                        unset($node->stmts[$i]);
                    }
                }
            }
        }
        return null;
    }
}
