<?php

declare(strict_types=1);

namespace Fansipan\Mist\ValueObject;

use CuyZ\Valinor\MapperBuilder;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

final class Output
{
    public readonly string $directory;

    public function __construct(
        string $directory,
        public readonly Paths $paths,
    ) {
        $this->directory = Path::isAbsolute(Path::canonicalize($directory)) ? $directory : Path::join(\getcwd() ?: './', $directory);
    }

    public static function fromArray(array ...$array): self
    {
        $default = Yaml::parseFile(Config::DEFAULT_YAML_FILE);

        $data = \array_replace_recursive(
            \array_merge_recursive($default['output'] ?? [], ...$array),
            ...$array
        );

        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->mapper()
            ->map(self::class, $data);
    }

    public static function fromYamlFile(string $file, array $merges = []): self
    {
        if (! \file_exists($file)) {
            return self::fromArray($merges);
        }

        return self::fromArray(Yaml::parseFile($file), $merges);
    }
}
