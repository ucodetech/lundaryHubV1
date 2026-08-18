<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Laravel\Facades\Window;
use Native\Laravel\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider extends ServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            Window::open()
                ->title('LaundryHub Platform')
                ->width(1280)
                ->height(800)
                ->minWidth(400)
                ->minHeight(600)
                ->rememberState();
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
