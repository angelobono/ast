<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

use function array_filter;
use function explode;
use function implode;
use function strpos;

class RemoveAnnotationVisitor extends NodeVisitorAbstract
{
    private string $className;
    private ?string $methodName;
    private string $annotation;

    public function __construct(array $data)
    {
        $this->className  = $data['class'] ?? '';
        $this->methodName = $data['method'] ?? null;
        $this->annotation = $data['annotation'] ?? '';
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
                        $this->removeAnnotation($stmt);
                    }
                }
            } else {
                $this->removeAnnotation($node);
            }
        }
        return null;
    }

    private function removeAnnotation($node)
    {
        $doc = $node->getDocComment();
        if ($doc) {
            $lines  = explode("\n", $doc->getText());
            $lines  = array_filter(
                $lines,
                fn($line) => strpos($line, $this->annotation) === false
            );
            $newDoc = implode("\n", $lines);
            $node->setDocComment(new Doc($newDoc));
        }
    }
}
