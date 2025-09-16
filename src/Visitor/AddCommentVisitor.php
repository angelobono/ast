<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddCommentVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        $params = $this->fix['params'] ?? [];
        if (
            $node instanceof Node\Stmt\Class_ && isset($params['class'])
            && $node->name
            && $node->name->toString() === $params['class']
        ) {
            $comment = $params['comment'] ?? null;
            if ($comment) {
                $node->setAttribute(
                    'comments',
                    [new Comment($comment)]
                );
            }
        }
        return null;
    }
}
