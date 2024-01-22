<?php

declare(strict_types=1);

namespace Fansipan\Mist\ValueObject;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use Illuminate\Support\LazyCollection;

/**
 * @extends LazyCollection<array-key, Parameter>
 */
final class ParameterCollection extends LazyCollection
{
    public function inPath(): static
    {
        return $this->filter(static fn (Parameter $param) => $param->in === 'path');
    }

    public function inHeader(): static
    {
        return $this->filter(static fn (Parameter $param) => $param->in === 'header');
    }

    public function inQuery(): static
    {
        return $this->filter(static fn (Parameter $param) => $param->in === 'query');
    }

    /**
     * @param  iterable<Parameter> $parameters
     */
    public static function from(iterable $parameters): static
    {
        return new static(static fn () => yield from $parameters);
    }

    public static function tryFrom(iterable $parameters): static
    {
        return new static(static function () use ($parameters) {
            foreach ($parameters as $parameter) {
                if ($parameter instanceof Reference) {
                    $parameter = $parameter->resolve();
                }

                if ($parameter instanceof Parameter) {
                    yield $parameter;
                }
            }
        });
    }
}
