<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class ChangeMethodVisibilityVisitor extends NodeVisitorAbstract
{
    private string $className;
    private string $methodName;
    private string $visibility;

    public function __construct(array $data)
    {
        $this->className  = $data['class'] ?? '';
        $this->methodName = $data['method'] ?? '';
        $this->visibility = $data['visibility'] ?? 'public';
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
                    // Entferne alle Sichtbarkeits-Flags
                    $stmt->flags &= ~Class_::MODIFIER_PUBLIC
                        | Class_::MODIFIER_PROTECTED
                        | Class_::MODIFIER_PRIVATE;
                    // Setze gewünschte Sichtbarkeit
                    $flag = match ($this->visibility) {
                        'private' => Class_::MODIFIER_PRIVATE,
                        'protected' => Class_::MODIFIER_PROTECTED,
                        default => Class_::MODIFIER_PUBLIC
                    };
                    $stmt->flags |= $flag;
                }
            }
        }
        return null;
    }
}
