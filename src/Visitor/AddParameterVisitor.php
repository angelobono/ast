<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

use function array_key_exists;
use function is_null;

class AddParameterVisitor extends NodeVisitorAbstract
{
    private string $className;
    private string $methodName;
    private array $paramData;

    public function __construct(array $data)
    {
        $this->className  = $data['class'] ?? '';
        $this->methodName = $data['method'] ?? '';
        $this->paramData  = $data['parameter'] ?? [];
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Class_
            && $node->name->toString() === $this->className
        ) {
            foreach ($node->stmts as $stmt) {
                if (
                    $stmt instanceof ClassMethod
                    && $stmt->name->toString() === $this->methodName
                ) {
                    // Prüfe, ob Parameter schon existiert
                    foreach ($stmt->params as $param) {
                        if ($param->var->name === $this->paramData['name']) {
                            return null;
                        }
                    }
                    $type    = isset($this->paramData['type']) ? new Identifier(
                        $this->paramData['type']
                    ) : null;
                    $default = null;
                    if (array_key_exists('default', $this->paramData)) {
                        $default = is_null($this->paramData['default'])
                            ? null
                            : new Expr\ConstFetch(
                                new Node\Name($this->paramData['default'])
                            );
                    }
                    $stmt->params[] = new Param(
                        new Node\Expr\Variable($this->paramData['name']),
                        $default,
                        $type
                    );
                }
            }
        }
        return null;
    }
}
