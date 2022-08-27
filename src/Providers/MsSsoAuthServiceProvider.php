<?php

namespace GumarovDev\MicrosoftSsoAuth\Providers;

use Illuminate\Support\ServiceProvider;

class MsSsoAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/ms-auth.php' => config_path('ms_auth.php'),
        ]);
    }
}
