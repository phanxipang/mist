<?php

declare(strict_types=1);

namespace App\Providers;

use Fansipan\Mist\Generator\ConsoleGeneratorFactory;
use Fansipan\Mist\Generator\GeneratorFactoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GeneratorFactoryInterface::class, ConsoleGeneratorFactory::class);
    }
}
