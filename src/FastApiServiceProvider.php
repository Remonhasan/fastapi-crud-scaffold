<?php

namespace FastApi\CrudScaffold;

use FastApi\CrudScaffold\Console\Commands\MakeFastApiCommand;
use Illuminate\Support\ServiceProvider;

class FastApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/fastapi.php', 'fastapi');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/fastapi.php' => config_path('fastapi.php'),
        ], 'fastapi-config');

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/fastapi'),
        ], 'fastapi-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeFastApiCommand::class,
            ]);
        }
    }
}
