<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\NodeVisitorAbstract;

use function array_map;

class AddEnumVisitor extends NodeVisitorAbstract
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function beforeTraverse(array $nodes): array
    {
        $params     = $this->data['params'] ?? [];
        $enumName   = $params['name'] ?? 'NewEnum';
        $implements = $params['implements'] ?? [];
        // Check if enum already exists
        foreach ($nodes as $node) {
            if (
                $node instanceof Enum_ && $node->name
                && $node->name->toString() === $enumName
            ) {
                // Enum already exists, do not add another
                return $nodes;
            }
        }
        $enumNode = new Enum_(
            new Identifier($enumName),
            [
                'implements' => array_map(fn($i) => new Name($i), $implements),
                'stmts'      => [],
            ]
        );
        $nodes[]  = $enumNode;
        return $nodes;
    }
}
