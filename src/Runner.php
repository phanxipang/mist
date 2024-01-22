<?php

declare(strict_types=1);

namespace Fansipan\Mist;

use Amp\Future;
use Amp\Parallel\Worker;
use Amp\Parallel\Worker\Task;
use Amp\Pipeline\Pipeline;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Fansipan\Mist\Generator\GeneratorFactoryInterface;
use Fansipan\Mist\Generator\GeneratorInterface;
use Fansipan\Mist\ValueObject\Config;

final class Runner
{
    public function __construct(
        private readonly GeneratorFactoryInterface $factory,
    ) {
    }

    public function run(Config $config): iterable
    {
        $spec = $this->createSpecFromFile($config->spec);

        $tasks = Pipeline::fromIterable($this->factory->create($spec))
            ->map(static fn (GeneratorInterface $generator): Task => new WriteTask($generator, $config))
            ->concurrent(10)
            ->unordered();

        return Future\awaitAll(
            $tasks->map(static fn (Task $task): Future => Worker\submit($task)->getFuture())
        );
    }

    private function createSpecFromFile(string $path): OpenApi
    {
        $json = false;

        if (\filter_var($path, \FILTER_VALIDATE_URL) !== false && \str_ends_with($path, '.json')) {
            $json = true;
        } else {
            $file = new \SplFileInfo($path);
            $json = $file->getExtension() === 'json';
        }

        return $json ? Reader::readFromJsonFile($path) : Reader::readFromYamlFile($path);
    }
}
