<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

use function array_map;

class AddClassVisitor extends NodeVisitorAbstract
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function beforeTraverse(array $nodes): array
    {
        $params     = $this->data['params'] ?? [];
        $className  = $params['name'] ?? 'NewClass';
        $extends    = $params['extends'] ?? null;
        $implements = $params['implements'] ?? [];

        // Check if class already exists
        foreach ($nodes as $node) {
            if (
                $node instanceof Class_ && $node->name
                && $node->name->toString() === $className
            ) {
                // Class already exists, do not add another
                return $nodes;
            }
        }
        $classNode = new Class_(
            new Identifier($className),
            [
                'extends'    => $extends ? new Name($extends) : null,
                'implements' => array_map(fn($i) => new Name($i), $implements),
                'stmts'      => [],
            ]
        );
        $nodes[]   = $classNode;
        return $nodes;
    }
}
