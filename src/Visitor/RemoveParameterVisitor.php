<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function array_filter;
use function array_values;
use function is_array;

class RemoveParameterVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(mixed $fix)
    {
        $this->fix = is_array($fix) ? $fix : [];
    }

    /**
     * @param array $nodes
     * @return array
     */
    public function beforeTraverse(array $nodes): array
    {
        return $nodes;
    }

    public function enterNode(Node $node)
    {
        $params          = $this->fix['params'] ?? [];
        $targetClass     = $params['class'] ?? null;
        $targetMethod    = $params['method'] ?? null;
        $targetParameter = $params['parameter'] ?? null;

        if (! $targetClass || ! $targetMethod || ! $targetParameter) {
            return null;
        }
        // Unterstützt Klassen, Interfaces und Enums
        if (
            (
                $node instanceof Node\Stmt\Class_
                || $node instanceof Node\Stmt\Interface_
                || $node instanceof Node\Stmt\Enum_
            )
            && $node->name
            && $node->name->toString() === $targetClass
        ) {
            foreach ($node->getMethods() as $method) {
                if ($method->name->toString() === $targetMethod) {
                    // Filtere den Parameter aus
                    $method->params = array_values(
                        array_filter(
                            $method->params,
                            fn($param) => $param->var->name !== $targetParameter
                        )
                    );
                }
            }
        }
        return null;
    }
}
