<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Nop;
use PhpParser\NodeVisitorAbstract;

class FormatCodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        // Entferne leere Statements (z.B. überflüssige Semikolons)
        if ($node instanceof Nop) {
            return NodeVisitorAbstract::REMOVE_NODE;
        }
        return null;
    }
}
