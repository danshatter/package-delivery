<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Sms\Dispatcher;
use App\Services\Sms\AfricaIsTalking;
use App\Services\Settings\Application;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Dispatcher::class, fn($app) => new AfricaIsTalking);
    }
}
