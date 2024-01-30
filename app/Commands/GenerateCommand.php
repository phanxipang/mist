<?php

declare(strict_types=1);

namespace App\Commands;

use Fansipan\Mist\Config\Author;
use Fansipan\Mist\Config\Config;
use Fansipan\Mist\Config\Output;
use Fansipan\Mist\Config\PackageMetadata;
use Fansipan\Mist\Generator\GeneratorFactoryInterface;
use Fansipan\Mist\Runner;
use function Termwind\{render};
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Filesystem\Path;

use Termwind\Termwind;

final class GenerateCommand extends Command implements PromptsForMissingInput
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'generate
                            {spec : Path to the API specification file to generate the SDK from}
                            {--o|output=./generated : The output path where the code will be created, will be created if it does not exist}
                            {--c|config= : The configuration file path.}
                            {--force : Force overwriting existing files}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generate an Fasipan SDK based on the API specification file.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(GeneratorFactoryInterface $factory)
    {
        render('<div class="m-1 px-1 bg-green-300">Fansipan Mist</div>');

        $outputDir = $this->resolvePath($this->option('output'));

        if ($config = $this->option('config')) {
            $configFile = $this->resolvePath($config);
            $this->warn('Output directory from configuration file have been overridden by output directory provided as command arguments.');
            $output = Output::fromYamlFile($configFile, ['directory' => $outputDir]);
        } else {
            $output = Output::fromArray(['directory' => $outputDir]);
        }

        try {
            $packageMetadata = PackageMetadata::fromComposer($outputDir);
        } catch (\Throwable $e) {
            $gitName = Process::run('git config user.name');
            $authorName = $this->ask('Author name', \trim($gitName->output()));

            $gitEmail = Process::run('git config user.email');
            $authorEmail = $this->ask('Author email', \trim($gitEmail->output()));

            $gitUsername = Process::run('git config remote.origin.url');
            $usernameGuess = null;

            if ($gitUsername->successful()) {
                $usernameGuess = \explode(':', $gitUsername->output())[1] ?? '';
                $usernameGuess = \dirname($usernameGuess);
                $usernameGuess = \basename($usernameGuess);
            }

            $authorUsername = $this->ask('Author username', $usernameGuess);

            $currentDirectory = \getcwd();
            $folderName = \basename($currentDirectory);

            $packageName = $this->ask('Package name', $folderName);
            $packageNamespace = Str::studly($packageName);
            $description = $this->ask('Package description', "This is my package {$packageName}");

            $vendorName = $this->ask('Vendor name', $authorUsername);

            $vendorNamespace = \ucwords($vendorName);
            $vendorNamespace = $this->ask('Vendor namespace', $vendorNamespace);

            $phpVersion = $this->ask('PHP Version constraint', '^7.2.5 | ^8.1');

            $packageMetadata = new PackageMetadata(
                $phpVersion,
                $vendorName,
                $packageName,
                $description,
                $vendorNamespace.'\\'.$packageNamespace,
                new Author($authorName, $authorEmail, $authorUsername)
            );
        }

        try {
            $spec = $this->argument('spec');

            if (\filter_var($spec, \FILTER_VALIDATE_URL) !== false) {
                // continue;
            } else {
                $spec = $this->resolvePath($spec);
            }

            $config = new Config(
                $spec, $output, $packageMetadata
            );

            $results = (new Runner($factory))->run($config);

            foreach ($results[0] ?? [] as $exception) {
                /** @var \Amp\Parallel\Worker\TaskFailureException $exception */
                $messages = [sprintf(
                    '[%s - Ln %s] %s',
                    $exception->getOriginalClassName(),
                    $exception->getOriginalLine(),
                    $exception->getOriginalMessage()
                )];

                if ($this->getOutput()->isVeryVerbose()) {
                    $messages[] = $exception->getOriginalTraceAsString();
                }

                render((string) Termwind::ul($messages));
            }

            render((string) view('message', [
                'label' => 'OK',
                'message' => sprintf('SDK generated successfully at <span class="text-blue-300">%s</span>', $config->output->directory),
            ]));

            return self::SUCCESS;
            // io()->listing(\array_map(static fn (GeneratedFile $file): string => $file->name, $results[1] ?? 0));
        } catch (\Throwable $e) {
            $this->error($e);

            return self::FAILURE;
        }
    }

    private function resolvePath(string $path): string
    {
        return Path::isAbsolute(Path::canonicalize($path)) ? $path : Path::join(\getcwd(), $path);
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
