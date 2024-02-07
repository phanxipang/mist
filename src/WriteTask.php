<?php

declare(strict_types=1);

namespace Fansipan\Mist;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Fansipan\Mist\Config\Config;
use Fansipan\Mist\Generator\GeneratorInterface;
use Symfony\Component\Filesystem\Filesystem;

final class WriteTask implements Task
{
    public function __construct(
        private readonly GeneratorInterface $generator,
        private readonly Config $config,
    ) {
    }

    /**
     * @return GeneratorMessage
     */
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $fs = new Filesystem();
        $file = $this->generator->generate($this->config);

        if ($fs->exists($file->name) && ! $this->config->force) {
            return new GeneratorMessage(GeneratedFileStatus::SKIPPED, $file);
        }

        $file->save();

        return new GeneratorMessage(GeneratedFileStatus::GENERATED, $file);
    }
}
