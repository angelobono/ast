<?php

declare(strict_types=1);

namespace Bono\AST;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * Gibt die Service-Dependencies für Laminas/Mezzio zurück.
     *
     * @return array<string, array>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                // Beispiel: 'MyService' => MyServiceFactory::class
            ],
        ];
    }
}
