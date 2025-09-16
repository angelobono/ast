<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class RenameParameterVisitor extends NodeVisitorAbstract
{
    private string $className;
    private string $methodName;
    private string $oldName;
    private string $newName;

    public function __construct(array $data)
    {
        $this->className  = $data['class'] ?? '';
        $this->methodName = $data['method'] ?? '';
        $this->oldName    = $data['old_name'] ?? '';
        $this->newName    = $data['new_name'] ?? '';
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
                    foreach ($stmt->params as $param) {
                        if ($param->var->name === $this->oldName) {
                            $param->var->name = $this->newName;
                        }
                    }
                }
            }
        }
        return null;
    }
}
