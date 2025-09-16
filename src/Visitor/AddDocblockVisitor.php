<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddDocblockVisitor extends NodeVisitorAbstract
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
            $doc = $this->fix['docblock'] ?? null;
            if ($doc) {
                $node->setDocComment(new Doc($doc));
            }
        }

        return null;
    }
}
