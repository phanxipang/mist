<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use cebe\openapi\spec\Reference;
use cebe\openapi\SpecObjectInterface;
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

    /**
     * @template T of SpecObjectInterface
     *
     * @param  T  $spec
     * @return T
     *
     * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
     */
    protected function resolveSpecRef(SpecObjectInterface $spec): SpecObjectInterface
    {
        if ($spec instanceof Reference) {
            return $spec->resolve();
        }

        $spec->resolveReferences();

        return $spec;
    }

    /* protected function literal(string $str, mixed ...$args): string
    {
        return (string) new Literal($str, \count($args) ? $args : null);
    } */
}
