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
use function is_string;
use function ltrim;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function substr;
use function trim;

class ReplaceNodeVisitor extends NodeVisitorAbstract
{
    private string $className;
    private string $methodName;
    private array $oldStmtNodes;
    private array $newStmtNodes;
    private ?string $rawOld = null;
    private ?string $rawNew = null;
    private PrettyPrinter $printer;

    public function __construct(
        $fixOrData,
        $oldStmtNodes = null,
        $newStmtNodes = null
    ) {
        if (
            is_array($fixOrData) && is_array($oldStmtNodes)
            && is_array(
                $newStmtNodes
            )
        ) {
            $params             = $fixOrData['params'] ?? [];
            $this->className    = $params['class'] ?? '';
            $this->methodName   = $params['method'] ?? '';
            $this->oldStmtNodes = $oldStmtNodes;
            $this->newStmtNodes = $newStmtNodes;
            // old/code bevorzugt aus params, sonst Top-Level
            $rawOld       = $params['old'] ?? ($fixOrData['old'] ?? null);
            $rawNew       = $params['code'] ?? ($fixOrData['code'] ?? null);
            $this->rawOld = is_string($rawOld) ? $rawOld : null;
            $this->rawNew = is_string($rawNew) ? $rawNew : null;
        } elseif (is_array($fixOrData)) {
            $params             = $fixOrData['params'] ?? [];
            $this->className    = $params['class'] ?? '';
            $this->methodName   = $params['method'] ?? '';
            $old                = $params['old'] ?? '';
            $new                = $params['code'] ?? '';
            $this->rawOld       = is_string($old) ? $old : null;
            $this->rawNew       = is_string($new) ? $new : null;
            $this->oldStmtNodes = $this->parseStmts($old);
            $this->newStmtNodes = $this->parseStmts($new);
        } else {
            throw new InvalidArgumentException(
                'Invalid arguments for ReplaceNodeVisitor'
            );
        }
        $this->printer = new PrettyPrinter();
    }

    private function parseStmts(string $snippet): array
    {
        $snippet = trim($snippet);
        if ($snippet === '') {
            return [];
        }
        // Dollar aus JSON ent-escapen
        $snippet = str_replace('\\$', '$', $snippet);
        $parser  = new ParserFactory()->createForHostVersion();
        $code    = str_starts_with(ltrim($snippet), '<?php')
            ? $snippet
            : "<?php\n" . $snippet;
        $ast     = $parser->parse($code);
        return array_filter($ast ?? [], fn($n) => $n instanceof Node\Stmt);
    }

    public function enterNode(Node $node)
    {
        if (
            ! $node instanceof Node\Stmt\Class_
            || $node->name?->toString() !== $this->className
        ) {
            return null;
        }
        foreach ($node->stmts as $stmt) {
            if (
                ! $stmt instanceof Node\Stmt\ClassMethod
                || $stmt->name->toString() !== $this->methodName
            ) {
                continue;
            }
            if ($stmt->stmts === null) {
                continue;
            }

            $stmts    = $stmt->stmts;
            $countOld = count($this->oldStmtNodes);

            // 1) AST-basierter Slice-Vergleich
            if ($countOld > 0) {
                $oldPrinted = array_map(
                    fn($n) => $this->normalizePrint(
                        $this->printer->prettyPrint([$n])
                    ),
                    $this->oldStmtNodes
                );
                for ($i = 0; $i <= count($stmts) - $countOld; $i++) {
                    $slicePrinted = [];
                    for ($k = 0; $k < $countOld; $k++) {
                        $slicePrinted[] = $this->normalizePrint(
                            $this->printer->prettyPrint([$stmts[$i + $k]])
                        );
                    }
                    $match = true;
                    for ($k = 0; $k < $countOld; $k++) {
                        if (
                            $slicePrinted[$k] !== $oldPrinted[$k]
                            && ! $this->nodesEqual(
                                $stmts[$i + $k],
                                $this->oldStmtNodes[$k]
                            )
                        ) {
                            $match = false;
                            break;
                        }
                    }
                    if ($match) {
                        array_splice(
                            $stmts,
                            $i,
                            $countOld,
                            $this->newStmtNodes
                        );
                        $stmt->stmts = $stmts;
                        return null;
                    }
                }
            }

            // 2) Fallback: einfacher Textersatz old -> code im Methodenrumpf
            if ($this->rawOld && $this->rawNew) {
                $bodyCode    = $this->printer->prettyPrint($stmt->stmts);
                $quoted      = preg_quote(trim($this->rawOld), '/');
                $flex        = preg_replace('/\\s+/', '\\s*', $quoted);
                $pattern     = '/' . $flex . '/s';
                $newBodyCode = preg_replace(
                    $pattern,
                    trim($this->rawNew),
                    $bodyCode,
                    1
                );
                if (is_string($newBodyCode) && $newBodyCode !== $bodyCode) {
                    $dummy  = "<?php class _D { function __tmp__() { "
                        . $newBodyCode . " } }";
                    $parser = (new ParserFactory())->createForHostVersion();
                    $nodes  = $parser->parse($dummy) ?? [];
                    foreach ($nodes as $n) {
                        if ($n instanceof Node\Stmt\Class_) {
                            foreach ($n->stmts as $s) {
                                if (
                                    $s instanceof Node\Stmt\ClassMethod
                                    && $s->name->toString() === '__tmp__'
                                ) {
                                    $stmt->stmts = $s->stmts ?? [];
                                    return null;
                                }
                            }
                        }
                    }
                }
            }
        }
        return null;
    }

    private function normalizePrint(string $s): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        if (str_ends_with($s, ';')) {
            $s = rtrim(substr($s, 0, -1));
        }
        return $s;
    }

    private function nodesEqual(Node $a, Node $b): bool
    {
        if ($a::class !== $b::class) {
            return false;
        }
        foreach ($a->getSubNodeNames() as $name) {
            $va = $a->$name;
            $vb = $b->$name;
            if ($va instanceof Node && $vb instanceof Node) {
                if (! $this->nodesEqual($va, $vb)) {
                    return false;
                }
                continue;
            }
            if (is_array($va) && is_array($vb)) {
                if (count($va) !== count($vb)) {
                    return false;
                }
                for ($i = 0, $l = count($va); $i < $l; $i++) {
                    $ea = $va[$i];
                    $eb = $vb[$i];
                    if ($ea instanceof Node && $eb instanceof Node) {
                        if (! $this->nodesEqual($ea, $eb)) {
                            return false;
                        }
                    } elseif ($ea !== $eb) {
                        return false;
                    }
                }
                continue;
            }
            if ($va !== $vb) {
                return false;
            }
        }
        return true;
    }
}
