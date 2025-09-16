<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

use function array_pop;
use function explode;
use function implode;

class AddAnnotationVisitor extends NodeVisitorAbstract
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
                        $this->addAnnotation($stmt);
                    }
                }
            } else {
                $this->addAnnotation($node);
            }
        }
        return null;
    }

    private function addAnnotation($node)
    {
        $doc   = $node->getDocComment();
        $lines = $doc ? explode("\n", $doc->getText()) : ["/**", " */"];
        // Füge Annotation vor dem abschließenden */ ein
        $last    = array_pop($lines);
        $lines[] = " * {$this->annotation}";
        $lines[] = $last;
        $newDoc  = implode("\n", $lines);
        $node->setDocComment(new Doc($newDoc));
    }
}
