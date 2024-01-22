<?php

declare(strict_types=1);

namespace Fansipan\Mist;

use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Type;

final class ParameterType implements \Stringable
{
    private const DATA_TYPES = [
        Type::ANY => 'mixed',
        Type::INTEGER => 'int',
        Type::NUMBER => 'int',
        Type::STRING => 'string',
        Type::BOOLEAN => 'bool',
        Type::OBJECT => 'object',
        Type::ARRAY => 'array',
    ];

    public function __construct(private readonly Schema $schema)
    {
        //
    }

    public function type(): string
    {
        return self::DATA_TYPES[$this->schema->type];
    }

    public function __toString(): string
    {
        return $this->type();
    }
}
