<?php

namespace WACloud\LaravelWACloud;

use Illuminate\Support\ServiceProvider;

class WACloudServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/wacloud.php',
            'wacloud'
        );

        // Register WACloud client as singleton
        $this->app->singleton('wacloud', function ($app) {
            $config = $app['config']['wacloud'];
            
            return new WACloudClient(
                $config['api_key'],
                $config['base_url'] ?? 'https://app.wacloud.id/api/v1',
                $config['timeout'] ?? 30
            );
        });

        // Register alias
        $this->app->alias('wacloud', WACloudClient::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__ . '/../config/wacloud.php' => config_path('wacloud.php'),
        ], 'wacloud-config');

        // Publish migrations if needed in the future
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}

