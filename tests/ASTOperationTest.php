<?php

declare(strict_types=1);

namespace Bono\AST\Tests;

use Bono\AST\ASTOperation;
use Bono\AST\ASTOption;
use PHPUnit\Framework\TestCase;

class ASTOperationTest extends TestCase
{
    public function testOperationProperties(): void
    {
        $operation         = new ASTOperation();
        $operation->type   = ASTOption::ADD_METHOD;
        $operation->params = [
            'class'  => 'TestClass',
            'method' => 'newMethod',
            'code'   => 'public function newMethod(): void {}',
        ];

        $this->assertSame(ASTOption::ADD_METHOD, $operation->type);
        $this->assertArrayHasKey('class', $operation->params);
        $this->assertArrayHasKey('method', $operation->params);
        $this->assertArrayHasKey('code', $operation->params);
        $this->assertSame('TestClass', $operation->params['class']);
    }

    public function testOperationWithComplexParams(): void
    {
        $operation         = new ASTOperation();
        $operation->type   = ASTOption::REMOVE_METHOD;
        $operation->params = [
            'target' => [
                'class'  => 'Calculator',
                'method' => 'divide',
            ],
            'old'    => 'return $a / $b;',
            'new'    => 'if ($b === 0) throw new Exception(); return $a / $b;',
        ];

        $this->assertSame(ASTOption::REMOVE_METHOD, $operation->type);
        $this->assertIsArray($operation->params['target']);
        $this->assertSame('Calculator', $operation->params['target']['class']);
        $this->assertSame('divide', $operation->params['target']['method']);
    }

    public function testAddClass(): void
    {
        $json = '{"type":"add_class","params":{"name":"JsonDemoClass"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_CLASS, $op->type);
        $this->assertSame('JsonDemoClass', $op->params['name']);
    }

    public function testAddProperty(): void
    {
        $json
            = '{"type":"add_property","params":{"class":"JsonDemoClass","name":"counter","type":"int","visibility":"private","default":0}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_PROPERTY, $op->type);
        $this->assertSame('counter', $op->params['name']);
        $this->assertSame('int', $op->params['type']);
    }

    public function testAddMethod(): void
    {
        $json
            = '{"type":"add_method","params":{"class":"JsonDemoClass","name":"process","code":"public function process(int $value): string { return \'x\'; }"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_METHOD, $op->type);
        $this->assertSame('process', $op->params['name']);
    }

    public function testRenameClass(): void
    {
        $json
            = '{"type":"rename_class","params":{"oldName":"JsonDemoClass","newName":"RenamedJsonDemoClass"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::RENAME_CLASS, $op->type);
        $this->assertSame('JsonDemoClass', $op->params['oldName']);
        $this->assertSame('RenamedJsonDemoClass', $op->params['newName']);
    }

    public function testRemoveClass(): void
    {
        $json = '{"type":"remove_class","params":{"name":"JsonDemoClass"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_CLASS, $op->type);
        $this->assertSame('JsonDemoClass', $op->params['name']);
    }

    public function testAddDocblock(): void
    {
        $json
            = '{"type":"add_docblock","params":{"class":"JsonDemoClass","target":"process","docblock":"/**\\n * Verarbeitet einen Wert.\\n * @param int $value\\n * @return string\\n */"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_DOCBLOCK, $op->type);
        $this->assertSame('process', $op->params['target']);
    }

    public function testAddComment(): void
    {
        $json
            = '{"type":"add_comment","params":{"class":"JsonDemoClass","target":"counter","comment":"// Zähler für die Demo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_COMMENT, $op->type);
        $this->assertSame('counter', $op->params['target']);
    }

    public function testAddUseStatement(): void
    {
        $json = '{"type":"add_use_statement","params":{"use":"DateTime"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_USE_STATEMENT, $op->type);
        $this->assertSame('DateTime', $op->params['use']);
    }

    public function testRenameMethod(): void
    {
        $json
            = '{"type":"rename_method","params":{"class":"JsonDemoClass","oldName":"process","newName":"processValue"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::RENAME_METHOD, $op->type);
        $this->assertSame('process', $op->params['oldName']);
        $this->assertSame('processValue', $op->params['newName']);
    }

    public function testRemoveProperty(): void
    {
        $json
            = '{"type":"remove_property","params":{"class":"JsonDemoClass","name":"counter"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_PROPERTY, $op->type);
        $this->assertSame('counter', $op->params['name']);
    }

    public function testRemoveMethod(): void
    {
        $json
            = '{"type":"remove_method","params":{"class":"JsonDemoClass","name":"callAllTypes"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_METHOD, $op->type);
        $this->assertSame('callAllTypes', $op->params['name']);
    }

    public function testChangeMethodSignature(): void
    {
        $json
            = '{"type":"change_method_signature","params":{"class":"JsonDemoClass","name":"foo","signature":"public function foo(int $a): void"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::CHANGE_METHOD_SIGNATURE,
            $op->type
        );
        $this->assertSame('foo', $op->params['name']);
    }

    public function testUpdateReturnType(): void
    {
        $json
            = '{"type":"update_return_type","params":{"class":"JsonDemoClass","method":"foo","returnType":"string"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::UPDATE_RETURN_TYPE, $op->type);
        $this->assertSame('foo', $op->params['method']);
    }

    public function testAddMethodSignature(): void
    {
        $json
            = '{"type":"add_method_signature","params":{"class":"JsonDemoClass","name":"bar","signature":"public function bar(): int"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_METHOD_SIGNATURE, $op->type);
        $this->assertSame('bar', $op->params['name']);
    }

    public function testChangeClassExtends(): void
    {
        $json
            = '{"type":"change_class_extends","params":{"class":"JsonDemoClass","extends":"BaseClass"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::CHANGE_CLASS_EXTENDS, $op->type);
        $this->assertSame('BaseClass', $op->params['extends']);
    }

    public function testChangeClassImplements(): void
    {
        $json
            = '{"type":"change_class_implements","params":{"class":"JsonDemoClass","implements":["MyInterface"]}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::CHANGE_CLASS_IMPLEMENTS,
            $op->type
        );
        $this->assertContains('MyInterface', $op->params['implements']);
    }

    public function testRemoveUseStatement(): void
    {
        $json = '{"type":"remove_use_statement","params":{"use":"DateTime"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_USE_STATEMENT, $op->type);
        $this->assertSame('DateTime', $op->params['use']);
    }

    public function testReplaceNode(): void
    {
        $json
            = '{"type":"replace_node","params":{"class":"JsonDemoClass","target":"foo","code":"public function foo() { return 1; }"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REPLACE_NODE, $op->type);
        $this->assertSame('foo', $op->params['target']);
    }

    public function testRemoveNode(): void
    {
        $json
            = '{"type":"remove_node","params":{"class":"JsonDemoClass","target":"foo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_NODE, $op->type);
        $this->assertSame('foo', $op->params['target']);
    }

    public function testExtractInterface(): void
    {
        $json
            = '{"type":"extract_interface","params":{"class":"JsonDemoClass","interface":"DemoInterface"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::EXTRACT_INTERFACE, $op->type);
        $this->assertSame('DemoInterface', $op->params['interface']);
    }

    public function testAddInterface(): void
    {
        $json = '{"type":"add_interface","params":{"name":"DemoInterface"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_INTERFACE, $op->type);
        $this->assertSame('DemoInterface', $op->params['name']);
    }

    public function testRemoveInterface(): void
    {
        $json = '{"type":"remove_interface","params":{"name":"DemoInterface"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_INTERFACE, $op->type);
        $this->assertSame('DemoInterface', $op->params['name']);
    }

    public function testAddConstant(): void
    {
        $json
            = '{"type":"add_constant","params":{"class":"JsonDemoClass","name":"FOO","value":42}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_CONSTANT, $op->type);
        $this->assertSame('FOO', $op->params['name']);
    }

    public function testRemoveConstant(): void
    {
        $json
            = '{"type":"remove_constant","params":{"class":"JsonDemoClass","name":"FOO"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_CONSTANT, $op->type);
        $this->assertSame('FOO', $op->params['name']);
    }

    public function testRenameConstant(): void
    {
        $json
            = '{"type":"rename_constant","params":{"class":"JsonDemoClass","oldName":"FOO","newName":"BAR"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::RENAME_CONSTANT, $op->type);
        $this->assertSame('FOO', $op->params['oldName']);
        $this->assertSame('BAR', $op->params['newName']);
    }

    public function testChangeConstantValue(): void
    {
        $json
            = '{"type":"change_constant_value","params":{"class":"JsonDemoClass","name":"FOO","value":99}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::CHANGE_CONSTANT_VALUE,
            $op->type
        );
        $this->assertSame('FOO', $op->params['name']);
    }

    public function testAddTrait(): void
    {
        $json
            = '{"type":"add_trait","params":{"class":"JsonDemoClass","trait":"DemoTrait"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_TRAIT, $op->type);
        $this->assertSame('DemoTrait', $op->params['trait']);
    }

    public function testRemoveTrait(): void
    {
        $json
            = '{"type":"remove_trait","params":{"class":"JsonDemoClass","trait":"DemoTrait"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_TRAIT, $op->type);
        $this->assertSame('DemoTrait', $op->params['trait']);
    }

    public function testExtractTrait(): void
    {
        $json
            = '{"type":"extract_trait","params":{"class":"JsonDemoClass","trait":"DemoTrait"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::EXTRACT_TRAIT, $op->type);
        $this->assertSame('DemoTrait', $op->params['trait']);
    }

    public function testExtractMethod(): void
    {
        $json
            = '{"type":"extract_method","params":{"class":"JsonDemoClass","method":"foo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::EXTRACT_METHOD, $op->type);
        $this->assertSame('foo', $op->params['method']);
    }

    public function testInlineMethod(): void
    {
        $json
            = '{"type":"inline_method","params":{"class":"JsonDemoClass","method":"foo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::INLINE_METHOD, $op->type);
        $this->assertSame('foo', $op->params['method']);
    }

    public function testRenameVariable(): void
    {
        $json
            = '{"type":"rename_variable","params":{"class":"JsonDemoClass","method":"foo","oldName":"a","newName":"b"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::RENAME_VARIABLE, $op->type);
        $this->assertSame('a', $op->params['oldName']);
        $this->assertSame('b', $op->params['newName']);
    }

    public function testChangeVariableType(): void
    {
        $json
            = '{"type":"change_variable_type","params":{"class":"JsonDemoClass","method":"foo","name":"a","type":"float"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::CHANGE_VARIABLE_TYPE, $op->type);
        $this->assertSame('a', $op->params['name']);
        $this->assertSame('float', $op->params['type']);
    }

    public function testRemoveVariable(): void
    {
        $json
            = '{"type":"remove_variable","params":{"class":"JsonDemoClass","method":"foo","name":"a"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_VARIABLE, $op->type);
        $this->assertSame('a', $op->params['name']);
    }

    public function testAddVariable(): void
    {
        $json
            = '{"type":"add_variable","params":{"class":"JsonDemoClass","method":"foo","name":"a","type":"int"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_VARIABLE, $op->type);
        $this->assertSame('a', $op->params['name']);
        $this->assertSame('int', $op->params['type']);
    }

    public function testAddMethodCall(): void
    {
        $json
            = '{"type":"add_method_call","params":{"class":"JsonDemoClass","method":"foo","call":"bar()"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_METHOD_CALL, $op->type);
        $this->assertSame('bar()', $op->params['call']);
    }

    public function testRemoveMethodCall(): void
    {
        $json
            = '{"type":"remove_method_call","params":{"class":"JsonDemoClass","method":"foo","call":"bar()"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_METHOD_CALL, $op->type);
        $this->assertSame('bar()', $op->params['call']);
    }

    public function testRenameMethodCall(): void
    {
        $json
            = '{"type":"rename_method_call","params":{"class":"JsonDemoClass","method":"foo","oldCall":"bar()","newCall":"baz()"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::RENAME_METHOD_CALL, $op->type);
        $this->assertSame('bar()', $op->params['oldCall']);
        $this->assertSame('baz()', $op->params['newCall']);
    }

    public function testChangeMethodCallArguments(): void
    {
        $json
            = '{"type":"change_method_call_arguments","params":{"class":"JsonDemoClass","method":"foo","call":"bar()","arguments":[1,2]}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::CHANGE_METHOD_CALL_ARGUMENTS,
            $op->type
        );
        $this->assertSame('bar()', $op->params['call']);
        $this->assertEquals([1, 2], $op->params['arguments']);
    }

    public function testMoveMethod(): void
    {
        $json
            = '{"type":"move_method","params":{"fromClass":"A","toClass":"B","method":"foo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::MOVE_METHOD, $op->type);
        $this->assertSame('foo', $op->params['method']);
    }

    public function testCopyMethod(): void
    {
        $json
            = '{"type":"copy_method","params":{"fromClass":"A","toClass":"B","method":"foo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::COPY_METHOD, $op->type);
        $this->assertSame('foo', $op->params['method']);
    }

    public function testMakeMethodStatic(): void
    {
        $json
            = '{"type":"make_method_static","params":{"class":"JsonDemoClass","method":"foo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::MAKE_METHOD_STATIC, $op->type);
        $this->assertSame('foo', $op->params['method']);
    }

    public function testMakeMethodNonStatic(): void
    {
        $json
            = '{"type":"make_method_non_static","params":{"class":"JsonDemoClass","method":"foo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::MAKE_METHOD_NON_STATIC,
            $op->type
        );
        $this->assertSame('foo', $op->params['method']);
    }

    public function testChangeMethodVisibility(): void
    {
        $json
            = '{"type":"change_method_visibility","params":{"class":"JsonDemoClass","method":"foo","visibility":"protected"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::CHANGE_METHOD_VISIBILITY,
            $op->type
        );
        $this->assertSame('foo', $op->params['method']);
        $this->assertSame('protected', $op->params['visibility']);
    }

    public function testChangePropertyVisibility(): void
    {
        $json
            = '{"type":"change_property_visibility","params":{"class":"JsonDemoClass","property":"counter","visibility":"protected"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::CHANGE_PROPERTY_VISIBILITY,
            $op->type
        );
        $this->assertSame('counter', $op->params['property']);
        $this->assertSame('protected', $op->params['visibility']);
    }

    public function testAddParameter(): void
    {
        $json
            = '{"type":"add_parameter","params":{"class":"JsonDemoClass","method":"foo","name":"bar","type":"int"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_PARAMETER, $op->type);
        $this->assertSame('bar', $op->params['name']);
        $this->assertSame('int', $op->params['type']);
    }

    public function testRemoveParameter(): void
    {
        $json
            = '{"type":"remove_parameter","params":{"class":"JsonDemoClass","method":"foo","name":"bar"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_PARAMETER, $op->type);
        $this->assertSame('bar', $op->params['name']);
    }

    public function testRenameParameter(): void
    {
        $json
            = '{"type":"rename_parameter","params":{"class":"JsonDemoClass","method":"foo","oldName":"bar","newName":"baz"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::RENAME_PARAMETER, $op->type);
        $this->assertSame('bar', $op->params['oldName']);
        $this->assertSame('baz', $op->params['newName']);
    }

    public function testChangeParameterType(): void
    {
        $json
            = '{"type":"change_parameter_type","params":{"class":"JsonDemoClass","method":"foo","name":"bar","type":"float"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::CHANGE_PARAMETER_TYPE,
            $op->type
        );
        $this->assertSame('bar', $op->params['name']);
        $this->assertSame('float', $op->params['type']);
    }

    public function testReorderParameters(): void
    {
        $json
            = '{"type":"reorder_parameters","params":{"class":"JsonDemoClass","method":"foo","order":["baz","bar"]}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REORDER_PARAMETERS, $op->type);
        $this->assertEquals(["baz", "bar"], $op->params['order']);
    }

    public function testRenameNamespace(): void
    {
        $json
            = '{"type":"rename_namespace","params":{"oldName":"Old\\\\Namespace","newName":"New\\\\Namespace"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::RENAME_NAMESPACE, $op->type);
        $this->assertSame('Old\\Namespace', $op->params['oldName']);
        $this->assertSame('New\\Namespace', $op->params['newName']);
    }

    public function testMoveClassToNamespace(): void
    {
        $json
            = '{"type":"move_class_to_namespace","params":{"class":"JsonDemoClass","namespace":"New\\\\Namespace"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::MOVE_CLASS_TO_NAMESPACE,
            $op->type
        );
        $this->assertSame('New\\Namespace', $op->params['namespace']);
    }

    public function testRemoveComment(): void
    {
        $json
            = '{"type":"remove_comment","params":{"class":"JsonDemoClass","target":"foo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_COMMENT, $op->type);
        $this->assertSame('foo', $op->params['target']);
    }

    public function testRemoveDocblock(): void
    {
        $json
            = '{"type":"remove_docblock","params":{"class":"JsonDemoClass","target":"foo"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_DOCBLOCK, $op->type);
        $this->assertSame('foo', $op->params['target']);
    }

    public function testUpdateDocblock(): void
    {
        $json
            = '{"type":"update_docblock","params":{"class":"JsonDemoClass","target":"foo","docblock":"/** updated */"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::UPDATE_DOCBLOCK, $op->type);
        $this->assertSame('foo', $op->params['target']);
        $this->assertSame('/** updated */', $op->params['docblock']);
    }

    public function testSortUseStatements(): void
    {
        $json = '{"type":"sort_use_statements","params":{}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::SORT_USE_STATEMENTS, $op->type);
    }

    public function testFormatCode(): void
    {
        $json = '{"type":"format_code","params":{}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::FORMAT_CODE, $op->type);
    }

    public function testInlineVariable(): void
    {
        $json
            = '{"type":"inline_variable","params":{"class":"JsonDemoClass","method":"foo","name":"a"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::INLINE_VARIABLE, $op->type);
        $this->assertSame('a', $op->params['name']);
    }

    public function testExtractConstant(): void
    {
        $json
            = '{"type":"extract_constant","params":{"class":"JsonDemoClass","method":"foo","name":"FOO"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::EXTRACT_CONSTANT, $op->type);
        $this->assertSame('FOO', $op->params['name']);
    }

    public function testReplaceLiteral(): void
    {
        $json
            = '{"type":"replace_literal","params":{"class":"JsonDemoClass","method":"foo","old":1,"new":2}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REPLACE_LITERAL, $op->type);
        $this->assertSame(1, $op->params['old']);
        $this->assertSame(2, $op->params['new']);
    }

    public function testAddAnnotation(): void
    {
        $json
            = '{"type":"add_annotation","params":{"class":"JsonDemoClass","target":"foo","annotation":"@MyAnno"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_ANNOTATION, $op->type);
        $this->assertSame('@MyAnno', $op->params['annotation']);
    }

    public function testRemoveAnnotation(): void
    {
        $json
            = '{"type":"remove_annotation","params":{"class":"JsonDemoClass","target":"foo","annotation":"@MyAnno"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_ANNOTATION, $op->type);
        $this->assertSame('@MyAnno', $op->params['annotation']);
    }

    public function testAddGenericTypeAnnotation(): void
    {
        $json
            = '{"type":"add_generic_type_annotation","params":{"class":"JsonDemoClass","target":"foo","annotation":"@template T"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::ADD_GENERIC_TYPE_ANNOTATION,
            $op->type
        );
        $this->assertSame('@template T', $op->params['annotation']);
    }

    public function testRemoveGenericTypeAnnotation(): void
    {
        $json
            = '{"type":"remove_generic_type_annotation","params":{"class":"JsonDemoClass","target":"foo","annotation":"@template T"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::REMOVE_GENERIC_TYPE_ANNOTATION,
            $op->type
        );
        $this->assertSame('@template T', $op->params['annotation']);
    }

    public function testReplaceGenericTypeAnnotation(): void
    {
        $json
            = '{"type":"replace_generic_type_annotation","params":{"class":"JsonDemoClass","target":"foo","old":"@template T","new":"@template U"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(
            ASTOption::REPLACE_GENERIC_TYPE_ANNOTATION,
            $op->type
        );
        $this->assertSame('@template T', $op->params['old']);
        $this->assertSame('@template U', $op->params['new']);
    }

    public function testAddEnum(): void
    {
        $json = '{"type":"add_enum","params":{"name":"DemoEnum"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::ADD_ENUM, $op->type);
        $this->assertSame('DemoEnum', $op->params['name']);
    }

    public function testRemoveEnum(): void
    {
        $json = '{"type":"remove_enum","params":{"name":"DemoEnum"}}';
        $op   = ASTOperation::fromJson($json);
        $this->assertSame(ASTOption::REMOVE_ENUM, $op->type);
        $this->assertSame('DemoEnum', $op->params['name']);
    }
}
