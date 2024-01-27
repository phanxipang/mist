<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use Fansipan\Mist\Config\Config;
use Fansipan\Mist\GeneratedFile;

interface GeneratorInterface
{
    public function generate(Config $config): GeneratedFile;
}
