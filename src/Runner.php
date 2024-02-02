<?php

declare(strict_types=1);

namespace Fansipan\Mist;

use Amp\Future;
use Amp\Parallel\Worker;
use Amp\Parallel\Worker\Task;
use Amp\Pipeline\Pipeline;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Fansipan\Mist\Config\Config;
use Fansipan\Mist\Event\SdkFileGenerated;
use Fansipan\Mist\Event\SpecFileLoaded;
use Fansipan\Mist\Generator\GeneratorFactoryInterface;
use Fansipan\Mist\Generator\GeneratorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class Runner
{
    public function __construct(
        private readonly GeneratorFactoryInterface $factory,
        private readonly ?EventDispatcherInterface $event = null,
    ) {
    }

    public function generate(Config $config): iterable
    {
        $spec = $this->createSpecFromFile($config->spec);

        $this->event?->dispatch(new SpecFileLoaded($config->spec));

        $tasks = Pipeline::fromIterable($this->factory->create($spec))
            // ->map(static fn (GeneratorInterface $generator): Task => new WriteTask($generator, $config))
            // ->map(static fn (Task $task): Future => Worker\submit($task)->getFuture())
            ->map(fn (GeneratorInterface $generator) => $this->createTask($generator, $config))
            ->concurrent(10)
            ->unordered();

        return Future\awaitAll($tasks);
    }

    private function createTask(GeneratorInterface $generator, Config $config): Future
    {
        return Worker\submit(new WriteTask($generator, $config))
            ->getFuture()
            ->map(function (GeneratedFile $file) {
                $this->event?->dispatch(new SdkFileGenerated($file));

                return $file;
            });
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
