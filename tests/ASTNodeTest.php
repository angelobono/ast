<?php

declare(strict_types=1);

namespace Bono\AST\Tests;

use Bono\AST\ASTNode;
use PHPUnit\Framework\TestCase;

class ASTNodeTest extends TestCase
{
    public function testCanCreateNode(): void
    {
        $node = new ASTNode('node1', 'planner', 'Plan implementation');

        $this->assertSame('node1', $node->getId());
        $this->assertSame('planner', $node->getType());
        $this->assertSame('Plan implementation', $node->getContent());
        $this->assertEmpty($node->getChildren());
    }

    public function testCanAddChildren(): void
    {
        $parent = new ASTNode('parent', 'planner', 'Main plan');
        $child1 = new ASTNode('child1', 'developer', 'Code task');
        $child2 = new ASTNode('child2', 'tester', 'Test task');

        $parent->addChild($child1);
        $parent->addChild($child2);

        $children = $parent->getChildren();
        $this->assertCount(2, $children);
        $this->assertSame($child1, $children[0]);
        $this->assertSame($child2, $children[1]);
    }

    public function testNodeHierarchy(): void
    {
        $root    = new ASTNode('root', 'planner', 'Root plan');
        $feature = new ASTNode(
            'feature',
            'developer',
            'Feature implementation'
        );
        $test    = new ASTNode('test', 'tester', 'Feature test');

        $root->addChild($feature);
        $feature->addChild($test);

        $this->assertCount(1, $root->getChildren());
        $this->assertCount(1, $feature->getChildren());
        $this->assertCount(0, $test->getChildren());

        $this->assertSame($feature, $root->getChildren()[0]);
        $this->assertSame($test, $feature->getChildren()[0]);
    }
}
