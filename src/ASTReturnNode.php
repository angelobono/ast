<?php

declare(strict_types=1);

namespace Bono\AST;

/**
 * @template TValue
 */
class ASTReturnNode
{
    /** @var TValue */
    public $value;

    /**
     * @param TValue $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
