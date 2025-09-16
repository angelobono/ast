<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ReplaceLiteralVisitor extends NodeVisitorAbstract
{
    private $old;
    private $new;

    public function __construct(array $data)
    {
        $this->old = $data['old'] ?? null;
        $this->new = $data['new'] ?? null;
    }

    public function enterNode(Node $node)
    {
        // Ersetze nur einfache Literale (String, Int, Float, Bool)
        if ($this->old === null || $this->new === null) {
            return null;
        }
        if (
            $node instanceof Node\Scalar\String_
            && $node->value === $this->old
        ) {
            $node->value = $this->new;
        } elseif (
            $node instanceof Node\Scalar\LNumber
            && $node->value === $this->old
        ) {
            $node->value = $this->new;
        } elseif (
            $node instanceof Node\Scalar\DNumber
            && $node->value === $this->old
        ) {
            $node->value = $this->new;
        } elseif (
            $node instanceof Node\Expr\ConstFetch
            && $node->name->toString() === $this->old
        ) {
            $node->name->parts = [$this->new];
        }
        return null;
    }
}
