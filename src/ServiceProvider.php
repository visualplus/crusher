<?php namespace Visualplus\Crusher;

class ServiceProvider extends Illumiate\Support\ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {

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