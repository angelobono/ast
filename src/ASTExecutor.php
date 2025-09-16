<?php

declare(strict_types=1);

namespace Bono\AST;

use PhpParser\Error;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use RuntimeException;

use function array_filter;
use function array_values;
use function error_log;
use function file_exists;
use function file_get_contents;
use function ltrim;
use function preg_match;
use function str_starts_with;
use function trim;

/**
 * ASTExecutor
 * Supported actions:
 *  - add_method:
 *      ['action'=>'add_method','target'=>['class'=>'ClassName'],'code'=>'public
 *      function foo(){ return 1; }']
 *  - rename_method:
 *      ['action'=>'rename_method','target'=>['class'=>'ClassName','method'=>'old'],'new_name'=>'new']
 *  - replace_node (statement-level inside a method):
 *      ['action'=>'replace_node','target'=>['class'=>'ClassName','method'=>'m'],
 *       'old'=>'return $a / $b;', 'code'=>'if ($b===0) throw new
 *       \\InvalidArgumentException("Division by zero"); return $a / $b;']
 *  - remove_node:
 *      1) remove statement inside method:
 *         ['action'=>'remove_node','target'=>['class'=>'ClassName','method'=>'m'],'old'=>'$x
 *         = 1;']
 *      2) remove entire method:
 *         ['action'=>'remove_node','target'=>['class'=>'ClassName','method'=>'m']]
 */
class ASTExecutor
{
    private Parser $parser;
    private PrettyPrinter $printer;

    public function __construct()
    {
        $lexer         = new Lexer(
            ['usedAttributes' => ['comments', 'startLine', 'endLine']]
        );
        $this->parser  = new ParserFactory()->createForHostVersion();
        $this->printer = new PrettyPrinter();
    }

    /**
     * Apply a list of fixes to one or more PHP files.
     *
     * @param array<int,array<string,mixed>|ASTOperation> $fixes
     * @return array<string, string>
     */
    public function applyFixes(array $fixes): array
    {
        $fixesByPath = [];
        foreach ($fixes as $fix) {
            if ($fix instanceof ASTOperation) {
                $fix = $fix->toArray();
            }
            $fix  = $this->normalizeOperation($fix);
            $path = $fix['path'] ?? ($fix['params']['path'] ?? null);

            if (! $path) {
                error_log('ASTExecutor: Missing path in fix');
                continue;
            }
            $fixesByPath[$path][] = $fix;
        }
        $results = [];
        foreach ($fixesByPath as $path => $fileFixes) {
            // Try to read the file, or start with empty PHP code

            $fileCode = file_exists($path)
                ? file_get_contents($path)
                : "<?php\n";

            $ast = $this->parse($fileCode);

            foreach ($fileFixes as $fix) {
                /** @var string|null $type */
                $type = $fix['action'] ?? $fix['type'] ?? null;

                if ($type === null) {
                    error_log('ASTExecutor: Missing action in fix');
                    continue;
                }
                $traverser = new NodeTraverser();

                switch (ASTOption::from($type)) {
                    case ASTOption::ADD_METHOD:
                        $className  = $fix['params']['class'] ?? null;
                        $targetType = $fix['targetType'] ?? null;
                        $nodeFound  = false;
                        foreach ($ast as $node) {
                            if (
                                (
                                    $node instanceof Class_
                                    || $node instanceof
                                    Enum_
                                    || $node instanceof
                                    Interface_
                                )
                                && $node->name
                                && $node->name->toString() === $className
                            ) {
                                $nodeFound = true;
                                break;
                            }
                        }

                        if ($nodeFound) {
                            if (
                                $targetType === 'interface'
                                || $node instanceof
                                Interface_
                            ) {
                                $traverser->addVisitor(
                                    new Visitor\AddMethodSignatureVisitor($fix)
                                );
                            } elseif (
                                $targetType === 'enum'
                                || $node instanceof Enum_
                            ) {
                                $traverser->addVisitor(
                                    new Visitor\AddEnumMethodVisitor($fix)
                                );
                            } else {
                                $traverser->addVisitor(
                                    new Visitor\AddMethodVisitor($fix)
                                );
                            }
                        } else {
                            error_log(
                                "ASTExecutor: Target '$className' not found for adding method in $path."
                            );
                        }
                        break;
                    case ASTOption::RENAME_METHOD:
                        $traverser->addVisitor(
                            new Visitor\RenameMethodVisitor(
                                $fix['target']['method'],
                                $fix['new_name']
                            )
                        );
                        break;
                    case ASTOption::REMOVE_METHOD:
                        $traverser->addVisitor(
                            new Visitor\RemoveMethodVisitor(
                                $fix['target']['method']
                            )
                        );
                        break;
                    case ASTOption::ADD_PROPERTY:
                        $traverser->addVisitor(
                            new Visitor\AddPropertyVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_PROPERTY:
                        $traverser->addVisitor(
                            new Visitor\RemovePropertyVisitor($fix['name'])
                        );
                        break;
                    case ASTOption::RENAME_CLASS:
                        $traverser->addVisitor(
                            new Visitor\RenameClassVisitor(
                                $fix['old_name'],
                                $fix['new_name']
                            )
                        );
                        break;
                    case ASTOption::REMOVE_CLASS:
                        $traverser->addVisitor(
                            new Visitor\RemoveClassVisitor($fix['name'])
                        );
                        break;
                    case ASTOption::ADD_CLASS:
                        $traverser->addVisitor(
                            new Visitor\AddClassVisitor($fix)
                        );
                        break;
                    case ASTOption::CHANGE_PROPERTY_TYPE:
                        $traverser->addVisitor(
                            new Visitor\ChangePropertyTypeVisitor(
                                $fix['name'],
                                $fix['type']
                            )
                        );
                        break;
                    case ASTOption::REPLACE_NODE:
                        $oldStmtNodes = $this->parseStmts($fix['old'] ?? '');
                        $newStmtNodes = $this->parseStmts($fix['code'] ?? '');
                        $traverser->addVisitor(
                            new Visitor\ReplaceNodeVisitor(
                                $fix,
                                $oldStmtNodes,
                                $newStmtNodes
                            )
                        );
                        break;
                    case ASTOption::REMOVE_NODE:
                        $oldStmtNodes = $this->parseStmts($fix['old'] ?? '');
                        $traverser->addVisitor(
                            new Visitor\RemoveNodeVisitor(
                                $fix,
                                $oldStmtNodes
                            )
                        );
                        break;
                    case ASTOption::MOVE_METHOD:
                        $traverser->addVisitor(
                            new Visitor\MoveMethodVisitor($fix)
                        );
                        break;
                    case ASTOption::COPY_METHOD:
                        $traverser->addVisitor(
                            new Visitor\CopyMethodVisitor($fix)
                        );
                        break;
                    case ASTOption::MAKE_METHOD_STATIC:
                        $traverser->addVisitor(
                            new Visitor\MakeMethodStaticVisitor($fix)
                        );
                        break;
                    case ASTOption::MAKE_METHOD_NON_STATIC:
                        $traverser->addVisitor(
                            new Visitor\MakeMethodNonStaticVisitor($fix)
                        );
                        break;
                    case ASTOption::CHANGE_METHOD_VISIBILITY:
                        $traverser->addVisitor(
                            new Visitor\ChangeMethodVisibilityVisitor($fix)
                        );
                        break;
                    case ASTOption::CHANGE_PROPERTY_VISIBILITY:
                        $traverser->addVisitor(
                            new Visitor\ChangePropertyVisibilityVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_PARAMETER:
                        $traverser->addVisitor(
                            new Visitor\AddParameterVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_PARAMETER:
                        $traverser->addVisitor(
                            new Visitor\RemoveParameterVisitor($fix)
                        );
                        break;
                    case ASTOption::RENAME_PARAMETER:
                        $traverser->addVisitor(
                            new Visitor\RenameParameterVisitor($fix)
                        );
                        break;
                    case ASTOption::CHANGE_PARAMETER_TYPE:
                        $traverser->addVisitor(
                            new Visitor\ChangeParameterTypeVisitor(
                                $fix['parameter'] ?? $fix['name'] ?? '',
                                $fix['type'] ?? $fix['new_type'] ?? ''
                            )
                        );
                        break;
                    case ASTOption::REORDER_PARAMETERS:
                        $traverser->addVisitor(
                            new Visitor\ReorderParametersVisitor($fix)
                        );
                        break;
                    case ASTOption::RENAME_NAMESPACE:
                        $traverser->addVisitor(
                            new Visitor\RenameNamespaceVisitor($fix)
                        );
                        break;
                    case ASTOption::MOVE_CLASS_TO_NAMESPACE:
                        $traverser->addVisitor(
                            new Visitor\MoveClassToNamespaceVisitor(
                                $fix['class'] ?? $fix['name'] ?? '',
                                $fix['target_namespace'] ??
                                $fix['namespace'] ?? ''
                            )
                        );
                        break;
                    case ASTOption::REMOVE_COMMENT:
                        $traverser->addVisitor(
                            new Visitor\RemoveCommentVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_DOCBLOCK:
                        $traverser->addVisitor(
                            new Visitor\RemoveDocblockVisitor($fix)
                        );
                        break;
                    case ASTOption::UPDATE_DOCBLOCK:
                        $traverser->addVisitor(
                            new Visitor\UpdateDocblockVisitor($fix)
                        );
                        break;
                    case ASTOption::SORT_USE_STATEMENTS:
                        $traverser->addVisitor(
                            new Visitor\SortUseStatementsVisitor($fix)
                        );
                        break;
                    case ASTOption::FORMAT_CODE:
                        $traverser->addVisitor(
                            new Visitor\FormatCodeVisitor($fix)
                        );
                        break;
                    case ASTOption::INLINE_VARIABLE:
                        $traverser->addVisitor(
                            new Visitor\InlineVariableVisitor(
                                $fix['variable'] ?? $fix['name'] ?? ''
                            )
                        );
                        break;
                    case ASTOption::EXTRACT_CONSTANT:
                        $traverser->addVisitor(
                            new Visitor\ExtractConstantVisitor(
                                $fix['constant'] ?? $fix['name'] ?? ''
                            )
                        );
                        break;
                    case ASTOption::REPLACE_LITERAL:
                        $traverser->addVisitor(
                            new Visitor\ReplaceLiteralVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_ANNOTATION:
                        $traverser->addVisitor(
                            new Visitor\AddAnnotationVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_ANNOTATION:
                        $traverser->addVisitor(
                            new Visitor\RemoveAnnotationVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_GENERIC_TYPE_ANNOTATION:
                        $traverser->addVisitor(
                            new Visitor\AddGenericTypeAnnotationVisitor(
                                $fix['parameter'],
                                $fix['annotation']
                            )
                        );
                        break;
                    case ASTOption::REMOVE_GENERIC_TYPE_ANNOTATION:
                        $traverser->addVisitor(
                            new Visitor\RemoveGenericTypeAnnotationVisitor(
                                $fix['parameter'],
                                $fix['annotation_pattern']
                            )
                        );
                        break;
                    case ASTOption::REPLACE_GENERIC_TYPE_ANNOTATION:
                        $traverser->addVisitor(
                            new Visitor\ReplaceGenericTypeAnnotationVisitor(
                                $fix['parameter'],
                                $fix['old_pattern'],
                                $fix['new_annotation']
                            )
                        );
                        break;
                    case ASTOption::CHANGE_METHOD_SIGNATURE:
                        $traverser->addVisitor(
                            new Visitor\ChangeMethodSignatureVisitor($fix)
                        );
                        break;
                    case ASTOption::UPDATE_RETURN_TYPE:
                        $traverser->addVisitor(
                            new Visitor\UpdateReturnTypeVisitor($fix)
                        );
                        break;
                    case ASTOption::CHANGE_CLASS_EXTENDS:
                        $traverser->addVisitor(
                            new Visitor\ChangeClassExtendsVisitor($fix)
                        );
                        break;
                    case ASTOption::CHANGE_CLASS_IMPLEMENTS:
                        $traverser->addVisitor(
                            new Visitor\ChangeClassImplementsVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_USE_STATEMENT:
                        $traverser->addVisitor(
                            new Visitor\AddUseStatementVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_USE_STATEMENT:
                        $traverser->addVisitor(
                            new Visitor\RemoveUseStatementVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_COMMENT:
                        $traverser->addVisitor(
                            new Visitor\AddCommentVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_DOCBLOCK:
                        $traverser->addVisitor(
                            new Visitor\AddDocblockVisitor($fix)
                        );
                        break;
                    case ASTOption::EXTRACT_INTERFACE:
                        $traverser->addVisitor(
                            new Visitor\ExtractInterfaceVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_INTERFACE:
                        $traverser->addVisitor(
                            new Visitor\AddInterfaceVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_INTERFACE:
                        $traverser->addVisitor(
                            new Visitor\RemoveInterfaceVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_CONSTANT:
                        $traverser->addVisitor(
                            new Visitor\AddConstantVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_CONSTANT:
                        $traverser->addVisitor(
                            new Visitor\RemoveConstantVisitor($fix)
                        );
                        break;
                    case ASTOption::RENAME_CONSTANT:
                        $traverser->addVisitor(
                            new Visitor\RenameConstantVisitor($fix)
                        );
                        break;
                    case ASTOption::CHANGE_CONSTANT_VALUE:
                        $traverser->addVisitor(
                            new Visitor\ChangeConstantValueVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_TRAIT:
                        $traverser->addVisitor(
                            new Visitor\AddTraitVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_TRAIT:
                        $traverser->addVisitor(
                            new Visitor\RemoveTraitVisitor($fix)
                        );
                        break;
                    case ASTOption::EXTRACT_TRAIT:
                        $traverser->addVisitor(
                            new Visitor\ExtractTraitVisitor($fix)
                        );
                        break;
                    case ASTOption::EXTRACT_METHOD:
                        $traverser->addVisitor(
                            new Visitor\ExtractMethodVisitor($fix)
                        );
                        break;
                    case ASTOption::INLINE_METHOD:
                        $traverser->addVisitor(
                            new Visitor\InlineMethodVisitor($fix)
                        );
                        break;
                    case ASTOption::RENAME_VARIABLE:
                        $traverser->addVisitor(
                            new Visitor\RenameVariableVisitor($fix)
                        );
                        break;
                    case ASTOption::CHANGE_VARIABLE_TYPE:
                        $traverser->addVisitor(
                            new Visitor\ChangeVariableTypeVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_VARIABLE:
                        $traverser->addVisitor(
                            new Visitor\RemoveVariableVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_VARIABLE:
                        $traverser->addVisitor(
                            new Visitor\AddVariableVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_METHOD_CALL:
                        $traverser->addVisitor(
                            new Visitor\AddMethodCallVisitor($fix)
                        );
                        break;
                    case ASTOption::REMOVE_METHOD_CALL:
                        $traverser->addVisitor(
                            new Visitor\RemoveMethodCallVisitor($fix)
                        );
                        break;
                    case ASTOption::RENAME_METHOD_CALL:
                        $traverser->addVisitor(
                            new Visitor\RenameMethodCallVisitor($fix)
                        );
                        break;
                    case ASTOption::CHANGE_METHOD_CALL_ARGUMENTS:
                        $traverser->addVisitor(
                            new Visitor\ChangeMethodCallArgumentsVisitor($fix)
                        );
                        break;
                    case ASTOption::ADD_ENUM:
                        $traverser->addVisitor(
                            new Visitor\AddEnumVisitor($fix)
                        );
                        break;
                    // ... weitere Visitors für andere Operationen ...
                    default:
                        // Noch nicht als Visitor umgesetzt
                        break;
                }
                $ast = $traverser->traverse($ast);
            }
            $results[$path] = $this->printer->prettyPrintFile($ast);
        }
        return $results;
    }

    /**
     * Normalize an operation to always use 'params' for all relevant fields.
     */
    private function normalizeOperation(array $fix): array
    {
        if (! isset($fix['params'])) {
            $fix['params'] = [];
        }
        foreach (
            [
                'class',
                'name',
                'code',
                'extends',
                'implements',
                'method',
                'old',
                'new_name',
                'type',
                'target_namespace',
                'namespace',
                'parameter',
                'annotation',
                'annotation_pattern',
                'old_pattern',
                'new_annotation',
                'new_type',
                'constant',
                'property',
                'value',
                'flags',
                'visibility',
                'interface',
                'trait',
                'use',
                'comment',
                'docblock',
                'order',
                'variable',
                'signature',
                'return_type',
                'old_name',
                'new_name',
                'path',
                'file',
                'target',
                'params',
                'action',
                'type',
                'target_class',
                'target_method',
                'target_property',
                'target_constant',
                'target_interface',
                'target_trait',
                'target_use',
                'target_comment',
                'target_docblock',
                'target_order',
                'target_variable',
                'target_signature',
                'target_return_type',
                'target_old_name',
                'target_new_name',
                'target_path',
                'target_file',
            ] as $key
        ) {
            if (isset($fix[$key])) {
                $fix['params'][$key] = $fix[$key];
            }
            if (isset($fix['target'][$key])) {
                $fix['params'][$key] = $fix['target'][$key];
            }
        }
        return $fix;
    }

    /**
     * Helper to parse full PHP code (expects `<?php` present or not).
     *
     * @return array<Node>
     */
    public function parse(string $code): array
    {
        $code = ltrim($code);

        if (! str_starts_with($code, '<?php')) {
            $code = "<?php\n" . $code;
        }
        try {
            $ast = $this->parser->parse($code);
        } catch (Error $e) {
            throw new RuntimeException(
                "Parse error: " . $e->getMessage()
                . "\nCode: " . $code
            );
        }

        if ($ast === null) {
            throw new RuntimeException("Parse error: AST is null");
        }
        return $ast;
    }

    /**
     * Parse a *statement list* snippet (no opening tag required).
     *
     * @return array<Node\Stmt>
     */
    private function parseStmts(string $snippet): array
    {
        $snippet = trim($snippet);

        if ($snippet === '') {
            return [];
        }
        if (
            preg_match(
                '/^(public|protected|private)?\s*function\s/mi',
                $snippet
            )
        ) {
            $wrapped = "<?php\nclass _Dummy {\n$snippet\n}";
            $nodes   = $this->parse($wrapped);
            foreach ($nodes as $node) {
                if ($node instanceof Class_) {
                    return array_values(
                        array_filter(
                            $node->stmts,
                            fn($n) => $n instanceof Node\Stmt
                        )
                    );
                }
            }
            return [];
        }
        // Sonst wie gehabt
        $wrapped = "<?php\n" . $snippet;
        $nodes   = $this->parse($wrapped);
        return array_values(
            array_filter($nodes, fn($n) => $n instanceof Node\Stmt)
        );
    }
}
