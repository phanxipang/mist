<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use Assert\Assertion;
use Fansipan\Contracts\ConnectorInterface;
use Fansipan\Mist\ValueObject\Config;
use Fansipan\Mist\ValueObject\GeneratedFile;
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

        $class = $namespace->addClass('Connector');

        $class->setFinal()
            ->addImplement(ConnectorInterface::class)
            ->addTrait(ConnectorTrait::class);

        $method = $class->addMethod('baseUri');
        $method->setStatic()
            ->setReturnType('string')
            ->setReturnNullable(true)
            ->addBody($this->literal('return ?;', $this->baseUri));

        return new GeneratedFile(
            [$config->output->directory, $config->output->paths->src, 'Connector.php'],
            $this->print($file)
        );
    }
}
