<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveTraitVisitor extends NodeVisitorAbstract
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
            && isset($this->fix['class'], $this->fix['trait'])
            && $node->name
            && $node->name->toString() === $this->fix['class']
        ) {
            foreach ($node->stmts as $i => $stmt) {
                if ($stmt instanceof Node\Stmt\TraitUse) {
                    foreach ($stmt->traits as $j => $trait) {
                        if ($trait->toString() === $this->fix['trait']) {
                            unset($stmt->traits[$j]);
                        }
                    }
                    if (empty($stmt->traits)) {
                        unset($node->stmts[$i]);
                    }
                }
            }
        }
        return null;
    }
}
