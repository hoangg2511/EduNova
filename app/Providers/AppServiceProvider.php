<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Initialize Firebase Admin SDK
        $firebaseCredentials = storage_path('app/private/edunova-60ce7-firebase-adminsdk-fbsvc-ab22cbb1f0.json');
        if (file_exists($firebaseCredentials)) {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $firebaseCredentials);
        }

        if ($this->app->environment('production') || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
    }
}
