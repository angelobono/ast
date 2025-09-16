<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;
use Webmozart\Assert\Assert;

class MoveClassToNamespaceVisitor extends NodeVisitorAbstract
{
    private string $className;
    private string $targetNamespace;
    private bool $moved = false;

    public function __construct(array $data)
    {
        Assert::stringNotEmpty($data['className']);
        Assert::stringNotEmpty($data['targetNamespace']);
        $this->className       = $data['className'];
        $this->targetNamespace = $data['targetNamespace'];
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Namespace_ && ! $this->moved) {
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof Class_
                    && $stmt->name->toString() === $this->className
                ) {
                    // Namespace ändern
                    $node->name  = new Name($this->targetNamespace);
                    $this->moved = true;
                    break;
                }
            }
        }
        return null;
    }
}
