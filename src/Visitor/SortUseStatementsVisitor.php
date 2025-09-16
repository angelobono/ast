<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

use function array_merge;
use function strcmp;
use function usort;

class SortUseStatementsVisitor extends NodeVisitorAbstract
{
    public function beforeTraverse(array $nodes)
    {
        $useStmts   = [];
        $otherStmts = [];
        foreach ($nodes as $stmt) {
            if ($stmt instanceof Use_) {
                $useStmts[] = $stmt;
            } else {
                $otherStmts[] = $stmt;
            }
        }
        usort($useStmts, function ($a, $b) {
            return strcmp(
                $a->uses[0]->name->toString(),
                $b->uses[0]->name->toString()
            );
        });
        return array_merge($useStmts, $otherStmts);
    }
}
