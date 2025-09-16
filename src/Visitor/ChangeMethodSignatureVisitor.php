<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function is_array;

class ChangeMethodSignatureVisitor extends NodeVisitorAbstract
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
            $node instanceof Node\Stmt\ClassMethod
            && isset($params['method'])
            && $node->name->toString() === $params['method']
        ) {
            if (
                isset($params['parameters'])
                && is_array($params['parameters'])
            ) {
                $paramsArr = [];
                foreach ($params['parameters'] as $param) {
                    $type        = isset($param['type']) ? new Node\Identifier(
                        $param['type']
                    ) : null;
                    $paramsArr[] = new Node\Param(
                        new Node\Expr\Variable($param['name']),
                        null,
                        $type
                    );
                }
                $node->params = $paramsArr;
            }
        }
        return null;
    }
}
