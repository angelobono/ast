<?php

declare(strict_types=1);

use Bono\AST\ASTAdapter;
use PHPUnit\Framework\TestCase;

final class ASTAdapterTest extends TestCase
{
    public function testNormalizePhpAddsPhpTagAndRemovesArtifacts(): void
    {
        $input      = "echo 'hi';\n>\n";
        $normalized = ASTAdapter::normalizePhp($input);
        $this->assertStringStartsWith('<?php', $normalized);
        $this->assertStringNotContainsString('>', $normalized);
        $this->assertStringContainsString("echo 'hi';", $normalized);
    }

    public function testNormalizePhpHandlesInvalidCode(): void
    {
        $input      = "<<<INVALID";
        $normalized = ASTAdapter::normalizePhp($input);
        $this->assertStringContainsString('<?php', $normalized);
    }

    public function testModifyDefaultReturnsAstUnchanged(): void
    {
        $php      = "<?php\nclass Foo {}";
        $ast      = ASTAdapter::fromPhp($php);
        $modified = ASTAdapter::modify($ast, 'unknown_operation', []);
        $this->assertEquals($ast, $modified);
    }

    public function testToPhpAndFromPhpRoundtrip(): void
    {
        $php  = "<?php\nclass Foo { public function bar() { return 1; } }";
        $ast  = ASTAdapter::fromPhp($php);
        $code = ASTAdapter::toPhp($ast);
        $this->assertStringContainsString('function bar', $code);
        $this->assertStringContainsString('return 1;', $code);
    }

    public function testExtractStructureReturnsNodeTypes(): void
    {
        $php       = "<?php\nclass Foo { public function bar() {} }";
        $structure = ASTAdapter::extractStructure($php);
        $this->assertArrayHasKey('nodes', $structure);
        $this->assertContains('Stmt_Class', $structure['nodes']);
    }

    public function testFromPhpReturnsEmptyArrayOnParseError(): void
    {
        $php = "<?php\n<<<INVALID";
        $ast = ASTAdapter::fromPhp($php);
        $this->assertIsArray($ast);
        $this->assertCount(0, $ast);
    }
}
