<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MessagingConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config([
            'messaging.redis.host' => config('messaging.redis.host') ?: config('database.redis.default.host'),
            'messaging.redis.port' => config('messaging.redis.port') ?: config('database.redis.default.port'),
        ]);
    }
}
