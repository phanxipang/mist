<?php

declare(strict_types=1);

namespace Fansipan\Mist\ValueObject;

final class Author
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $username = null,
    ) {
    }
}
