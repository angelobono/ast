<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function array_map;
use function is_array;

class ChangeClassImplementsVisitor extends NodeVisitorAbstract
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
            && isset($this->fix['class'], $this->fix['implements'])
            && $node->name
            && $node->name->toString() === $this->fix['class']
        ) {
            $interfaces       = is_array($this->fix['implements'])
                ? $this->fix['implements'] : [$this->fix['implements']];
            $node->implements = array_map(
                fn($iface) => new Node\Name($iface),
                $interfaces
            );
        }

        return null;
    }
}
