<?php

declare(strict_types=1);

namespace Fansipan\Mist\ValueObject;

use Symfony\Component\Filesystem\Path;

final class PackageMetadata
{
    public readonly string $namespace;

    public function __construct(
        public readonly string $vendor,
        public readonly string $name,
        public readonly string $description = '',
        ?string $namespace = null,
        public readonly ?Author $author = null,
    ) {
        $this->namespace = $namespace ?? (\ucwords($vendor).'\\'.\ucwords($name));
    }

    public static function fromComposer(string $path): self
    {
        $file = \str_ends_with($path, 'composer.json')
            ? $path
            : Path::join($path, 'composer.json');

        if (! \file_exists($file)) {
            throw new \InvalidArgumentException('composer.json not found in '.$file);
        }

        $content = \json_decode(\file_get_contents($file) ?: '', true);
        [$vendor, $name] = \explode('/', $content['name']);

        return new self(
            $vendor,
            $name,
            $content['description'] ?? '',
        );
    }
}
