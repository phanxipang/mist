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
use Fansipan\Mist\Generator\GeneratorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class Runner
{
    /**
     * @param  iterable<GeneratorInterface>  $generators
     */
    public function __construct(
        private readonly iterable $generators,
        private readonly ?EventDispatcherInterface $event = null,
    ) {
    }

    public function generate(Config $config): iterable
    {
        $tasks = Pipeline::fromIterable($this->generators)
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
            ->map($this->handleMessage(...));
    }

    private function handleMessage(GeneratorMessage $message): GeneratorMessage
    {
        $this->event?->dispatch($message);

        return $message;
    }

    public static function loadSpec(string $path): OpenApi
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
