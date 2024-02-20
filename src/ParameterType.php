<?php

declare(strict_types=1);

namespace Fansipan\Mist;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Type;

final class ParameterType implements \Stringable
{
    private const DATA_TYPES = [
        Type::ANY => 'mixed',
        Type::INTEGER => 'int',
        Type::NUMBER => 'float',
        Type::STRING => 'string',
        Type::BOOLEAN => 'bool',
        Type::OBJECT => 'object',
        Type::ARRAY => 'array',
    ];

    public function __construct(
        private readonly string $type,
        private readonly ?string $format = null,
    ) {
    }

    public function type(): string
    {
        return self::DATA_TYPES[$this->type] ?? self::DATA_TYPES[Type::ANY];
    }

    public function __toString(): string
    {
        return $this->type();
    }

    public static function fromSchema(Schema|Reference $schema): self
    {
        $schema = $schema instanceof Reference
            ? $schema->resolve()
            : $schema;

        \assert($schema instanceof Schema);

        return new self($schema->type, $schema->format);
    }
}
