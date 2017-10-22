<?php

namespace Tutorigo\LaravelMacroHelper;

use Illuminate\Support\ServiceProvider;

class IdeMacrosServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/ide-macros.php' => config_path('ide-macros.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\MacrosCommand::class,
            ]);
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ide-macros.php', 'ide-macros'
        );
    }
}
