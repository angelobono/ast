<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class ExtractTraitVisitor extends NodeVisitorAbstract
{
    private array $fix;

    public function __construct(array $fix)
    {
        $this->fix = $fix;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Stmt\Namespace_
            && isset($this->fix['trait'], $this->fix['code'])
        ) {
            $parser    = new ParserFactory();
            $phpParser = $parser->createForHostVersion();
            // Code als Trait-Body in Dummy-Trait einbetten
            $dummyTraitCode = "<?php trait Dummy { {$this->fix['code']} }";
            $dummyAst       = $phpParser->parse($dummyTraitCode);
            $traitBody      = [];
            foreach ($dummyAst as $dummyNode) {
                if ($dummyNode instanceof Node\Stmt\Trait_) {
                    $traitBody = $dummyNode->stmts;
                    break;
                }
            }
            $traitNode     = new Node\Stmt\Trait_(
                $this->fix['trait'],
                ['stmts' => $traitBody]
            );
            $node->stmts[] = $traitNode;
        }

        return null;
    }
}
