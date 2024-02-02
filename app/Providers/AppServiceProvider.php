<?php

declare(strict_types=1);

namespace App\Providers;

use Fansipan\Mist\Generator\ConsoleGeneratorFactory;
use Fansipan\Mist\Generator\GeneratorFactoryInterface;
use Illuminate\Support\ServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcher;

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
        $this->app->singleton(SymfonyEventDispatcher::class, static fn ($app) => $app->make(EventDispatcher::class));
        $this->app->alias(SymfonyEventDispatcher::class, EventDispatcherInterface::class);
    }
}
