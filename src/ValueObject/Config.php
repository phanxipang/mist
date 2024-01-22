<?php

declare(strict_types=1);

namespace Fansipan\Mist\ValueObject;

final class Config
{
    public const DEFAULT_YAML_FILE = __DIR__.'/../../config/mist.yml';

    public function __construct(
        public readonly string $spec,
        public readonly Output $output,
        public readonly PackageMetadata $package,
        public readonly bool $force = false,
    ) {
    }
}
