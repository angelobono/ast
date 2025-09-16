<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class ExtractMethodVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Stmt\Class_
            && isset($this->fix['class'], $this->fix['method'], $this->fix['extract'], $this->fix['code'])
            && $node->name
            && $node->name->toString() === $this->fix['class']
        ) {
            // Extrahiere Code aus Methode und erstelle neue Methode
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof Node\Stmt\ClassMethod
                    && $stmt->name->toString() === $this->fix['method']
                ) {
                    $stmts          = $stmt->stmts ?? [];
                    $extractCode    = $this->fix['code'];
                    $parser         = new ParserFactory();
                    $phpParser      = $parser->createForHostVersion();
                    $stmtsToExtract = $phpParser->parse("<?php {$extractCode}");
                    $newMethod      = new Node\Stmt\ClassMethod(
                        $this->fix['extract'],
                        [
                            'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                            'stmts' => $stmtsToExtract,
                        ]
                    );
                    $node->stmts[]  = $newMethod;
                }
            }
        }
        return null;
    }
}
