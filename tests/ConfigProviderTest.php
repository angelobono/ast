<?php

declare(strict_types=1);

namespace Bono\AST\Tests;

use Bono\AST\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    public function testInvokeReturnsArray(): void
    {
        $config = ($this->configProvider)();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
    }

    public function testGetDependenciesReturnsArray(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey('factories', $dependencies);
    }

    public function testFactoriesIsArray(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertIsArray($dependencies['factories']);
    }

    public function testConfigStructure(): void
    {
        $config = ($this->configProvider)();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('factories', $config['dependencies']);

        // Can be extended to add actual factory mappings when they exist
        $this->assertIsArray($config['dependencies']['factories']);
    }

    protected function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }
}
