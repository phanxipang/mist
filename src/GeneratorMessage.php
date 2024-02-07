<?php

declare(strict_types=1);

namespace Fansipan\Mist;

final class GeneratorMessage
{
    public function __construct(
        public readonly GeneratedFileStatus $status,
        public readonly GeneratedFile $file,
    ) {
    }
}
