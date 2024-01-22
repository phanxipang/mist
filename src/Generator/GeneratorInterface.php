<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use Fansipan\Mist\ValueObject\Config;
use Fansipan\Mist\ValueObject\GeneratedFile;

interface GeneratorInterface
{
    public function generate(Config $config): GeneratedFile;
}
