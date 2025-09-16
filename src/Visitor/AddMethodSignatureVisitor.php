<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

use function is_array;
use function is_string;
use function preg_match;
use function rtrim;
use function trim;

class AddMethodSignatureVisitor extends NodeVisitorAbstract
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function enterNode(Node $node)
    {
        if (! $node instanceof Interface_) {
            return null;
        }
        $params     = $this->data['params'] ?? $this->data;
        $targetName = $params['class'] ?? $params['interface'] ?? null;

        if (
            $targetName && $node->name
            && $node->name->toString() !== $targetName
        ) {
            return null;
        }
        // code-basierte Signatur?
        $code = $params['code'] ?? null;

        if (is_string($code) && trim($code) !== '') {
            $method = $this->parseInterfaceMethodSignature($code);

            if ($method) {
                foreach ($node->getMethods() as $m) {
                    if ($m->name->toString() === $method->name->toString()) {
                        return null;
                    }
                }
                $node->stmts[] = $method;
            }
            return null;
        }

        // Strukturierte Felder
        $methodName   = $params['name'] ?? 'newMethod';
        $methodParams = $params['params'] ?? [];
        $returnType   = $params['returnType'] ?? ($params['return_type'] ?? null);

        // Duplikate vermeiden
        foreach ($node->getMethods() as $method) {
            if ($method->name->toString() === $methodName) {
                return null;
            }
        }

        $paramNodes = [];
        foreach ($methodParams as $p) {
            if (! is_array($p) || ! isset($p['name'])) {
                continue;
            }
            $type = null;
            if (isset($p['type'])) {
                $type = is_string($p['type']) ? new Name($p['type'])
                    : $p['type'];
            } elseif (isset($p['var_type'])) {
                $type = is_string($p['var_type']) ? new Name($p['var_type'])
                    : $p['var_type'];
            }
            $paramNodes[] = new Param(
                new Node\Expr\Variable($p['name']),
                null,
                $type
            );
        }

        $methodNode    = new ClassMethod(
            new Identifier($methodName),
            [
                'flags'      => Node\Stmt\Class_::MODIFIER_PUBLIC,
                'params'     => $paramNodes,
                'returnType' => $returnType ? new Identifier($returnType)
                    : null,
                'stmts'      => null, // Keine Body für Interface-Methoden
            ]
        );
        $node->stmts[] = $methodNode;

        return null;
    }

    private function parseInterfaceMethodSignature(string $code): ?ClassMethod
    {
        $code = trim($code);
        // einfache Validierung
        if (
            ! preg_match(
                '/^(public|protected|private)?\s*function\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\(/',
                $code
            )
        ) {
            return null;
        }
        // Dummy-Interface um Signatur zu parsen
        $dummy  = "<?php interface _D { " . rtrim($code, ';') . "; }";
        $parser = (new ParserFactory())->createForHostVersion();
        try {
            $stmts = $parser->parse($dummy);
        } catch (Error) {
            return null;
        }
        if (
            ! $stmts || ! isset($stmts[0])
            || ! $stmts[0] instanceof Node\Stmt\Interface_
        ) {
            return null;
        }
        foreach ($stmts[0]->stmts as $s) {
            if ($s instanceof ClassMethod) {
                // Signatur sicherstellen: kein Body
                $s->stmts = null;
                return $s;
            }
        }
        return null;
    }
}
