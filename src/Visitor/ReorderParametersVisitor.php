<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

use function in_array;

class ReorderParametersVisitor extends NodeVisitorAbstract
{
    private string $className;
    private string $methodName;
    private array $order;

    public function __construct(array $data)
    {
        $this->className  = $data['class'] ?? '';
        $this->methodName = $data['method'] ?? '';
        $this->order      = $data['order'] ?? [];
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Class_
            && $node->name->toString() === $this->className
        ) {
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof ClassMethod
                    && $stmt->name->toString() === $this->methodName
                ) {
                    $paramsByName = [];
                    foreach ($stmt->params as $param) {
                        $paramsByName[$param->var->name] = $param;
                    }
                    $newParams = [];
                    foreach ($this->order as $name) {
                        if (isset($paramsByName[$name])) {
                            $newParams[] = $paramsByName[$name];
                        }
                    }
                    // Füge nicht explizit genannte Parameter hinten an
                    foreach ($stmt->params as $param) {
                        if (! in_array($param, $newParams, true)) {
                            $newParams[] = $param;
                        }
                    }
                    $stmt->params = $newParams;
                }
            }
        }
        return null;
    }
}
