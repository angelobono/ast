<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function is_numeric;

class ChangeConstantValueVisitor extends NodeVisitorAbstract
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
            $node instanceof Node\Stmt\Class_
            && isset($params['class'], $params['constant'], $params['value'])
            && $node->name
            && $node->name->toString() === $params['class']
        ) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\ClassConst) {
                    foreach ($stmt->consts as $const) {
                        if (
                            $const->name->toString()
                            === $params['constant']
                        ) {
                            $const->value = is_numeric($params['value'])
                                ? new Node\Scalar\LNumber($params['value'])
                                : new Node\Scalar\String_(
                                    (string) $params['value']
                                );
                        }
                    }
                }
            }
        }
        return null;
    }
}
