<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\NodeVisitorAbstract;

use function array_unshift;

class AddPropertyVisitor extends NodeVisitorAbstract
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function enterNode(Node $node)
    {
        $params = $this->data['params'] ?? [];
        if ($node instanceof Class_ && isset($params['name'])) {
            $type       = $params['type'] ?? null;
            $default    = $params['default'] ?? null;
            $visibility = $params['visibility'] ?? 'public';
            $flags      = match ($visibility) {
                'private' => Class_::MODIFIER_PRIVATE,
                'protected' => Class_::MODIFIER_PROTECTED,
                default => Class_::MODIFIER_PUBLIC
            };
            $prop = new Property(
                $flags,
                [
                    new PropertyProperty(
                        $params['name'],
                        $default !== null ? new ConstFetch(new Name($default))
                            : null
                    ),
                ],
                [
                    'type' => $type ? new Identifier($type) : null,
                ]
            );
            array_unshift($node->stmts, $prop);
        }
        return null;
    }
}
