<?php

declare(strict_types=1);

namespace Fansipan\Mist;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Fansipan\Mist\Generator\GeneratorInterface;
use Fansipan\Mist\ValueObject\Config;
use Fansipan\Mist\ValueObject\GeneratedFile;

final class WriteTask implements Task
{
    public function __construct(
        private readonly GeneratorInterface $generator,
        private readonly Config $config,
    ) {
    }

    /**
     * @return GeneratedFile
     */
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $file = $this->generator->generate($this->config);
        $file->save($this->config->force);

        return $file;
    }
}
