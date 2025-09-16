<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function array_unshift;

class ExtractInterfaceVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Stmt\Namespace_
            && isset($this->fix['class'], $this->fix['interface'])
        ) {
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof Node\Stmt\Class_ && $stmt->name
                    && $stmt->name->toString() === $this->fix['class']
                ) {
                    $interfaceName = $this->fix['interface'];
                    $methodStmts   = [];
                    foreach ($stmt->stmts as $classStmt) {
                        if ($classStmt instanceof Node\Stmt\ClassMethod) {
                            $methodStmts[] = new Node\Stmt\ClassMethod(
                                $classStmt->name,
                                [
                                    'flags'      => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                    'params'     => $classStmt->params,
                                    'returnType' => $classStmt->returnType,
                                    'stmts'      => null,
                                ]
                            );
                        }
                    }
                    $interfaceNode = new Node\Stmt\Interface_(
                        $interfaceName,
                        ['stmts' => $methodStmts]
                    );
                    array_unshift($node->stmts, $interfaceNode);
                }
            }
        }
        return null;
    }

    public function beforeTraverse(array $nodes)
    {
        if (! isset($this->fix['class'], $this->fix['interface'])) {
            return null;
        }
        $interfaceName = $this->fix['interface'];
        foreach ($nodes as $stmt) {
            if (
                $stmt instanceof Node\Stmt\Class_ && $stmt->name
                && $stmt->name->toString() === $this->fix['class']
            ) {
                $methodStmts = [];
                foreach ($stmt->stmts as $classStmt) {
                    if ($classStmt instanceof Node\Stmt\ClassMethod) {
                        $methodStmts[] = new Node\Stmt\ClassMethod(
                            $classStmt->name,
                            [
                                'flags'      => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'params'     => $classStmt->params,
                                'returnType' => $classStmt->returnType,
                                'stmts'      => null,
                            ]
                        );
                    }
                }
                $interfaceNode = new Node\Stmt\Interface_(
                    $interfaceName,
                    ['stmts' => $methodStmts]
                );
                array_unshift($nodes, $interfaceNode);
                break;
            }
        }
        return $nodes;
    }
}
