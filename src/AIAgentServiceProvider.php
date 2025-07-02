<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Kaviyarasu\AIAgent\Commands\AIAgentCommand;
use Kaviyarasu\AIAgent\Commands\MakeAiAgentCommand;
use Kaviyarasu\AIAgent\Commands\ListProvidersCommand;
use Kaviyarasu\AIAgent\Config\AIConfigManager;
use Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface;
use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;
use Kaviyarasu\AIAgent\Contracts\Services\VideoServiceInterface;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;
use Kaviyarasu\AIAgent\Factory\ServiceFactory;
use Kaviyarasu\AIAgent\Services\Core\ImageService;
use Kaviyarasu\AIAgent\Services\Core\TextService;
use Kaviyarasu\AIAgent\Services\Core\VideoService;
use Kaviyarasu\AIAgent\Services\Modules\Storyboard\CharacterService;

class AIAgentServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('ai-agent')
            ->hasConfigFile('ai-agent')
            ->hasViews()
            ->hasMigration('create_ai_agent_table')
            ->hasCommands([
                AIAgentCommand::class,
                MakeAiAgentCommand::class,
                ListProvidersCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // Register singletons
        $this->app->singleton(AIConfigManager::class);

        $this->app->singleton(ProviderFactory::class, function ($app) {
            return new ProviderFactory(
                $app->make(AIConfigManager::class)
            );
        });

        $this->app->singleton(ServiceFactory::class, function ($app) {
            return new ServiceFactory(
                $app->make(ProviderFactory::class),
                $app->make(AIConfigManager::class)
            );
        });

        // Register main AI service
        $this->app->singleton(AIAgent::class, function ($app) {
            return new AIAgent(
                $app->make(ServiceFactory::class)
            );
        });

        // Register service bindings
        $this->registerServices();

        // Register module services
        $this->registerModuleServices();
    }

    protected function registerServices(): void
    {
        $this->app->bind(
            TextServiceInterface::class,
            TextService::class
        );

        $this->app->bind(
            ImageServiceInterface::class,
            ImageService::class
        );

        $this->app->bind(
            VideoServiceInterface::class,
            VideoService::class
        );
    }

    protected function registerModuleServices(): void
    {
        // Storyboard Character Service
        $this->app->when(CharacterService::class)
            ->needs(TextServiceInterface::class)
            ->give(function ($app) {
                $service = $app->make(TextService::class);
                if (config('ai-agent.modules.storyboard.character_provider')) {
                    $service->setProvider(config('ai-agent.modules.storyboard.character_provider'));
                }

                return $service;
            });

        // Storyboard Shot Service
        $this->app->when(\Kaviyarasu\AIAgent\Services\Modules\Storyboard\ShotService::class)
            ->needs(ImageServiceInterface::class)
            ->give(function ($app) {
                $service = $app->make(ImageService::class);
                if (config('ai-agent.modules.storyboard.shot_provider')) {
                    $service->setProvider(config('ai-agent.modules.storyboard.shot_provider'));
                }

                return $service;
            });
    }
}
