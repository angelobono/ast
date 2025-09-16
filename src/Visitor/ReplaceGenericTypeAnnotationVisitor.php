<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

use function explode;
use function implode;
use function preg_match;
use function preg_quote;

class ReplaceGenericTypeAnnotationVisitor extends NodeVisitorAbstract
{
    private string $parameterName;
    private string $oldPattern;
    private string $newAnnotation;

    public function __construct(
        string $parameterName,
        string $oldPattern,
        string $newAnnotation
    ) {
        $this->parameterName = $parameterName;
        $this->oldPattern    = $oldPattern;
        $this->newAnnotation = $newAnnotation;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassMethod) {
            // Prüfe, ob der Parameter existiert
            $paramFound = false;
            foreach ($node->params as $param) {
                if ($param->var->name === $this->parameterName) {
                    $paramFound = true;
                    break;
                }
            }
            if ($paramFound) {
                $doc = $node->getDocComment();
                if ($doc) {
                    $lines = explode("\n", $doc->getText());
                    foreach ($lines as &$line) {
                        if (
                            preg_match(
                                '/template\\s+' . preg_quote($this->oldPattern, '/')
                                . '\\b/i',
                                $line
                            )
                        ) {
                            $line = ' * ' . $this->newAnnotation;
                        }
                    }
                    $newDoc = new Doc(implode("\n", $lines));
                    $node->setDocComment($newDoc);
                    $node->setAttribute('comments', [$newDoc]);
                }
            }
        }
        return null;
    }
}
