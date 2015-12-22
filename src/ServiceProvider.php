<?php namespace Visualplus\Crusher;

class ServiceProvider extends Illumiate\Support\ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/crusher.php' => config_path('crusher.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../migrations/' => database_path('migrations'),
        ], 'migrations');
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton('crusher', function($app) {
            return new \Visualplus\Crusher\Crusher;
        });
    }
}