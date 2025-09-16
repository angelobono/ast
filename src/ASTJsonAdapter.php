<?php

declare(strict_types=1);

namespace Bono\AST;

use Bono\AST\Json\JsonAstParser;
use ColinODell\Json5\Json5;
use ColinODell\Json5\Json5Decoder;
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

use function class_exists;
use function error_log;
use function gettype;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function ltrim;
use function preg_match;
use function preg_replace;
use function trim;

use const JSON_THROW_ON_ERROR;

class ASTJsonAdapter
{
    /**
     * Erzeugt AST-Nodes aus einem JSON-String (Convenience für fromArray).
     *
     * @return array<int, object>
     */
    public static function fromJson(string $json): array
    {
        $clean = self::sanitizeJson($json);
        $data  = null;
        try {
            /** @var array<string,mixed>|list<mixed> $decoded */
            $decoded = json_decode($clean, true, 512, JSON_THROW_ON_ERROR);
            $data    = $decoded;
        } catch (Throwable $e) {
            // Fallback: JSON5, wenn verfügbar
            try {
                if (class_exists(Json5Decoder::class)) {
                    /** @var array<string,mixed>|list<mixed> $decoded5 */
                    $data = Json5Decoder::decode($clean, true);
                } elseif (class_exists('ColinODell\\Json5\\Json5')) {
                    /** @var array<string,mixed>|list<mixed> $decoded5 */
                    $data = Json5::decode($clean, true);
                } else {
                    error_log(
                        '[ASTJsonAdapter] Invalid JSON and JSON5 decoder not available: '
                        . $e->getMessage()
                    );
                    return [];
                }
            } catch (Throwable $e2) {
                error_log(
                    '[ASTJsonAdapter] Invalid JSON/JSON5: ' . $e2->getMessage()
                );
                return [];
            }
        }
        if (! is_array($data)) {
            return [];
        }
        return self::fromArray($data);
    }

    /**
     * Entfernt Code-Fences und trimmt Eingaben.
     */
    private static function sanitizeJson(string $input): string
    {
        $s = $input;
        // Entferne ```json / ``` Fences
        $s = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $s) ?? $s;
        return trim($s);
    }

    /**
     * @param array<string, mixed> $json
     * @return array<int, object>
     */
    public static function fromArray(array $json): array
    {
        return (new JsonAstParser())->parseArray($json);
    }

    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<int, object>
     */
    private static function parseAstChildren(array $children): array
    {
        $result = [];
        foreach ($children as $child) {
            // Nur Arrays mit 'type' verarbeiten
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
                        $classChildren = self::parseAstChildren(
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
                        $methodChildren = self::parseAstChildren(
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
                        $expr = self::parseExpr($child['value']);
                    }
                    $result[] = new Return_($expr);
                    break;
                case 'assertEquals':
                    $expected = self::parseExpr($child['expected'] ?? null);
                    $actual   = self::parseExpr($child['actual'] ?? null);
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
                    $exception = self::parseExpr($child['class'] ?? null);
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
                // Hier können weitere AST-Knoten ergänzt werden
                default:
                    // Unbekannte Typen ignorieren
                    break;
            }
        }
        return $result;
    }

    /**
     * Wandelt einen einfachen Ausdrucks-String in einen PhpParser-Expr um.
     */
    private static function parseExpr($expr)
    {
        if (is_numeric($expr)) {
            return new LNumber((int) $expr);
        }
        if (is_string($expr)) {
            if (preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $expr)) {
                return new Variable(ltrim($expr, '$'));
            }
            if (preg_match('/^["\'].*["\']$/', $expr)) {
                return new String_(trim($expr, "\"'"));
            }
            try {
                $parserFactory = new ParserFactory();
                $parser        = $parserFactory->createForHostVersion();
                $stmts         = $parser->parse('<?php return ' . $expr . ';');
                if (
                    is_array($stmts)
                    && isset($stmts[0])
                    && $stmts[0] instanceof Return_
                    && $stmts[0]->expr !== null
                ) {
                    return $stmts[0]->expr;
                }
            } catch (Throwable $e) {
                error_log(
                    '[ASTJsonAdapter] parseExpr Parse-Fehler für "'
                    . $expr
                    . '": '
                    . $e->getMessage()
                );
            }
            // Fallback: als String
            return new String_($expr);
        }
        throw new InvalidArgumentException(
            'Unsupported expression type: ' . gettype($expr)
        );
    }
}
