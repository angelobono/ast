<?php

declare(strict_types=1);

namespace Bono\AST;

/**
 * @template TChild
 */
class ASTMethodNode
{
    public string $name;

    public string $visibility;

    /** @var array<int, mixed> */
    public array $params;

    /** @var TChild[] */
    public array $children;

    /**
     * @param array<int, mixed> $params
     * @param TChild[]          $children
     */
    public function __construct(
        string $name,
        string $visibility = 'public',
        array $params = [],
        array $children = []
    ) {
        $this->name       = $name;
        $this->visibility = $visibility;
        $this->params     = $params;
        $this->children   = $children;
    }
}
