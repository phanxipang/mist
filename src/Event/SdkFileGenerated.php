<?php

declare(strict_types=1);

namespace Fansipan\Mist\Event;

use Fansipan\Mist\GeneratedFile;

final class SdkFileGenerated
{
    public function __construct(public readonly GeneratedFile $file)
    {
    }
}
