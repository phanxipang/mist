<?php

declare(strict_types=1);

namespace Fansipan\Mist\ValueObject;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class GeneratedFile
{
    private readonly string $name;

    public function __construct(
        string|array $name,
        private readonly string $content
    ) {
        $this->name = \is_array($name) ? Path::join(...\array_filter($name)) : $name;
    }

    public function save(): void
    {
        (new Filesystem())->dumpFile($this->name, $this->content);
    }
}
