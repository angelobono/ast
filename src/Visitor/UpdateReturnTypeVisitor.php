<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class UpdateReturnTypeVisitor extends NodeVisitorAbstract
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
            $node instanceof Node\Stmt\ClassMethod
            && isset($params['method'], $params['return_type'])
            && $node->name->toString() === $params['method']
        ) {
            $node->returnType = new Node\Identifier($params['return_type']);
        }
        return null;
    }
}
