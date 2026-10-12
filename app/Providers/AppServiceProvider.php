<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\PersonalAccessToken;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

public function boot(): void
{
    // Force Sanctum à accepter que les identifiants des modèles liés soient des chaînes (UUID)
    Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
}

}
