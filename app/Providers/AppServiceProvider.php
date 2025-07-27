<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Debate;
use App\Policies\DebatePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 新しい接続管理システムの依存関係注入
        $this->app->singleton(\App\Services\Connection\ConnectionStateManager::class);
        $this->app->singleton(\App\Services\Connection\ConnectionLogger::class);
        $this->app->singleton(\App\Services\Connection\DisconnectionHandler::class);
        $this->app->singleton(\App\Services\Connection\ReconnectionHandler::class);
        $this->app->singleton(\App\Services\Connection\ConnectionAnalyzer::class);
        $this->app->singleton(\App\Services\Connection\ConnectionCoordinator::class);

        // OTP Service binding
        $this->app->bind(\App\Contracts\OtpServiceInterface::class, \App\Services\OtpService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    protected $policies = [
        Debate::class => DebatePolicy::class,
    ];
}
