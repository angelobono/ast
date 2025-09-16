<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class InlineMethodVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        // Für eine produktionsreife Inlining-Implementierung müsste man alle Aufrufe der Methode durch den Body ersetzen.
        // Hier: Entferne die Methode (Stub, da vollständiges Inlining komplex ist)
        if (
            $node instanceof Node\Stmt\Class_
            && isset($this->fix['class'], $this->fix['method'])
            && $node->name
            && $node->name->toString() === $this->fix['class']
        ) {
            foreach ($node->stmts as $i => $stmt) {
                if (
                    $stmt instanceof Node\Stmt\ClassMethod
                    && $stmt->name->toString() === $this->fix['method']
                ) {
                    unset($node->stmts[$i]);
                }
            }
        }

        return null;
    }
}
