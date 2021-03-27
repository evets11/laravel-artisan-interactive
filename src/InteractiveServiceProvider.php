<?php

namespace Evets11;

use Illuminate\Support\ServiceProvider;

class InteractiveServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->singleton('command.evets11.interactive', function ($app) {
            return $app['Evets11\Commands\InteractiveCommand'];
        });

        $this->commands('command.evets11.interactive');
    }
}
