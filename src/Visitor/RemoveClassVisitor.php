<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

use function array_filter;

class RemoveClassVisitor extends NodeVisitorAbstract
{
    private string $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function beforeTraverse(array $nodes)
    {
        return array_filter($nodes, function ($n) {
            return ! ($n instanceof Class_
                && $n->name->toString() === $this->className);
        });
    }
}
