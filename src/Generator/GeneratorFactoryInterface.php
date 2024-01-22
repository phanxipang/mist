<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use cebe\openapi\SpecObjectInterface;

interface GeneratorFactoryInterface
{
    /**
     * Create generators for given OpenAPI spec.
     *
     * @return iterable<GeneratorInterface>
     */
    public function create(SpecObjectInterface $spec): iterable;
}
