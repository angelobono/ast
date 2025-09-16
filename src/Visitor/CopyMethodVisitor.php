<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class CopyMethodVisitor extends NodeVisitorAbstract
{
    private string $sourceClass;
    private string $targetClass;
    private string $methodName;
    private ?ClassMethod $methodNode = null;

    public function __construct(array $data)
    {
        $this->sourceClass = $data['source_class'] ?? '';
        $this->targetClass = $data['target_class'] ?? '';
        $this->methodName  = $data['method'] ?? '';
    }

    public function enterNode(Node $node)
    {
        // Extrahiere Methode aus Quellklasse (aber nicht entfernen!)
        if (
            $node instanceof Class_
            && $node->name->toString() === $this->sourceClass
            && ! $this->methodNode
        ) {
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof ClassMethod
                    && $stmt->name->toString() === $this->methodName
                ) {
                    $this->methodNode = clone $stmt;
                    break;
                }
            }
        }
        // Füge Methode in Zielklasse ein
        if (
            $node instanceof Class_
            && $node->name->toString() === $this->targetClass
            && $this->methodNode
        ) {
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof ClassMethod
                    && $stmt->name->toString() === $this->methodName
                ) {
                    return null;
                }
            }
            $node->stmts[]    = $this->methodNode;
            $this->methodNode = null;
        }
        return null;
    }
}
