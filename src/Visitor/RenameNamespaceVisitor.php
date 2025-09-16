<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

class RenameNamespaceVisitor extends NodeVisitorAbstract
{
    private string $oldNamespace;
    private string $newNamespace;

    public function __construct(array $data)
    {
        $this->oldNamespace = $data['old_namespace'] ?? '';
        $this->newNamespace = $data['new_namespace'] ?? '';
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Namespace_ && $node->name
            && $node->name->toString() === $this->oldNamespace
        ) {
            $node->name = new Name($this->newNamespace);
        }
        return null;
    }
}
