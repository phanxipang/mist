<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use Assert\Assertion;
use Assert\AssertionFailedException;
use BenTools\RewindableGenerator;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Paths;
use cebe\openapi\spec\Server;
use cebe\openapi\SpecObjectInterface;
use Laravel\Prompts;

final class ConsoleGeneratorFactory implements GeneratorFactoryInterface
{
    public function create(SpecObjectInterface $spec): iterable
    {
        \assert($spec instanceof OpenApi);

        $generators = function (OpenApi $spec) {
            yield 'composer' => new Composer($spec->info);

            yield from $this->createConnectorGenerator($spec);

            yield from $this->createRequestGenerators($spec->paths);
        };

        return new RewindableGenerator($generators($spec));
    }

    private function createConnectorGenerator(OpenApi $spec): \Generator
    {
        if (count($spec->servers) > 1) {
            $urls = \array_map(
                static fn (Server $server): string => $server->url,
                $spec->servers
            );

            $url = Prompts\select('Please select base url', $urls, $urls[0]);
        } else {
            $url = $spec->servers[0]->url;
        }

        try {
            Assertion::url($url);
        } catch (AssertionFailedException) {
            $url = Prompts\text('Please enter the base url');
        }

        yield 'connector' => new Connector($url);
    }

    /**
     * @param  Paths|PathItem[]  $paths
     */
    private function createRequestGenerators(iterable $paths): \Generator
    {
        foreach ($paths as $path => $pathItem) {
            foreach ($pathItem->getOperations() as $method => $operation) {
                yield $operation->operationId => new Request($operation, $method, $path);
            }
        }
    }
}
