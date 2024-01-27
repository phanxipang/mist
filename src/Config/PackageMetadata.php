<?php

declare(strict_types=1);

namespace Fansipan\Mist\Config;

use Assert\Assertion;
use Composer\Semver\VersionParser;
use Symfony\Component\Filesystem\Path;

final class PackageMetadata
{
    public readonly string $namespace;

    public readonly string $minimumPhpVersion;

    public function __construct(
        public readonly string $phpVersion,
        public readonly string $vendor,
        public readonly string $name,
        public readonly string $description = '',
        ?string $namespace = null,
        public readonly ?Author $author = null,
    ) {
        $this->namespace = $namespace ?? (\ucwords($vendor).'\\'.\ucwords($name));
        $constraint = (new VersionParser())->parseConstraints($phpVersion);
        $this->minimumPhpVersion = $constraint->getLowerBound()->getVersion();
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

        Assertion::keyExists($content['require'], 'php');
        $author = $content['authors'][0] ?? [];

        return new self(
            $content['require']['php'],
            $vendor,
            $name,
            $content['description'] ?? '',
            null,
            new Author($author['name'] ?? '', $author['email'] ?? '', $vendor)
        );
    }
}
