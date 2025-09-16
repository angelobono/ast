<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveCommentVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if ($node->getComments()) {
            $node->setAttribute('comments', []);
        }
        return null;
    }
}
