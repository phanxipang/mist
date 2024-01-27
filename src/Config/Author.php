<?php

declare(strict_types=1);

namespace Fansipan\Mist\Config;

final class Author
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $username = null,
    ) {
    }
}
