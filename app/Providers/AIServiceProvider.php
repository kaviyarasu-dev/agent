<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\AI\Config\AIConfigManager;
use App\AI\Factory\ProviderFactory;
use App\AI\Factory\ServiceFactory;
use App\AI\Services\Core\TextService;
use App\AI\Services\Core\ImageService;
use App\AI\Services\Core\VideoService;
use App\AI\Contracts\Services\TextServiceInterface;
use App\AI\Contracts\Services\ImageServiceInterface;
use App\AI\Contracts\Services\VideoServiceInterface;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register config manager as singleton
        $this->app->singleton(AIConfigManager::class);
        
        // Register provider factory
        $this->app->singleton(ProviderFactory::class, function ($app) {
            return new ProviderFactory($app->make(AIConfigManager::class));
        });
        
        // Register service factory
        $this->app->singleton(ServiceFactory::class, function ($app) {
            return new ServiceFactory($app->make(ProviderFactory::class));
        });
        
        // Register core services
        $this->app->bind(TextServiceInterface::class, TextService::class);
        $this->app->bind(ImageServiceInterface::class, ImageService::class);
        $this->app->bind(VideoServiceInterface::class, VideoService::class);
        
        // Register module services with specific configurations
        $this->app->when(\App\AI\Services\Modules\Storyboard\CharacterService::class)
            ->needs(TextServiceInterface::class)
            ->give(function ($app) {
                $service = $app->make(TextService::class);
                // Configure specific provider if needed
                if (config('ai.storyboard.character_provider')) {
                    $service->setProvider(config('ai.storyboard.character_provider'));
                }
                return $service;
            });
        
        $this->app->when(\App\AI\Services\Modules\Storyboard\ShotService::class)
            ->needs(TextServiceInterface::class)
            ->give(function ($app) {
                $service = $app->make(TextService::class);
                // Configure specific provider if needed
                if (config('ai.storyboard.shot_provider')) {
                    $service->setProvider(config('ai.storyboard.shot_provider'));
                }
                return $service;
            });
    }
    
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/ai.php' => config_path('ai.php'),
        ], 'ai-config');
        
        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add any AI-related commands here
            ]);
        }
    }
}