<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveDocblockVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if ($node->getDocComment() !== null) {
            $node->setDocComment(null);
        }
        return null;
    }
}
