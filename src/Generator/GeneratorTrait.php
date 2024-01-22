<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

trait GeneratorTrait
{
    protected function file(): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes(true);

        return $file;
    }

    protected function print(PhpFile $file): string
    {
        return (new PsrPrinter())->printFile($file);
    }

    protected function literal(string $str, mixed ...$args): string
    {
        return (string) new Literal($str, \count($args) ? $args : null);
    }
}
