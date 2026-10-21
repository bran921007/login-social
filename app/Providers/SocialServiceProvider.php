<?php

namespace App\Providers;

use Hybridauth\HttpClient\Curl;
use Hybridauth\HttpClient\HttpClientInterface;
use Illuminate\Support\ServiceProvider;

final class SocialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HttpClientInterface::class, Curl::class);
    }
}
