<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveInterfaceVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        $params = $this->fix['params'] ?? [];
        if (
            $node instanceof Node\Stmt\Namespace_
            && isset($params['interface'])
        ) {
            foreach ($node->stmts as $i => $stmt) {
                if (
                    $stmt instanceof Node\Stmt\Interface_
                    && $stmt->name->toString() === $params['interface']
                ) {
                    unset($node->stmts[$i]);
                }
            }
        }
        return null;
    }
}
