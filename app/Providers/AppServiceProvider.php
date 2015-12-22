<?php

namespace App\Providers;

use Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('*', function ($view) {
            $view->with('auth', Auth::user());
        });
    }

    /**w
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
