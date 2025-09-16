<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

use function array_filter;
use function array_values;
use function count;
use function explode;
use function implode;
use function strpos;

class RemoveGenericTypeAnnotationVisitor extends NodeVisitorAbstract
{
    private string $parameterName;
    private string $annotationPattern;

    public function __construct(
        string $parameterName,
        string $annotationPattern
    ) {
        $this->parameterName     = $parameterName;
        $this->annotationPattern = $annotationPattern;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof ClassMethod) {
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
                    $lines    = explode("\n", $doc->getText());
                    $filtered = array_values(
                        array_filter(
                            $lines,
                            fn($line) => strpos($line, $this->annotationPattern)
                                === false
                        )
                    );
                    if (count($filtered) !== count($lines)) {
                        $node->setDocComment(new Doc(implode("\n", $filtered)));
                    }
                }
            }
        }
        return null;
    }
}
