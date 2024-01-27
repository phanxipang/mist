<?php

declare(strict_types=1);

namespace Fansipan\Mist\Config;

final class Paths
{
    public function __construct(
        public readonly string $src = 'src',
        public readonly string $tests = 'tests',
    ) {
    }
}
