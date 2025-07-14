<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent;

use Kaviyarasu\AIAgent\Commands\AIAgentCommand;
use Kaviyarasu\AIAgent\Commands\ListProvidersCommand;
use Kaviyarasu\AIAgent\Commands\MakeAiAgentCommand;
use Kaviyarasu\AIAgent\Config\AIConfigManager;
use Kaviyarasu\AIAgent\Contracts\Formatters\ResponseFormatterInterface;
use Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface;
use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;
use Kaviyarasu\AIAgent\Contracts\Services\VideoServiceInterface;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;
use Kaviyarasu\AIAgent\Factory\ServiceFactory;
use Kaviyarasu\AIAgent\Formatters\ImageResponseFormatter;
use Kaviyarasu\AIAgent\Formatters\ResponseFormatterFactory;
use Kaviyarasu\AIAgent\Formatters\TextResponseFormatter;
use Kaviyarasu\AIAgent\Services\Core\ImageService;
use Kaviyarasu\AIAgent\Services\Core\TextService;
use Kaviyarasu\AIAgent\Services\Core\VideoService;
use Kaviyarasu\AIAgent\Services\Modules\Storyboard\CharacterService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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

        // Register Response Formatter Factory
        $this->app->singleton(ResponseFormatterFactory::class, function ($app) {
            $factory = new ResponseFormatterFactory();
            
            // Register additional formatters if needed
            $this->registerCustomFormatters($factory);
            
            return $factory;
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

        // Register formatter bindings
        $this->registerFormatters();

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

    protected function registerFormatters(): void
    {
        // Register individual formatters
        $this->app->bind('ai-agent.formatter.text', TextResponseFormatter::class);
        $this->app->bind('ai-agent.formatter.image', ImageResponseFormatter::class);

        // Register formatter interface binding
        $this->app->bind(ResponseFormatterInterface::class, function ($app) {
            // Default to text formatter, can be overridden
            return $app->make('ai-agent.formatter.text');
        });
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

    /**
     * Register custom formatters
     */
    protected function registerCustomFormatters(ResponseFormatterFactory $factory): void
    {
        // Register any custom formatters here
        // Example:
        // $factory->register('custom_text', new CustomTextResponseFormatter());
        
        // Allow users to extend formatters via configuration
        $customFormatters = config('ai-agent.custom_formatters', []);
        
        foreach ($customFormatters as $type => $formatterClass) {
            if (class_exists($formatterClass)) {
                $factory->register($type, new $formatterClass());
            }
        }
    }

    /**
     * Register facade aliases
     */
    public function packageBooted(): void
    {
        // Register additional aliases if needed
        $this->app->alias(AIAgent::class, 'ai-agent');
        $this->app->alias(ResponseFormatterFactory::class, 'ai-agent.formatter.factory');
    }
}
