<?php

declare(strict_types=1);

namespace Fansipan\Mist;

final class GenerationResult
{
    public function __construct(
        public readonly string $output,
        public readonly int $exitCode = 0,
        public readonly ?\Throwable $e = null,
    ) {
    }
}
