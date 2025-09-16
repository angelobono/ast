<?php

declare(strict_types=1);

namespace Bono\AST;

use InvalidArgumentException;

use function json_decode;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;

use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;

class ASTOperation
{
    /**
     * Repräsentiert eine Operation auf dem AST.
     *
     * @var ASTOption $type   Der Typ der Operation (z.B.
     *                        ASTOptions::ADD_METHOD)
     * @var array     $params Spezifische Parameter für die Operation.
     */
    public ASTOption $type;

    /** @var string $path Optionaler Pfad, auf den sich die Operation bezieht. */
    public string $path = '';

    /** @var array $params Spezifische Parameter für die Operation. */
    public array $params;

    public static function fromJson(string $json): ASTOperation
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                'Invalid JSON: ' . json_last_error_msg()
            );
        }
        return self::fromArray($data);
    }

    public static function fromArray(array $data): ASTOperation
    {
        $operation = new ASTOperation();

        if (! isset($data['type'])) {
            throw new InvalidArgumentException(
                'Missing required field: type'
            );
        }
        $operation->type   = ASTOption::from($data['type']);
        $operation->params = $data['params'] ?? [];
        $operation->path   = $data['path'] ?? '';
        return $operation;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function toArray(): array
    {
        return [
            'type'   => $this->type->value,
            'path'   => $this->path,
            'params' => $this->params,
        ];
    }
}
