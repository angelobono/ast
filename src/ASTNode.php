<?php

declare(strict_types=1);

namespace Bono\AST;

class ASTNode
{
    private string $id;
    private string $type; // z.B. planner, developer, tester
    private string $content;
    /** @var ASTNode[] */
    private array $children = [];

    public function __construct(string $id, string $type, string $content)
    {
        $this->id      = $id;
        $this->type    = $type;
        $this->content = $content;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /** @return ASTNode[] */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addMethod(string $name, string $body): void
    {
        $methodNode = new ASTNode($this->id . '::' . $name, 'method', $body);
        $this->addChild($methodNode);
    }

    public function addChild(ASTNode $child): void
    {
        $this->children[] = $child;
    }
}
