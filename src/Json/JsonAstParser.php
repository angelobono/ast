<?php

declare(strict_types=1);

namespace Bono\AST\Json;

use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\ParserFactory;
use Throwable;

use function array_is_list;
use function array_merge;
use function count;
use function error_log;
use function gettype;
use function is_array;
use function is_numeric;
use function is_string;
use function ltrim;
use function preg_match;
use function trim;

/**
 * JsonAstParser
 * - Parst das projektinterne JSON-AST-Format in PhpParser-Nodes
 * - Separiert JSON-spezifische Logik vom Adapter
 */
class JsonAstParser
{
    /**
     * @param array<string, mixed>|list<mixed> $json
     * @return array<int, object>
     */
    public function parseArray(array $json): array
    {
        $nodes = [];
        // Liste von Regeln
        if (array_is_list($json)) {
            foreach ($json as $entry) {
                if (
                    is_array($entry) && isset($entry['children'])
                    && is_array(
                        $entry['children']
                    )
                ) {
                    if (
                        count($entry['children']) === 1
                        && isset($entry['children'][0])
                        && is_array($entry['children'][0])
                        && ($entry['children'][0]['type'] ?? null) === 'file'
                        && isset($entry['children'][0]['children'])
                        && is_array($entry['children'][0]['children'])
                    ) {
                        $nodes = array_merge(
                            $nodes,
                            $this->parseChildren(
                                $entry['children'][0]['children']
                            )
                        );
                    } else {
                        $nodes = array_merge(
                            $nodes,
                            $this->parseChildren($entry['children'])
                        );
                    }
                }
            }
            return $nodes;
        }

        // Einzelne Regel
        if (isset($json['children']) && is_array($json['children'])) {
            if (
                count($json['children']) === 1
                && isset($json['children'][0])
                && is_array($json['children'][0])
                && ($json['children'][0]['type'] ?? null) === 'file'
                && isset($json['children'][0]['children'])
                && is_array($json['children'][0]['children'])
            ) {
                $nodes = $this->parseChildren($json['children'][0]['children']);
            } else {
                $nodes = $this->parseChildren($json['children']);
            }
            return $nodes;
        }

        if (isset($json['rules']) && is_array($json['rules'])) {
            foreach ($json['rules'] as $rule) {
                if (
                    isset($rule['children']['children'])
                    && is_array(
                        $rule['children']['children']
                    )
                ) {
                    $nodes = array_merge(
                        $nodes,
                        $this->parseChildren($rule['children']['children'])
                    );
                }
            }
        }

        return $nodes;
    }

    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<int, object>
     */
    public function parseChildren(array $children): array
    {
        $result = [];
        foreach ($children as $child) {
            if (! is_array($child) || ! isset($child['type'])) {
                continue;
            }
            switch ($child['type']) {
                case 'class':
                    $classChildren = [];
                    if (
                        isset($child['children'])
                        && is_array(
                            $child['children']
                        )
                    ) {
                        $classChildren = $this->parseChildren(
                            $child['children']
                        );
                    }
                    $result[] = new Class_(
                        $child['name'] ?? '',
                        [
                            'stmts'   => $classChildren,
                            'extends' => isset($child['extends']) ? new Name(
                                $child['extends']
                            ) : null,
                        ]
                    );
                    break;

                case 'add_method':
                case 'method':
                    $methodChildren = [];
                    if (
                        isset($child['children'])
                        && is_array(
                            $child['children']
                        )
                    ) {
                        $methodChildren = $this->parseChildren(
                            $child['children']
                        );
                    }
                    $params = [];
                    if (isset($child['params']) && is_array($child['params'])) {
                        foreach ($child['params'] as $param) {
                            if (! is_array($param) || ! isset($param['name'])) {
                                continue;
                            }
                            $params[] = new Param(
                                new Variable($param['name']),
                                null,
                                isset($param['var_type']) ? new Name(
                                    ltrim($param['var_type'], '\\')
                                ) : null
                            );
                        }
                    }
                    $flags = Class_::MODIFIER_PUBLIC;
                    if (isset($child['visibility'])) {
                        $flags = match ($child['visibility']) {
                            'protected' => Class_::MODIFIER_PROTECTED,
                            'private' => Class_::MODIFIER_PRIVATE,
                            default => Class_::MODIFIER_PUBLIC,
                        };
                    }
                    $result[] = new ClassMethod(
                        $child['name'] ?? '',
                        [
                            'flags'  => $flags,
                            'params' => $params,
                            'stmts'  => $methodChildren,
                        ]
                    );
                    break;

                case 'return':
                    $expr = null;
                    if (isset($child['value'])) {
                        $expr = $this->parseExpr($child['value']);
                    }
                    $result[] = new Return_($expr);
                    break;

                case 'assertEquals':
                    $expected = $this->parseExpr($child['expected'] ?? null);
                    $actual   = $this->parseExpr($child['actual'] ?? null);
                    $result[] = new Expression(
                        new MethodCall(
                            new Variable('this'),
                            new Identifier('assertEquals'),
                            [
                                new Arg($expected),
                                new Arg($actual),
                            ]
                        )
                    );
                    break;

                case 'assertThrows':
                    $exception = $this->parseExpr($child['class'] ?? null);
                    $result[]  = new Expression(
                        new MethodCall(
                            new Variable('this'),
                            new Identifier('expectException'),
                            [
                                new Arg($exception),
                            ]
                        )
                    );
                    break;

                default:
                    // Unbekannt: ignorieren
                    break;
            }
        }
        return $result;
    }

    /**
     * @param mixed $expr
     */
    private function parseExpr($expr)
    {
        if (is_numeric($expr)) {
            return new LNumber((int) $expr);
        }
        if (is_string($expr)) {
            if (preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $expr)) {
                return new Variable(ltrim($expr, '$'));
            }
            // Einfache Stringliteral-Erkennung ("..." oder '...')
            if (preg_match('/^["\'].*["\']$/', $expr)) {
                return new String_(trim($expr, "\"'"));
            }
            try {
                $parserFactory = new ParserFactory();
                $parser        = $parserFactory->createForHostVersion();
                $stmts         = $parser->parse('<?php return ' . $expr . ';');
                if (
                    is_array($stmts) && isset($stmts[0])
                    && $stmts[0] instanceof Return_
                    && $stmts[0]->expr !== null
                ) {
                    return $stmts[0]->expr;
                }
            } catch (Throwable $e) {
                error_log(
                    '[JsonAstParser] parseExpr Parse-Fehler für "' . $expr
                    . '": ' . $e->getMessage()
                );
            }
            return new String_($expr);
        }
        throw new InvalidArgumentException(
            'Unsupported expression type: ' . gettype($expr)
        );
    }
}
