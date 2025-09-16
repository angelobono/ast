<?php

declare(strict_types=1);

namespace Bono\AST\Visitor;

use InvalidArgumentException;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

use function array_filter;
use function array_map;
use function array_splice;
use function count;
use function is_array;
use function ltrim;
use function preg_replace;
use function str_starts_with;
use function trim;

class RemoveNodeVisitor extends NodeVisitorAbstract
{
    private string $className;
    private string $methodName;
    private array $oldStmtNodes;
    private bool $removeMethod;
    private PrettyPrinter $printer;

    public function __construct($fixOrData, $oldStmtNodes = null)
    {
        if (is_array($fixOrData) && is_array($oldStmtNodes)) {
            $params             = $fixOrData['params'] ?? [];
            $this->className    = $params['class'] ?? '';
            $this->methodName   = $params['method'] ?? '';
            $this->oldStmtNodes = $oldStmtNodes;
            $this->removeMethod = empty($oldStmtNodes);
        } elseif (is_array($fixOrData)) {
            $params             = $fixOrData['params'] ?? [];
            $this->className    = $params['class'] ?? '';
            $this->methodName   = $params['method'] ?? '';
            $old                = $params['old'] ?? '';
            $this->oldStmtNodes = $this->parseStmts($old);
            $this->removeMethod = empty($this->oldStmtNodes);
        } else {
            throw new InvalidArgumentException(
                'Invalid arguments for RemoveNodeVisitor'
            );
        }
        $this->printer = new PrettyPrinter();
    }

    private function parseStmts(string $snippet): array
    {
        if (! $snippet) {
            return [];
        }
        $parser = (new ParserFactory())->createForHostVersion();
        $code   = str_starts_with(ltrim($snippet), '<?php')
            ? $snippet
            : "<?php\n" . $snippet;
        $ast    = $parser->parse($code);
        return array_filter($ast ?? [], fn($n) => $n instanceof Node\Stmt);
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Stmt\Class_
            && $node->name?->toString() === $this->className
        ) {
            if ($this->removeMethod) {
                foreach ($node->stmts as $i => $stmt) {
                    if (
                        $stmt instanceof Node\Stmt\ClassMethod
                        && $stmt->name->toString() === $this->methodName
                    ) {
                        array_splice($node->stmts, $i, 1);
                        break;
                    }
                }
            } else {
                foreach ($node->stmts as $stmt) {
                    if (
                        $stmt instanceof Node\Stmt\ClassMethod
                        && $stmt->name->toString() === $this->methodName
                        && $stmt->stmts !== null
                    ) {
                        $stmts      = $stmt->stmts;
                        $countOld   = count($this->oldStmtNodes);
                        $oldPrinted = array_map(
                            fn($n) => $this->printer->prettyPrint([$n]) . ';',
                            $this->oldStmtNodes
                        );
                        $normalize  = fn(string $s) => trim(
                            preg_replace('/\s+/', ' ', $s)
                        );
                        for ($i = 0; $i <= count($stmts) - $countOld; $i++) {
                            $slicePrinted = [];
                            for ($k = 0; $k < $countOld; $k++) {
                                $slicePrinted[] = $this->printer->prettyPrint(
                                    [$stmts[$i + $k]]
                                ) . ';';
                            }
                            $match = true;
                            for ($k = 0; $k < $countOld; $k++) {
                                if (
                                    $normalize($slicePrinted[$k])
                                    !== $normalize($oldPrinted[$k])
                                ) {
                                    $match = false;
                                    break;
                                }
                            }
                            if ($match) {
                                array_splice($stmts, $i, $countOld, []);
                                $stmt->stmts = $stmts;
                                break;
                            }
                        }
                    }
                }
            }
        }
        return null;
    }
}
