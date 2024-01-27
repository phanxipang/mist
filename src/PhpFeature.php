<?php

declare(strict_types=1);

namespace Fansipan\Mist;

final class PhpFeature
{
    public function __construct(private readonly string $phpVersion)
    {
    }

    public function supportEnums(): bool
    {
        return \version_compare($this->phpVersion, '8.1', '>=');
    }

    public function supportConstructorPropertyPromotion(): bool
    {
        return \version_compare($this->phpVersion, '8.0', '>=');
    }

    public function supportTypedProperties(): bool
    {
        return \version_compare($this->phpVersion, '7.4', '>=');
    }
}
