<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function is_numeric;
use function str_contains;

class AddConstantVisitor extends NodeVisitorAbstract
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
            (
                $node instanceof Node\Stmt\Class_
                || $node instanceof Node\Stmt\Interface_
                || $node instanceof Node\Stmt\Enum_
            )
            && isset($params['class'])
            && $node->name
            && $node->name->toString() === $params['class']
        ) {
            $constName  = $params['constant'] ?? null;
            $constValue = $params['value'] ?? null;
            if ($constName !== null) {
                // Wert korrekt typisieren: int -> LNumber, float -> DNumber, sonst String_
                $valueNode = null;
                if (is_numeric($constValue)) {
                    $numericString = (string) $constValue;
                    if (
                        str_contains($numericString, '.')
                        || str_contains(
                            $numericString,
                            'e'
                        )
                        || str_contains($numericString, 'E')
                    ) {
                        $valueNode = new Node\Scalar\DNumber(
                            (float) $constValue
                        );
                    } else {
                        // explizit casten, da LNumber einen int erwartet
                        $valueNode = new Node\Scalar\LNumber((int) $constValue);
                    }
                } else {
                    $valueNode = new Node\Scalar\String_((string) $constValue);
                }
                $constNode     = new Node\Stmt\ClassConst([
                    new Node\Const_($constName, $valueNode),
                ]);
                $node->stmts[] = $constNode;
            }
        }
        return null;
    }
}
