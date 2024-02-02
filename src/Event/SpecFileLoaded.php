<?php

declare(strict_types=1);

namespace Fansipan\Mist\Event;

final class SpecFileLoaded
{
    public function __construct(public readonly string $spec)
    {
    }
}
