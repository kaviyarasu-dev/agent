<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Tests\Feature;

use App\Agents\Blog\BlogAiAgentAdvanced;
use App\Agents\Blog\BlogAiAgentWithTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use WebsiteLearners\AIAgent\Tests\TestCase;
use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;

class ProviderModelSwitchingTest extends TestCase
{
    /**
     * Test BlogAiAgentAdvanced provider switching.
     */
    public function test_advanced_agent_can_switch_providers(): void
    {
        // Mock dependencies
        $textService = $this->createMock(TextServiceInterface::class);
        $providerFactory = $this->createMock(ProviderFactory::class);

        // Create mock provider
        $mockProvider = $this->createMock(\WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface::class);
        $mockProvider->method('switchModel')->willReturn(true);
        $mockProvider->method('getCurrentModel')->willReturn('test-model');

        $providerFactory->method('create')->willReturn($mockProvider);

        // Test agent
        $agent = new BlogAiAgentAdvanced($textService, $providerFactory);

        // Test provider switching
        $result = $agent->switchProvider('openai');
        $this->assertInstanceOf(BlogAiAgentAdvanced::class, $result);
        $this->assertEquals('openai', $agent->getCurrentProvider());

        // Test model switching
        $result = $agent->switchModel('gpt-4');
        $this->assertInstanceOf(BlogAiAgentAdvanced::class, $result);
    }

    /**
     * Test trait-based agent functionality.
     */
    public function test_trait_based_agent_switching(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);

        // Mock app() calls for ProviderFactory
        $this->app->bind(ProviderFactory::class, function () {
            $factory = $this->createMock(ProviderFactory::class);

            $mockProvider = $this->createMock(\WebsiteLearners\AIAgent\Contracts\ProviderInterface::class);
            $mockProvider->method('switchModel')->willReturn(true);
            $mockProvider->method('getCurrentModel')->willReturn('test-model');
            $mockProvider->method('getName')->willReturn('Test Provider');
            $mockProvider->method('getVersion')->willReturn('1.0');
            $mockProvider->method('isAvailable')->willReturn(true);
            $mockProvider->method('getCapabilities')->willReturn(['text']);

            $factory->method('create')->willReturn($mockProvider);
            $factory->method('getAvailableProviders')->willReturn([
                'test' => $mockProvider
            ]);

            return $factory;
        });

        $agent = new BlogAiAgentWithTrait($textService);

        // Test provider switching
        $result = $agent->useProvider('openai');
        $this->assertInstanceOf(BlogAiAgentWithTrait::class, $result);
        $this->assertEquals('openai', $agent->getCurrentProvider());

        // Test getting available providers
        $providers = $agent->getAvailableProviders('text');
        $this->assertIsArray($providers);
    }

    /**
     * Test executeWith method for temporary configuration.
     */
    public function test_execute_with_temporary_configuration(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);
        $providerFactory = $this->createMock(ProviderFactory::class);

        $mockProvider = $this->createMock(\WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface::class);
        $mockProvider->method('switchModel')->willReturn(true);
        $mockProvider->method('getCurrentModel')->willReturn('original-model');

        $providerFactory->method('create')->willReturn($mockProvider);

        $textService->expects($this->once())
            ->method('generateText')
            ->willReturn('Generated content');

        $agent = new BlogAiAgentAdvanced($textService, $providerFactory);

        // Execute with temporary configuration
        $result = $agent->executeWith(
            ['prompt' => 'Test', 'options' => []],
            'openai',
            'gpt-4'
        );

        $this->assertEquals('Generated content', $result);
    }

    /**
     * Test error handling for invalid providers.
     */
    public function test_invalid_provider_throws_exception(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);
        $providerFactory = $this->createMock(ProviderFactory::class);

        $providerFactory->method('create')
            ->willThrowException(new \InvalidArgumentException('Provider not found'));

        $agent = new BlogAiAgentAdvanced($textService, $providerFactory);

        $this->expectException(\InvalidArgumentException::class);
        $agent->switchProvider('invalid-provider');
    }

    /**
     * Test getting available models.
     */
    public function test_get_available_models(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);
        $providerFactory = $this->createMock(ProviderFactory::class);

        // Create a mock provider with reflection access
        $mockProvider = new class implements \WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface {
            protected array $supportedModels = ['model-1', 'model-2', 'model-3'];

            public function generateText(array $params): string
            {
                return '';
            }
            public function streamText(array $params): iterable
            {
                yield '';
            }
            public function getMaxTokens(): int
            {
                return 4096;
            }
            public function getName(): string
            {
                return 'Test';
            }
            public function getVersion(): string
            {
                return '1.0';
            }
            public function supports(string $capability): bool
            {
                return true;
            }
            public function getCapabilities(): array
            {
                return ['text'];
            }
            public function isAvailable(): bool
            {
                return true;
            }
        };

        $providerFactory->method('create')->willReturn($mockProvider);

        $agent = new BlogAiAgentAdvanced($textService, $providerFactory);
        $agent->switchProvider('test');

        $models = $agent->getAvailableModels();
        $this->assertIsArray($models);
        $this->assertCount(3, $models);
        $this->assertContains('model-1', $models);
    }
}
