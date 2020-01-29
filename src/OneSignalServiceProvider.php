<?php

namespace Okn\OneSignal;

use Illuminate\Support\ServiceProvider;
use Okn\OneSignal\Console\OneSignalCommand;

class OneSignalServiceProvider extends ServiceProvider
{

    
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton('onesignal', function($app){
            return new OneSignalClient($app['config']['onesignal']);
        });
        
    }
    
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        if ($this->app->runningInConsole()) {

            // config publishing
            $configPath = __DIR__ . '/../config/onesignal.php';
            $this->publishes([$configPath => config_path('onesignal.php')], 'config');
            $this->mergeConfigFrom($configPath, 'onesignal');

            // register installation command
            $this->commands([
                OneSignalCommand::class,
            ]);
          }

    }
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['onesignal'];
    }
}
