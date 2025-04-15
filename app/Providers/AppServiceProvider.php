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
        //
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
