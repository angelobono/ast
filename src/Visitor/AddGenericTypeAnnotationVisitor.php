<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Param;
use PhpParser\NodeVisitorAbstract;

use function rtrim;

class AddGenericTypeAnnotationVisitor extends NodeVisitorAbstract
{
    private string $parameterName;
    private string $annotation;

    public function __construct(string $parameterName, string $annotation)
    {
        $this->parameterName = $parameterName;
        $this->annotation    = $annotation;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Param
            && $node->var->name === $this->parameterName
        ) {
            $doc    = $node->getDocComment();
            $newDoc = ($doc ? rtrim($doc->getText(), "\n") . "\n" : "")
                . $this->annotation;
            $node->setDocComment(new Comment\Doc($newDoc));
        }
        return null;
    }
}
