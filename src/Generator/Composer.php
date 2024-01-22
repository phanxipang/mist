<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use Fansipan\Mist\ValueObject\Config;
use Fansipan\Mist\ValueObject\GeneratedFile;

final class Composer implements GeneratorInterface
{
    public function __construct(private readonly string $phpVersion = '^8.1')
    {
    }

    public function generate(Config $config): GeneratedFile
    {
        $content = \json_encode([
            'name' => $name = sprintf('%s/%s', $config->package->vendor, $config->package->name),
            'description' => $config->package->description,
            'homepage' => sprintf('https://github.com/%s', $name),
            'license' => 'MIT',
            'require' => [
                'php' => $this->phpVersion,
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
