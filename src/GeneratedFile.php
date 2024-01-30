<?php

declare(strict_types=1);

namespace Fansipan\Mist;

use Fansipan\Mist\Exception\FileExistedException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class GeneratedFile
{
    public readonly string $name;

    public function __construct(
        string|array $name,
        private readonly string $content
    ) {
        $this->name = \is_array($name) ? Path::join(...\array_filter($name)) : $name;
    }

    public function save(bool $force = false): void
    {
        $fs = new Filesystem();

        if (! $force && $fs->exists($this->name)) {
            throw new FileExistedException('File is already exists.');
        }

        $fs->dumpFile($this->name, $this->content);
    }
}
