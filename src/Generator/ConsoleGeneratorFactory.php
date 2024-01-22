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
use Symfony\Component\Console\Style\StyleInterface;

final class ConsoleGeneratorFactory implements GeneratorFactoryInterface
{
    public function __construct(
        private readonly StyleInterface $io
    ) {
    }

    public function create(SpecObjectInterface $spec): iterable
    {
        \assert($spec instanceof OpenApi);

        $phpVersion = $this->io->ask('Please specify your PHP version constraint', '^8.1');

        yield new Composer($phpVersion);

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

            $url = $this->io->choice('Please select base url', $urls);
        } else {
            $url = $spec->servers[0]->url;
        }

        try {
            Assertion::url($url);
        } catch (AssertionFailedException) {
            $url = $this->io->ask('Please enter the base url');
        }

        return new Connector($url);
    }

    /**
     * @param  Paths|PathItem[] $paths
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
