<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\NodeVisitorAbstract;
use Webmozart\Assert\Assert;

use function in_array;
use function is_string;

class ChangeParameterTypeVisitor extends NodeVisitorAbstract
{
    private string $parameterName;
    private $newType;

    public function __construct(array $data)
    {
        Assert::string($data['parameterName']);
        Assert::string($data['newType']);
        $this->parameterName = $data['parameterName'];
        $this->newType       = $data['newType'];
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Param
            && $node->var->name === $this->parameterName
        ) {
            if (is_string($this->newType)) {
                // Unterstützt skalare Typen und Klassennamen
                $node->type = in_array(
                    $this->newType,
                    [
                        'int',
                        'string',
                        'float',
                        'bool',
                        'array',
                        'callable',
                        'iterable',
                        'object',
                        'mixed',
                        'void',
                        'self',
                        'parent',
                    ]
                )
                    ? new Identifier($this->newType)
                    : new Name($this->newType);
            } elseif ($this->newType instanceof Node) {
                $node->type = $this->newType;
            }
        }
        return null;
    }
}
