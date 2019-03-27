<?php

namespace Kingjian0801\LaravelCelery;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->bind(Celery::class);

    }

    public function provides()
    {
        return [Celery::class];
    }
}