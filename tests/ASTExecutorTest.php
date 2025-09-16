<?php

declare(strict_types=1);

use Bono\AST\ASTExecutor;
use PHPUnit\Framework\TestCase;

class ASTExecutorTest extends TestCase
{
    public function testAddMethodToClassWithVariables()
    {
        $executor = new ASTExecutor();
        $fixes    = [
            [
                'action' => 'add_class',
                'params' => [
                    'name' => 'MyClass',
                ],
                'path'   => 'MyClass.php',
            ],
            [
                'action' => 'add_method',
                'params' => [
                    'class' => 'MyClass',
                    'code'  => 'public function foo() { $bar = 1; return $bar; }',
                ],
                'path'   => 'MyClass.php',
            ],
        ];
        $results  = $executor->applyFixes($fixes);

        $this->assertStringContainsString(
            'public function foo()',
            $results['MyClass.php']
        );
        $this->assertStringContainsString(
            '$bar = 1;',
            $results['MyClass.php']
        );
        $this->assertStringContainsString(
            'return $bar;',
            $results['MyClass.php']
        );
    }

    public function testAddMethodToInterfaceWithVariables(): void
    {
        $executor = new ASTExecutor();
        $fixes    = [
            [
                'action' => 'add_interface',
                'params' => [
                    'interface' => 'MyInterface',
                ],
                'path'   => 'MyInterface.php',
            ],
            [
                'action'     => 'add_method',
                'params'     => [
                    'class' => 'MyInterface',
                    'code'  => 'public function foo();',
                ],
                'path'       => 'MyInterface.php',
                'targetType' => 'interface',
            ],
        ];
        $results  = $executor->applyFixes($fixes);
        $this->assertStringContainsString(
            'interface MyInterface',
            $results['MyInterface.php']
        );
        $this->assertStringContainsString(
            'public function foo();',
            $results['MyInterface.php']
        );
    }

    public function testAddMethodToEnumWithVariables()
    {
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $this->markTestSkipped('Enums require PHP 8.1+');
        }
        $executor = new ASTExecutor();
        $fixes    = [
            [
                'action' => 'add_enum',
                'params' => [
                    'name' => 'MyEnum',
                ],
                'path'   => 'MyEnum.php',
            ],
            [
                'action' => 'add_method',
                'params' => [
                    'class' => 'MyEnum',
                    'code'  => 'public function foo() { $bar = 42; return $bar; }',
                ],
                'path'   => 'MyEnum.php',
            ],
        ];
        $results  = $executor->applyFixes($fixes);

        $this->assertStringContainsString(
            'enum MyEnum',
            $results['MyEnum.php']
        );
        $this->assertStringContainsString(
            'public function foo()',
            $results['MyEnum.php']
        );
        $this->assertStringContainsString(
            '$bar = 42;',
            $results['MyEnum.php']
        );
    }

    public function testAddConstantToClassAndInterfaceAndEnum()
    {
        $executor = new ASTExecutor();
        $fixes    = [
            [
                'action' => 'add_class',
                'params' => [
                    'name' => 'ConstClass',
                ],
                'path'   => 'ConstClass.php',
            ],
            [
                'action' => 'add_constant',
                'params' => [
                    'class'    => 'ConstClass',
                    'constant' => 'FOO',
                    'value'    => 123,
                ],
                'path'   => 'ConstClass.php',
            ],
            [
                'action' => 'add_interface',
                'params' => [
                    'interface' => 'ConstInterface',
                ],
                'path'   => 'ConstInterface.php',
            ],
            [
                'action'     => 'add_constant',
                'params'     => [
                    'class'    => 'ConstInterface',
                    'constant' => 'BAR',
                    'value'    => 456,
                ],
                'path'       => 'ConstInterface.php',
                'targetType' => 'interface',
            ],
            [
                'action' => 'add_enum',
                'params' => [
                    'name' => 'ConstEnum',
                ],
                'path'   => 'ConstEnum.php',
            ],
            [
                'action' => 'add_constant',
                'params' => [
                    'class'    => 'ConstEnum',
                    'constant' => 'BAZ',
                    'value'    => 789,
                ],
                'path'   => 'ConstEnum.php',
            ],
        ];
        $results  = $executor->applyFixes($fixes);

        $this->assertStringContainsString(
            'class ConstClass',
            $results['ConstClass.php']
        );
        $this->assertStringContainsString(
            'const FOO = 123;',
            $results['ConstClass.php']
        );
        $this->assertStringContainsString(
            'interface ConstInterface',
            $results['ConstInterface.php']
        );
        $this->assertStringContainsString(
            'const BAR = 456;',
            $results['ConstInterface.php']
        );
        $this->assertStringContainsString(
            'enum ConstEnum',
            $results['ConstEnum.php']
        );
        $this->assertStringContainsString(
            'const BAZ = 789;',
            $results['ConstEnum.php']
        );
    }

    public function testReplaceNodeWithVariablesInClass()
    {
        $executor = new ASTExecutor();
        $fixes    = [
            [
                'action' => 'add_class',
                'params' => [
                    'name' => 'ReplaceClass',
                ],
                'path'   => 'ReplaceClass.php',
            ],
            [
                'action' => 'add_method',
                'params' => [
                    'class' => 'ReplaceClass',
                    'code'  => 'public function foo() { $a = 1; $b = 2; return $a + $b; }',
                ],
                'path'   => 'ReplaceClass.php',
            ],
            [
                'action' => 'replace_node',
                'params' => [
                    'class'  => 'ReplaceClass',
                    'method' => 'foo',
                    'old'    => '$a = 1;',
                    'code'   => '$a = 42;',
                ],
                'path'   => 'ReplaceClass.php',
            ],
        ];
        $results  = $executor->applyFixes($fixes);

        $this->assertStringContainsString(
            '$a = 42;',
            $results['ReplaceClass.php']
        );
        $this->assertStringNotContainsString(
            '$a = 1;',
            $results['ReplaceClass.php']
        );
    }
}
