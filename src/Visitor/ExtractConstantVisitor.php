<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\NodeVisitorAbstract;

class ExtractConstantVisitor extends NodeVisitorAbstract
{
    private string $constantName;
    private ?ClassConst $extractedConst = null;

    public function __construct(array $data)
    {
        $this->constantName = $data['constantName'];
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            foreach ($node->stmts as $key => $stmt) {
                if ($stmt instanceof ClassConst) {
                    foreach ($stmt->consts as $constKey => $const) {
                        if ($const->name->toString() === $this->constantName) {
                            // Extrahiere die Konstante
                            $this->extractedConst = new ClassConst([
                                new Const_($const->name, $const->value),
                            ], $stmt->flags, $stmt->attrGroups);
                            // Entferne die Konstante aus der Klasse
                            unset($node->stmts[$key]);
                            break 2;
                        }
                    }
                }
            }
        }
        return null;
    }

    public function afterTraverse(array $nodes)
    {
        // Hier könnte man die extrahierte Konstante an anderer Stelle einfügen
        // oder sie für weitere Verarbeitung bereitstellen.
        return null;
    }

    public function getExtractedConst(): ?ClassConst
    {
        return $this->extractedConst;
    }
}
