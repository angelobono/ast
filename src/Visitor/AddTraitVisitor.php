<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function array_unshift;

class AddTraitVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Stmt\Class_ && isset($this->fix['class'])
            && $node->name
            && $node->name->toString() === $this->fix['class']
        ) {
            $traitName = $this->fix['trait'] ?? null;

            if ($traitName) {
                $traitUse = new Node\Stmt\TraitUse([
                    new Node\Name($traitName),
                ]);
                array_unshift($node->stmts, $traitUse);
            }
        }
        return null;
    }
}
