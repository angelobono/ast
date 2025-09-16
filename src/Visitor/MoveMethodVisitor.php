<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

use function array_splice;

class MoveMethodVisitor extends NodeVisitorAbstract
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
        // Extrahiere Methode aus Quellklasse
        if (
            $node instanceof Class_
            && $node->name->toString() === $this->sourceClass
        ) {
            foreach ($node->stmts as $i => $stmt) {
                if (
                    $stmt instanceof ClassMethod
                    && $stmt->name->toString() === $this->methodName
                ) {
                    $this->methodNode = clone $stmt;
                    // Entferne Methode aus Quellklasse
                    array_splice($node->stmts, $i, 1);
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
            // Nur einfügen, wenn Methode nicht schon existiert
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof ClassMethod
                    && $stmt->name->toString() === $this->methodName
                ) {
                    return null;
                }
            }
            $node->stmts[]    = $this->methodNode;
            $this->methodNode = null; // Nur einmal einfügen
        }
        return null;
    }
}
