<?php

declare(strict_types=1);

namespace Fansipan\Mist;

enum GeneratedFileStatus
{
    case INVALID;
    case SKIPPED;
    case GENERATED;
    case EXCEPTION;
}
