<?php

namespace App\Providers;

use App\Services\ShopContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShopContext::class);
    }

    public function boot(): void
    {
        //
    }
}
