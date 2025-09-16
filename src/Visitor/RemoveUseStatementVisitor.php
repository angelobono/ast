<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveUseStatementVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function beforeTraverse(array $nodes)
    {
        $useName = $this->fix['use'] ?? null;
        if (! $useName) {
            return null;
        }
        $newNodes = [];
        foreach ($nodes as $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                $remainingUses = [];
                foreach ($stmt->uses as $useUse) {
                    if ($useUse->name->toString() !== $useName) {
                        $remainingUses[] = $useUse;
                    }
                }
                if (! empty($remainingUses)) {
                    $stmt->uses = $remainingUses;
                    $newNodes[] = $stmt;
                }
                // Sonst: use-Statement komplett entfernen
            } else {
                $newNodes[] = $stmt;
            }
        }
        return $newNodes;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $useName = $this->fix['use'] ?? null;
            if (! $useName) {
                return null;
            }
            $newStmts = [];
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Use_) {
                    $remainingUses = [];
                    foreach ($stmt->uses as $useUse) {
                        if ($useUse->name->toString() !== $useName) {
                            $remainingUses[] = $useUse;
                        }
                    }
                    if (! empty($remainingUses)) {
                        $stmt->uses = $remainingUses;
                        $newStmts[] = $stmt;
                    }
                    // Sonst: use-Statement komplett entfernen
                } else {
                    $newStmts[] = $stmt;
                }
            }
            $node->stmts = $newStmts;
        }
        // Entferne die File-Node Behandlung, da es im globalen Scope keine Node\Stmt\File gibt
        return null;
    }
}
