<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\LLMServiceInterface;
use App\Services\AI\GeminiNeuronService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LLMServiceInterface::class, function ($app) {
            return new GeminiNeuronService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forziamo il legame anche in fase di boot per sicurezza di risoluzione dei controller
        $this->app->bind(LLMServiceInterface::class, GeminiNeuronService::class);
    }
}
