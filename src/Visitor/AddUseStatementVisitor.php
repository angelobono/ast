<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function array_unshift;

class AddUseStatementVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function beforeTraverse(array $nodes)
    {
        $params  = $this->fix['params'] ?? [];
        $useName = $params['use'] ?? null;
        if (! $useName) {
            return null;
        }
        $useNode = new Node\Stmt\Use_([
            new Node\Stmt\UseUse(new Node\Name($useName)),
        ]);
        // Prüfe, ob bereits ein use-Statement für diesen Namen existiert
        foreach ($nodes as $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                foreach ($stmt->uses as $useUse) {
                    if ($useUse->name->toString() === $useName) {
                        return null; // Bereits vorhanden
                    }
                }
            }
        }
        array_unshift($nodes, $useNode);
        return $nodes;
    }

    public function enterNode(Node $node)
    {
        $params  = $this->fix['params'] ?? [];
        $useName = $params['use'] ?? null;

        if (! $useName) {
            return null;
        }
        $useNode = new Node\Stmt\Use_([
            new Node\Stmt\UseUse(new Node\Name($useName)),
        ]);
        if ($node instanceof Node\Stmt\Namespace_) {
            // Prüfe, ob bereits ein use-Statement für diesen Namen existiert
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Use_) {
                    foreach ($stmt->uses as $useUse) {
                        if ($useUse->name->toString() === $useName) {
                            return null; // Bereits vorhanden
                        }
                    }
                }
            }
            array_unshift($node->stmts, $useNode);
        }
        return null;
    }
}
