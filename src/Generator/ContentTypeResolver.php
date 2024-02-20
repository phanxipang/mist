<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use Fansipan\Body\AsJson;
use Fansipan\Body\AsMultipart;

final class ContentTypeResolver
{
    private const TYPES = [
        AsJson::class => [
            'application/json',
        ],
        AsMultipart::class => [
            'application/octet-stream',
        ],
    ];

    /**
     * @return class-string
     */
    public static function resolve(string $contentType): ?string
    {
        foreach (self::TYPES as $key => $values) {
            if (\in_array($contentType, $values)) {
                return $key;
            }
        }

        return null;
    }
}
