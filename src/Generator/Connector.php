<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use Assert\Assertion;
use Fansipan\Contracts\ConnectorInterface;
use Fansipan\Mist\Config\Config;
use Fansipan\Mist\GeneratedFile;
use Fansipan\Traits\ConnectorTrait;

final class Connector implements GeneratorInterface
{
    use GeneratorTrait;

    public function __construct(private readonly string $baseUri)
    {
        Assertion::url($baseUri);
    }

    public function generate(Config $config): GeneratedFile
    {
        $file = $this->file();

        $namespace = $file->addNamespace($config->package->namespace);
        $namespace->addUse(ConnectorInterface::class)
            ->addUse(ConnectorTrait::class);

        $filename = \rtrim($config->output->files['connector'] ?? 'Connector', '.php');
        $class = $namespace->addClass($filename);

        $class->addImplement(ConnectorInterface::class)
            ->addTrait(ConnectorTrait::class);

        $method = $class->addMethod('baseUri');
        $method->setStatic()
            ->setReturnType('string')
            ->setReturnNullable(true)
            ->addBody('return ?;', [$this->baseUri]);

        return new GeneratedFile(
            [$config->output->directory, $config->output->paths->src, $filename.'.php'],
            $this->print($file)
        );
    }
}
