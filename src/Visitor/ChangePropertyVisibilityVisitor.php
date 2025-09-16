<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;

class ChangePropertyVisibilityVisitor extends NodeVisitorAbstract
{
    private string $className;
    private string $propertyName;
    private string $visibility;

    public function __construct(array $data)
    {
        $this->className    = $data['class'] ?? '';
        $this->propertyName = $data['property'] ?? '';
        $this->visibility   = $data['visibility'] ?? 'public';
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Class_
            && $node->name->toString() === $this->className
        ) {
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof Property && isset($stmt->props[0])
                    && $stmt->props[0]->name->toString() === $this->propertyName
                ) {
                    // Entferne alle Sichtbarkeits-Flags
                    $stmt->flags &= ~Modifiers::PUBLIC
                        | Modifiers::PROTECTED
                        | Modifiers::PRIVATE;
                    // Setze gewünschte Sichtbarkeit
                    $flag = match ($this->visibility) {
                        'private' => Modifiers::PRIVATE,
                        'protected' => Modifiers::PROTECTED,
                        default => Modifiers::PUBLIC
                    };
                    $stmt->flags |= $flag;
                }
            }
        }
        return null;
    }
}
