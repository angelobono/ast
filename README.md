# AST

Abstract Syntax Tree – Transformation and Surgery for PHP

## Motivation

This package enables analysis and transformation of PHP code at the AST level. Typical use cases
include refactoring, code generation, static analysis, and automated code modifications.

## Features

- 🌳 AST parsing and manipulation
- 🧩 Extensible for custom node types and transformations
- 🛠️ Numerous visitors for common refactoring tasks

## Installation

```bash
git clone https://github.com/angelobono/ast.git
```

## Quick Start

```php
use Bono\AST\ASTAdapter;
use Bono\AST\Visitor\RenameClassVisitor;

$code = '<?php class OldName {}';
$adapter = new ASTAdapter();
$ast = $adapter->parse($code);

$visitor = new RenameClassVisitor('OldName', 'NewName');
$ast = $adapter->traverse($ast, [$visitor]);

echo $adapter->print($ast); // class NewName {}
```

## Core Visitor Examples

| Visitor            | Purpose                    | Example   |
|--------------------|----------------------------|-----------|
| AddMethodVisitor   | Adds a method to a class   | see below |
| RenameClassVisitor | Renames a class            | see below |
| AddPropertyVisitor | Adds a property to a class | see below |
| ...                | ...                        | ...       |

### AddMethodVisitor

```php
use Bono\AST\Visitor\AddMethodVisitor;

$visitor = new AddMethodVisitor([
    'params' => [
        'class' => 'MyClass',
        'code'  => 'public function foo() { return 42; }'
    ]
]);
```

### RenameClassVisitor

```php
use Bono\AST\Visitor\RenameClassVisitor;

$visitor = new RenameClassVisitor('OldName', 'NewName');
```

### AddPropertyVisitor

```php
use Bono\AST\Visitor\AddPropertyVisitor;

$visitor = new AddPropertyVisitor([
    'params' => [
        'name'       => 'myProp',
        'type'       => 'int',
        'default'    => '0',
        'visibility' => 'private'
    ]
]);
```

## Writing Your Own Visitor

Custom visitors can be created by extending `PhpParser\NodeVisitorAbstract`:

```php
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class MyVisitor extends NodeVisitorAbstract {
    public function enterNode(Node $node) {
        // custom logic
    }
}
```

## Testing

```bash
vendor/bin/phpunit
```

## API Documentation

The API follows PSR-4 and can be documented using tools like phpDocumentor.

## License

MIT License

## What Is Missing for a Release?

- Examples for all important visitors in the README
- An `examples/` directory with real-world use cases
- Optional: Automatically generated API documentation
- Optional: CHANGELOG.md
- Optional: CI/CD setup (e.g. GitHub Actions)

## Overview of All ASTOption Values

The following options are available for AST manipulations (enum `ASTOption`). The list is
synchronized with the enum definition; use `ASTOption::isValid($value)` to validate dynamic input.
Options marked with (v0.4.0+ / v0.5.0+ / v0.6.0+) were introduced in those versions.

- **Methods:**
    - ADD_METHOD
    - REMOVE_METHOD
    - RENAME_METHOD
    - CHANGE_METHOD_SIGNATURE
    - UPDATE_RETURN_TYPE
    - ADD_METHOD_SIGNATURE
    - EXTRACT_METHOD (v0.5.0+)
    - INLINE_METHOD (v0.5.0+)
    - MOVE_METHOD (v0.6.0+)
    - COPY_METHOD (v0.6.0+)
    - MAKE_METHOD_STATIC (v0.6.0+)
    - MAKE_METHOD_NON_STATIC (v0.6.0+)
    - CHANGE_METHOD_VISIBILITY (v0.6.0+)

- **Properties:**
    - ADD_PROPERTY
    - REMOVE_PROPERTY
    - CHANGE_PROPERTY_TYPE
    - CHANGE_PROPERTY_VISIBILITY (v0.6.0+)

- **Classes & Interfaces:**
    - ADD_CLASS
    - REMOVE_CLASS
    - RENAME_CLASS
    - CHANGE_CLASS_EXTENDS
    - CHANGE_CLASS_IMPLEMENTS
    - EXTRACT_INTERFACE (v0.5.0+)
    - ADD_INTERFACE (v0.5.0+)
    - REMOVE_INTERFACE (v0.5.0+)
    - ADD_TRAIT (v0.5.0+)
    - REMOVE_TRAIT (v0.5.0+)
    - EXTRACT_TRAIT (v0.5.0+)

- **Constants:**
    - ADD_CONSTANT
    - REMOVE_CONSTANT
    - RENAME_CONSTANT
    - CHANGE_CONSTANT_VALUE
    - EXTRACT_CONSTANT (v0.6.0+)

- **Variables:**
    - RENAME_VARIABLE
    - CHANGE_VARIABLE_TYPE
    - REMOVE_VARIABLE
    - ADD_VARIABLE
    - INLINE_VARIABLE (v0.6.0+)

- **Method Calls:**
    - ADD_METHOD_CALL
    - REMOVE_METHOD_CALL
    - RENAME_METHOD_CALL
    - CHANGE_METHOD_CALL_ARGUMENTS

- **Parameters:**
    - ADD_PARAMETER (v0.6.0+)
    - REMOVE_PARAMETER (v0.6.0+)
    - RENAME_PARAMETER (v0.6.0+)
    - CHANGE_PARAMETER_TYPE (v0.6.0+)
    - REORDER_PARAMETERS (v0.6.0+)

- **Namespaces / Imports:**
    - ADD_USE_STATEMENT
    - REMOVE_USE_STATEMENT
    - RENAME_NAMESPACE (v0.6.0+)
    - MOVE_CLASS_TO_NAMESPACE (v0.6.0+)
    - SORT_USE_STATEMENTS (v0.6.0+)

- **Comments / DocBlocks:**
    - ADD_COMMENT
    - REMOVE_COMMENT (v0.6.0+)
    - ADD_DOCBLOCK
    - REMOVE_DOCBLOCK (v0.6.0+)
    - UPDATE_DOCBLOCK (v0.6.0+)

- **Annotations:**
    - ADD_ANNOTATION (v0.6.0+)
    - REMOVE_ANNOTATION (v0.6.0+)

- **Generic Type Annotations:**
    - ADD_GENERIC_TYPE_ANNOTATION (v0.6.0+)
    - REMOVE_GENERIC_TYPE_ANNOTATION (v0.6.0+)
    - REPLACE_GENERIC_TYPE_ANNOTATION (v0.6.0+)

- **Enums:**
    - ADD_ENUM (v0.6.0+)
    - REMOVE_ENUM (v0.6.0+)

- **Miscellaneous:**
    - REPLACE_NODE (v0.4.0+)
    - REMOVE_NODE (v0.4.0+)
    - REPLACE_LITERAL (v0.6.0+)
    - FORMAT_CODE (v0.6.0+)
