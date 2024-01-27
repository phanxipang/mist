<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use cebe\openapi\spec\Info;
use Fansipan\Mist\Config\Config;
use Fansipan\Mist\GeneratedFile;

final class Composer implements GeneratorInterface
{
    public function __construct(private readonly ?Info $info = null)
    {
    }

    public function generate(Config $config): GeneratedFile
    {
        $content = \json_encode([
            'name' => $name = sprintf('%s/%s', $config->package->vendor, $config->package->name),
            'description' => $config->package->description ?: $this->info?->description,
            'homepage' => sprintf('https://github.com/%s', $name),
            'license' => $this->info?->license?->name ?? 'MIT',
            'require' => [
                'php' => $config->package->phpVersion,
                'fansipan/fansipan' => '^0.8',
            ],
            'autoload' => [
                'psr-4' => [
                    $config->package->namespace.'\\' => '/'.trim($config->output->paths->src, '/'),
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    $config->package->namespace.'\\Tests\\' => '/'.trim($config->output->paths->tests, '/'),
                ],
            ],
            'config' => [
                'sort-packages' => true,
                'allow-plugins' => [
                    'php-http/discovery' => true,
                ],
            ],
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);

        return new GeneratedFile(
            [$config->output->directory, 'composer.json'],
            $content ?: '{}'
        );
    }
}
