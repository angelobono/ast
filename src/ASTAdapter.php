<?php

declare(strict_types=1);

namespace Bono\AST;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use Throwable;
use ValueError;

use function array_map;
use function html_entity_decode;
use function ltrim;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_starts_with;
use function strrpos;
use function substr;
use function trim;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * AstAdapter
 * - Brücke zwischen AST-Operationen (ASTExecutor) und Dateischreiben
 * - Zusatz: PHP-Normalisierung und Struktur-Extraktion (Klassen/Methoden)
 */
class ASTAdapter
{
    /**
     * Normalisiert PHP-Dateiinhalt: entfernt Markup, stellt <?php sicher,
     * parsed und pretty-printed, um Artefakte (z. B. trailing '>') zu
     * eliminieren. Fallback: sanitizter Inhalt, wenn Parse fehlschlägt.
     */
    public static function normalizePhp(string $content): string
    {
        $san = self::basicSanitize($content);
        try {
            $parser = new ParserFactory()->createForHostVersion();
            $ast    = $parser->parse(
                str_starts_with(ltrim($san), '<?php')
                    ? $san : "<?php\n" . $san
            );
            if (! $ast) {
                return $san;
            }
            $printer = new PrettyPrinter();
            return $printer->prettyPrintFile($ast);
        } catch (Throwable) {
            return $san;
        }
    }

    /** Basic Clean: entfernt offensichtliche UI/HTML/Codefences und sichert PHP-Tag/Fußzeile. */
    private static function basicSanitize(string $content): string
    {
        $s = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);
        $s = preg_replace('/<\/?span[^>]*>/', '', $s) ?? $s;
        $s = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $s) ?? $s;
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s) ?? $s;
        $s = trim($s);
        if (! str_starts_with(ltrim($s), '<?php')) {
            $s = "<?php\n" . $s;
        }
        $s = rtrim($s);
        // letzte Zeile, falls nur '>'
        $pos      = strrpos($s, "\n");
        $lastLine = $pos === false ? $s : substr($s, $pos + 1);
        if (preg_match('/^>+$/', trim($lastLine))) {
            $s = $pos === false ? '' : substr($s, 0, $pos);
        }
        return rtrim($s) . "\n";
    }

    /**
     * Modifiziert einen AST gemäß einer ASTOption und Daten.
     */
    public static function modify(
        array $ast,
        string|ASTOption $operation,
        array $data
    ): array {
        try {
            $option = $operation instanceof ASTOption ? $operation
                : ASTOption::from($operation);
        } catch (ValueError) {
            // Unbekannte Operation: AST unverändert zurückgeben
            return $ast;
        }

        $traverser = new NodeTraverser();

        switch ($option) {
            case ASTOption::ADD_METHOD:
                if (($data['targetType'] ?? null) === 'interface') {
                    $traverser->addVisitor(
                        new Visitor\AddMethodSignatureVisitor($data)
                    );
                } else {
                    $traverser->addVisitor(new Visitor\AddMethodVisitor($data));
                }
                break;
            case ASTOption::ADD_METHOD_SIGNATURE:
                $traverser->addVisitor(
                    new Visitor\AddMethodSignatureVisitor($data)
                );
                break;
            case ASTOption::REMOVE_METHOD:
                $traverser->addVisitor(
                    new Visitor\RemoveMethodVisitor(
                        $data['name'] ?? ($data['method'] ?? '')
                    )
                );
                break;
            case ASTOption::RENAME_CLASS:
                $traverser->addVisitor(
                    new Visitor\RenameClassVisitor(
                        $data['oldName'] ?? ($data['old_name'] ?? ''),
                        $data['newName'] ?? ($data['new_name'] ?? '')
                    )
                );
                break;
            case ASTOption::RENAME_METHOD:
                $traverser->addVisitor(
                    new Visitor\RenameMethodVisitor(
                        $data['oldName'] ?? ($data['old_name'] ?? ''),
                        $data['newName'] ?? ($data['new_name'] ?? '')
                    )
                );
                break;
            case ASTOption::ADD_PROPERTY:
                $traverser->addVisitor(new Visitor\AddPropertyVisitor($data));
                break;
            case ASTOption::REMOVE_PROPERTY:
                $traverser->addVisitor(
                    new Visitor\RemovePropertyVisitor($data['name'] ?? '')
                );
                break;
            case ASTOption::CHANGE_PROPERTY_TYPE:
                $traverser->addVisitor(
                    new Visitor\ChangePropertyTypeVisitor(
                        $data['name'] ?? '',
                        $data['type'] ?? ''
                    )
                );
                break;
            case ASTOption::ADD_CLASS:
                $traverser->addVisitor(new Visitor\AddClassVisitor($data));
                break;
            case ASTOption::REMOVE_CLASS:
                $traverser->addVisitor(
                    new Visitor\RemoveClassVisitor($data['name'] ?? '')
                );
                break;
            case ASTOption::REPLACE_NODE:
                $traverser->addVisitor(new Visitor\ReplaceNodeVisitor($data));
                break;
            case ASTOption::REMOVE_NODE:
                $traverser->addVisitor(new Visitor\RemoveNodeVisitor($data));
                break;
            case ASTOption::MOVE_METHOD:
                $traverser->addVisitor(new Visitor\MoveMethodVisitor($data));
                break;
            case ASTOption::COPY_METHOD:
                $traverser->addVisitor(new Visitor\CopyMethodVisitor($data));
                break;
            case ASTOption::MAKE_METHOD_STATIC:
                $traverser->addVisitor(
                    new Visitor\MakeMethodStaticVisitor($data)
                );
                break;
            case ASTOption::MAKE_METHOD_NON_STATIC:
                $traverser->addVisitor(
                    new Visitor\MakeMethodNonStaticVisitor($data)
                );
                break;
            case ASTOption::CHANGE_METHOD_VISIBILITY:
                $traverser->addVisitor(
                    new Visitor\ChangeMethodVisibilityVisitor($data)
                );
                break;
            case ASTOption::CHANGE_PROPERTY_VISIBILITY:
                $traverser->addVisitor(
                    new Visitor\ChangePropertyVisibilityVisitor($data)
                );
                break;
            case ASTOption::ADD_PARAMETER:
                $traverser->addVisitor(new Visitor\AddParameterVisitor($data));
                break;
            case ASTOption::REMOVE_PARAMETER:
                $traverser->addVisitor(
                    new Visitor\RemoveParameterVisitor($data)
                );
                break;
            case ASTOption::RENAME_PARAMETER:
                $traverser->addVisitor(
                    new Visitor\RenameParameterVisitor($data)
                );
                break;
            case ASTOption::CHANGE_PARAMETER_TYPE:
                $traverser->addVisitor(
                    new Visitor\ChangeParameterTypeVisitor($data)
                );
                break;
            case ASTOption::REORDER_PARAMETERS:
                $traverser->addVisitor(
                    new Visitor\ReorderParametersVisitor($data)
                );
                break;
            case ASTOption::RENAME_NAMESPACE:
                $traverser->addVisitor(
                    new Visitor\RenameNamespaceVisitor($data)
                );
                break;
            case ASTOption::MOVE_CLASS_TO_NAMESPACE:
                $traverser->addVisitor(
                    new Visitor\MoveClassToNamespaceVisitor($data)
                );
                break;
            case ASTOption::REMOVE_COMMENT:
                $traverser->addVisitor(new Visitor\RemoveCommentVisitor($data));
                break;
            case ASTOption::REMOVE_DOCBLOCK:
                $traverser->addVisitor(
                    new Visitor\RemoveDocblockVisitor($data)
                );
                break;
            case ASTOption::UPDATE_DOCBLOCK:
                $traverser->addVisitor(
                    new Visitor\UpdateDocblockVisitor($data)
                );
                break;
            case ASTOption::SORT_USE_STATEMENTS:
                $traverser->addVisitor(
                    new Visitor\SortUseStatementsVisitor($data)
                );
                break;
            case ASTOption::FORMAT_CODE:
                $traverser->addVisitor(new Visitor\FormatCodeVisitor($data));
                break;
            case ASTOption::INLINE_VARIABLE:
                $traverser->addVisitor(
                    new Visitor\InlineVariableVisitor($data)
                );
                break;
            case ASTOption::EXTRACT_CONSTANT:
                $traverser->addVisitor(
                    new Visitor\ExtractConstantVisitor($data)
                );
                break;
            case ASTOption::REPLACE_LITERAL:
                $traverser->addVisitor(
                    new Visitor\ReplaceLiteralVisitor($data)
                );
                break;
            case ASTOption::ADD_ANNOTATION:
                $traverser->addVisitor(new Visitor\AddAnnotationVisitor($data));
                break;
            case ASTOption::REMOVE_ANNOTATION:
                $traverser->addVisitor(
                    new Visitor\RemoveAnnotationVisitor($data)
                );
                break;
            default:
                // Keine Änderung
                return $ast;
        }

        return $traverser->traverse($ast);
    }

    /**
     * Generiert PHP-Code aus einem AST.
     */
    public static function toPhp(array|ASTOperation $stmts): string
    {
        $printer = new PrettyPrinter();

        if ($stmts instanceof ASTOperation) {
            return $printer->prettyPrintFile($stmts->toArray());
        }
        return $printer->prettyPrintFile($stmts);
    }

    /**
     * return array{nodes: array{method: array, class: array}}
     */
    public static function extractStructure(string $phpCode): array
    {
        $ast = self::fromPhp($phpCode);
        // Hier könnte man Klassen, Methoden etc. extrahieren
        return [
            'nodes' => array_map(fn($n) => $n->getType(), $ast),
        ];
    }

    /**
     * Parst PHP-Code in einen AST (als Array von Nodes).
     */
    public static function fromPhp(string $phpCode): array
    {
        try {
            $parser = new ParserFactory()->createForHostVersion();
            return $parser->parse($phpCode) ?? [];
        } catch (Throwable) {
            return [];
        }
    }
}
