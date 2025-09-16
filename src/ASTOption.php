<?php

declare(strict_types=1);

namespace Bono\AST;

use BadMethodCallException;

use function array_map;
use function implode;
use function sprintf;
use function str_contains;

use const PHP_EOL;

enum ASTOption: string
{
    // Methoden
    case ADD_METHOD              = 'add_method';
    case REMOVE_METHOD           = 'remove_method';
    case RENAME_METHOD           = 'rename_method';
    case CHANGE_METHOD_SIGNATURE = 'change_method_signature';
    case UPDATE_RETURN_TYPE      = 'update_return_type';
    case ADD_METHOD_SIGNATURE    = 'add_method_signature';

    // Properties
    case ADD_PROPERTY         = 'add_property';
    case REMOVE_PROPERTY      = 'remove_property';
    case CHANGE_PROPERTY_TYPE = 'change_property_type';

    // Klassen & Interfaces
    case ADD_CLASS               = 'add_class';
    case REMOVE_CLASS            = 'remove_class';
    case RENAME_CLASS            = 'rename_class';
    case CHANGE_CLASS_EXTENDS    = 'change_class_extends';
    case CHANGE_CLASS_IMPLEMENTS = 'change_class_implements';

    // Namespaces / Imports
    case ADD_USE_STATEMENT    = 'add_use_statement';
    case REMOVE_USE_STATEMENT = 'remove_use_statement';

    // Kommentare / DocBlocks
    case ADD_COMMENT  = 'add_comment';
    case ADD_DOCBLOCK = 'add_docblock';

    // new in v0.4.0
    case REPLACE_NODE = 'replace_node';
    case REMOVE_NODE  = 'remove_node';

    // new in v0.5.0
    case EXTRACT_INTERFACE = 'extract_interface';
    case ADD_INTERFACE     = 'add_interface';
    case REMOVE_INTERFACE  = 'remove_interface';

    case ADD_CONSTANT          = 'add_constant';
    case REMOVE_CONSTANT       = 'remove_constant';
    case RENAME_CONSTANT       = 'rename_constant';
    case CHANGE_CONSTANT_VALUE = 'change_constant_value';

    case ADD_TRAIT     = 'add_trait';
    case REMOVE_TRAIT  = 'remove_trait';
    case EXTRACT_TRAIT = 'extract_trait';

    case EXTRACT_METHOD = 'extract_method';
    case INLINE_METHOD  = 'inline_method';

    case RENAME_VARIABLE      = 'rename_variable';
    case CHANGE_VARIABLE_TYPE = 'change_variable_type';
    case REMOVE_VARIABLE      = 'remove_variable';
    case ADD_VARIABLE         = 'add_variable';

    case ADD_METHOD_CALL              = 'add_method_call';
    case REMOVE_METHOD_CALL           = 'remove_method_call';
    case RENAME_METHOD_CALL           = 'rename_method_call';
    case CHANGE_METHOD_CALL_ARGUMENTS = 'change_method_call_arguments';

    // v0.6.0
    case MOVE_METHOD                = 'move_method';
    case COPY_METHOD                = 'copy_method';
    case MAKE_METHOD_STATIC         = 'make_method_static';
    case MAKE_METHOD_NON_STATIC     = 'make_method_non_static';
    case CHANGE_METHOD_VISIBILITY   = 'change_method_visibility';
    case CHANGE_PROPERTY_VISIBILITY = 'change_property_visibility';
    case ADD_PARAMETER              = 'add_parameter';
    case REMOVE_PARAMETER           = 'remove_parameter';
    case RENAME_PARAMETER           = 'rename_parameter';
    case CHANGE_PARAMETER_TYPE      = 'change_parameter_type';
    case REORDER_PARAMETERS         = 'reorder_parameters';
    case RENAME_NAMESPACE           = 'rename_namespace';
    case MOVE_CLASS_TO_NAMESPACE    = 'move_class_to_namespace';
    case REMOVE_COMMENT             = 'remove_comment';
    case REMOVE_DOCBLOCK            = 'remove_docblock';
    case UPDATE_DOCBLOCK            = 'update_docblock';
    case SORT_USE_STATEMENTS        = 'sort_use_statements';
    case FORMAT_CODE                = 'format_code';
    case INLINE_VARIABLE            = 'inline_variable';
    case EXTRACT_CONSTANT           = 'extract_constant';
    case REPLACE_LITERAL            = 'replace_literal';
    case ADD_ANNOTATION             = 'add_annotation';
    case REMOVE_ANNOTATION          = 'remove_annotation';

    // Generische Typannotationen
    case ADD_GENERIC_TYPE_ANNOTATION     = 'add_generic_type_annotation';
    case REMOVE_GENERIC_TYPE_ANNOTATION  = 'remove_generic_type_annotation';
    case REPLACE_GENERIC_TYPE_ANNOTATION = 'replace_generic_type_annotation';

    // Enum support
    case ADD_ENUM    = 'add_enum';
    case REMOVE_ENUM = 'remove_enum';

    /**
     * @param string|null $out Format-String with '%s' or null for raw values
     * @return array<string>
     * @throws BadMethodCallException
     */
    public static function join(
        ?string $out = null,
    ): string {
        if ($out !== null && ! str_contains($out, '%s')) {
            throw new BadMethodCallException(
                'Parameter "out" must contain "%s" as a placeholder for the enum value.'
            );
        }
        return PHP_EOL . implode(
            '',
            array_map(
                fn(self $option) => $out !== null
                    ? sprintf($out, $option->value)
                    : $option->value,
                self::cases()
            )
        );
    }

    public static function isValid(string $value): bool
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return true;
            }
        }
        return false;
    }
}
