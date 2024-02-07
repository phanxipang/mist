<?php

declare(strict_types=1);

namespace App\Commands;

use Assert\Assertion;
use Fansipan\Mist\Config\Author;
use Fansipan\Mist\Config\Config;
use Fansipan\Mist\Config\Output;
use Fansipan\Mist\Config\PackageMetadata;
use Fansipan\Mist\Event\SdkFileGenerated;
use Fansipan\Mist\Generator\GeneratorFactoryInterface;
use Fansipan\Mist\GeneratorMessage;
use Fansipan\Mist\Runner;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Prompts;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Path;

final class GenerateCommand extends Command implements PromptsForMissingInput
{
    use CommandTrait;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'generate
                            {spec : Path to the API specification file to generate the SDK from}
                            {--o|output=./generated : The output path where the code will be created, will be created if it does not exist}
                            {--c|config= : The configuration file path.}
                            {--f|force : Force overwriting existing files}';

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
    public function handle(
        GeneratorFactoryInterface $factory,
        EventDispatcherInterface $event,
        Repository $config,
    ) {
        $this->render('<div class="m-1 px-1 bg-green-300">'.sprintf('Fansipan Mist %s', $config->get('app.version')).'</div>');

        // $this->bindEvents($event);

        $outputDir = $this->resolvePath($this->option('output'));

        try {
            $config = new Config(
                $this->resolveSpec(),
                $this->resolveOutput($outputDir),
                $this->resolvePackageMetadata($outputDir),
                (bool) $this->option('force')
            );

            $spec = Prompts\spin(static fn () => Runner::loadSpec($config->spec), 'Loading spec...');

            $generators = $factory->create($spec);

            /** @var Prompts\Progress $progress */
            $progress = Prompts\progress('Generating...', \iterator_count($generators));

            $event->addListener(GeneratorMessage::class, fn (GeneratorMessage $event) => $progress
                ->hint($event->file->filename)
                ->advance());

            $results = (new Runner($generators, $event))
                ->generate($config);

            $progress->label('Done')->finish();

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

                $this->getOutput()->error($messages);
            }

            $this->getOutput()->writeln('');

            $this->render(view('message', [
                'label' => 'OK',
                'message' => sprintf('SDK generated successfully at <span class="text-blue-300">%s</span>', $config->output->directory),
            ]));

            if ($this->getOutput()->isVerbose()) {
                $this->render(view('files', [
                    'files' => $results[1] ?? [],
                    'outputDir' => $outputDir,
                ]));
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e);

            return self::FAILURE;
        }
    }

    private function bindEvents(EventDispatcherInterface $event): void
    {
        // $progress = Prompts\progress('Generating..', 20);
        // $event->addListener(SdkFileGenerated::class, static fn () => $progress->advanced());
    }

    private function resolvePath(string $path): string
    {
        return Path::isAbsolute(Path::canonicalize($path)) ? $path : Path::join(\getcwd(), $path);
    }

    private function resolveSpec(): string
    {
        $spec = $this->argument('spec');

        if (\filter_var($spec, \FILTER_VALIDATE_URL) !== false) {
            return $spec;
        }

        return $this->resolvePath($spec);
    }

    private function resolveOutput(string $outputDir): Output
    {
        $config = $this->option('config');

        if (! $config) {
            return Output::fromArray(['directory' => $outputDir]);
        }

        $configFile = $this->resolvePath($config);
        $this->warn('Output directory from configuration file have been overridden by output directory provided as command arguments.');

        return Output::fromYamlFile($configFile, ['directory' => $outputDir]);
    }

    private function resolvePackageMetadata(string $outputDir): PackageMetadata
    {
        try {
            return PackageMetadata::fromComposer($outputDir);
        } catch (\Throwable) {
            $gitName = Process::run('git config user.name');
            $authorName = Prompts\text(
                label: 'Author name',
                placeholder: 'Please enter your name',
                default: \trim($gitName->output()),
                required: true,
            );

            $gitEmail = Process::run('git config user.email');
            $authorEmail = Prompts\text(
                label: 'Author email',
                placeholder: 'Please enter your e-mail address',
                default: \trim($gitEmail->output()),
                required: true,
                validate: self::assert(static fn (string $value) => Assertion::email($value)),
            );

            $gitUsername = Process::run('git config remote.origin.url');
            $usernameGuess = null;

            if ($gitUsername->successful()) {
                $usernameGuess = \explode(':', $gitUsername->output())[1] ?? '';
                $usernameGuess = \dirname($usernameGuess);
                $usernameGuess = \basename($usernameGuess);
            }

            $authorUsername = Prompts\text(
                label: 'Author username',
                placeholder: 'Please enter your Git username',
                default: $usernameGuess,
                required: true,
                validate: self::assert(static fn (string $value) => Assertion::alnum($value)),
            );

            $currentDirectory = \getcwd();
            $folderName = \basename($currentDirectory);

            $packageName = Prompts\text(
                label: 'Package name',
                placeholder: 'Please enter your package name',
                default: $folderName,
                required: true,
                validate: self::assert(static fn (string $value) => Assertion::alnum($value)),
            );
            $packageNamespace = Str::studly($packageName);

            $description = Prompts\text(
                label: 'Package description',
                placeholder: 'Please enter your package description',
                default: "This is my package {$packageName}",
                required: true,
            );

            $vendorName = Prompts\text(
                label: 'Vendor name',
                placeholder: 'Please enter your vendor name',
                default: $authorUsername,
                required: true,
                validate: self::assert(static fn (string $value) => Assertion::alnum($value)),
            );

            $vendorNamespace = \ucwords($vendorName);
            $vendorNamespace = Prompts\text(
                label: 'Vendor namespace',
                placeholder: 'Please enter your vendor namespace',
                default: $vendorNamespace,
                required: true,
            );

            $phpVersion = Prompts\text(
                label: 'PHP Version constraint',
                placeholder: 'Please enter your PHP version constraints',
                default: '^7.2.5 | ^8.1',
                required: true,
                hint: 'Your supported PHP version(s)'
            );

            return new PackageMetadata(
                $phpVersion,
                $vendorName,
                $packageName,
                $description,
                $vendorNamespace.'\\'.$packageNamespace,
                new Author($authorName, $authorEmail, $authorUsername)
            );
        }
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
