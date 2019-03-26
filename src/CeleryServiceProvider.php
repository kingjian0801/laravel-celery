<?php

namespace Kingjian0801\LaravelCelery;

class CeleryServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(Celery::class);

        $this->app->alias(Celery::class);
    }

    public function provides()
    {
        return [Celery::class];
    }
}