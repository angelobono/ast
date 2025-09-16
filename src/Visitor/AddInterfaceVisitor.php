<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;

class AddInterfaceVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $interfaceName = $this->fix['interface'] ?? null;

            if ($interfaceName) {
                $interfaceNode = new Interface_(
                    new Identifier($interfaceName)
                );
                $node->stmts[] = $interfaceNode;
            }
        }

        return null;
    }

    public function beforeTraverse(array $nodes): array
    {
        $params        = $this->fix['params'] ?? $this->fix;
        $interfaceName = $params['interface'] ?? null;

        if (! $interfaceName) {
            return $nodes;
        }
        // Prüfen, ob Interface schon existiert
        foreach ($nodes as $node) {
            if (
                $node instanceof Interface_ && $node->name
                && $node->name->toString() === $interfaceName
            ) {
                return $nodes;
            }
        }
        $interfaceNode = new Interface_(new Identifier($interfaceName));
        $nodes[]       = $interfaceNode;
        return $nodes;
    }
}
