<?php

namespace Kaviyarasu\AIAgent\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kaviyarasu\AIAgent\AIAgentServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Kaviyarasu\\AIAgent\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            AIAgentServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('ai-agent.default_provider', 'claude');
        config()->set('ai-agent.providers.claude.api_key', 'test-api-key');
        config()->set('ai-agent.providers.openai.api_key', 'test-api-key');
        config()->set('ai-agent.providers.ideogram.api_key', 'test-api-key');

        // Run migrations if needed
        /*
        $migration = include __DIR__.'/../database/migrations/create_ai_agent_table.php.stub';
        $migration->up();
        */
    }
}
