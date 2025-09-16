<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

use function error_log;
use function preg_match;
use function preg_replace;
use function str_replace;
use function trim;

class AddEnumMethodVisitor extends NodeVisitorAbstract
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function enterNode(Node $node)
    {
        $targetEnum = $this->data['params']['class'] ?? null;
        $methodCode = $this->data['params']['code'] ?? null;

        if (
            $node instanceof Enum_ && $methodCode
            && $targetEnum
            && $node->name
            && $node->name->toString() === $targetEnum
        ) {
            // Sanitize method code: remove PHP tags and trim whitespace
            $methodCodeSanitized = trim(
                preg_replace('/<\?php/', '', $methodCode)
            );
            // Unescape dollar signs (from JSON)
            $methodCodeSanitized = str_replace(
                '\\$',
                '$',
                $methodCodeSanitized
            );
            // Validate that the code looks like a method declaration
            if (
                ! preg_match(
                    '/^(public|protected|private)\s+function\s+/i',
                    $methodCodeSanitized
                )
            ) {
                error_log(
                    '[AddEnumMethodVisitor] ERROR: Method code does not look like a method declaration. Skipping. Code: '
                    . $methodCodeSanitized
                );
                return null;
            }
            $dummyEnumCode = "<?php enum _Dummy { {$methodCodeSanitized} }";
            $parser        = new ParserFactory();
            $phpParser     = $parser->createForHostVersion();
            try {
                $stmts = $phpParser->parse($dummyEnumCode);
            } catch (Error $e) {
                error_log(
                    '[AddEnumMethodVisitor] PARSE ERROR: ' . $e->getMessage()
                );
                error_log(
                    '[AddEnumMethodVisitor] Offending dummy enum code: '
                    . $dummyEnumCode
                );
                return null;
            }
            if ($stmts && isset($stmts[0]) && $stmts[0] instanceof Enum_) {
                foreach ($stmts[0]->stmts as $stmt) {
                    if ($stmt instanceof ClassMethod) {
                        // Prüfe, ob Methode schon existiert
                        $found = false;
                        foreach ($node->stmts as $i => $existing) {
                            if (
                                $existing instanceof ClassMethod
                                && $existing->name->toString()
                                === $stmt->name->toString()
                            ) {
                                $node->stmts[$i] = $stmt;
                                $found           = true;
                                break;
                            }
                        }
                        if (! $found) {
                            $node->stmts[] = $stmt;
                        }
                    }
                }
            } else {
                error_log(
                    '[AddEnumMethodVisitor] Failed to parse method code.'
                );
            }
        }
        return null;
    }
}
