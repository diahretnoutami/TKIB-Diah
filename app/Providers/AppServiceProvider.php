<?php

namespace App\Providers;

use Carbon\Carbon;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrtuNotification; // <<< TAMBAHKAN IMPORT INI

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
    public function boot()
    {
        Carbon::setLocale('id');

        Notification::extend('fcm_custom', function ($app) {
            return new class {
                public function send($notifiable, OrtuNotification $notification)
                {
                    $notification->toFcmCustom($notifiable);
                }
            };
        });
    }
}