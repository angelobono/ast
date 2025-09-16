<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class UpdateDocblockVisitor extends NodeVisitorAbstract
{
    private string $className;
    private ?string $methodName;
    private string $docblock;

    public function __construct(array $data)
    {
        $this->className  = $data['class'] ?? '';
        $this->methodName = $data['method'] ?? null;
        $this->docblock   = $data['docblock'] ?? '';
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Class_
            && $node->name->toString() === $this->className
        ) {
            if ($this->methodName) {
                foreach ($node->stmts as $stmt) {
                    if (
                        $stmt instanceof ClassMethod
                        && $stmt->name->toString() === $this->methodName
                    ) {
                        $stmt->setDocComment(new Doc($this->docblock));
                    }
                }
            } else {
                $node->setDocComment(new Doc($this->docblock));
            }
        }
        return null;
    }
}
