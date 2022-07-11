<?php

namespace Ggmanuilov\LaravelSlaveMonitor\Providers;

use Ggmanuilov\LaravelSlaveMonitor\Commands\ReplicationMonitor;
use Ggmanuilov\LaravelSlaveMonitor\DbReadConnectionFactory;

class DbReadMonitorServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            dirname(__DIR__) . '/config' => config_path()
        ], 'database-monitor');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ReplicationMonitor::class,
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
        $this->mergeConfigFrom(__DIR__.'/../config/database-monitor.php', 'database-monitor');

        $this->app->singleton('db.factory', DbReadConnectionFactory::class);
    }
}