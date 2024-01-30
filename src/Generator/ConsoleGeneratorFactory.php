<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use Assert\Assertion;
use Assert\AssertionFailedException;
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

        yield new Composer($spec->info);

        yield $this->createConnectorGenerator($spec);

        yield from $this->createRequestGenerators($spec->paths);
    }

    private function createConnectorGenerator(OpenApi $spec): Connector
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

        return new Connector($url);
    }

    /**
     * @param  Paths|PathItem[]  $paths
     * @return Request[]
     */
    private function createRequestGenerators(iterable $paths): array
    {
        $generator = [];

        foreach ($paths as $path => $pathItem) {
            foreach ($pathItem->getOperations() as $method => $operation) {
                $generator[] = new Request($operation, $method, $path);
            }
        }

        return $generator;
    }
}
