<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class InlineVariableVisitor extends NodeVisitorAbstract
{
    private string $variableName;
    private $variableValue;
    private bool $inlined = false;

    public function __construct(array $data)
    {
        $this->variableName = $data['variableName'];
    }

    public function enterNode(Node $node)
    {
        // Suche die Zuweisung zur Variable
        if ($node instanceof Expression && $node->expr instanceof Assign) {
            $assign = $node->expr;
            if (
                $assign->var instanceof Variable
                && $assign->var->name === $this->variableName
            ) {
                $this->variableValue = $assign->expr;
                // Entferne die Deklaration (durch Rückgabe null)
                $this->inlined = true;
                return NodeTraverser::REMOVE_NODE;
            }
        }
        // Ersetze alle Vorkommen der Variable durch ihren Wert
        if (
            $this->inlined && $node instanceof Variable
            && $node->name === $this->variableName
            && $this->variableValue !== null
        ) {
            return $this->variableValue;
        }
        return null;
    }
}
