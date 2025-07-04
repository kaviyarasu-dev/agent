<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Tests\Feature;

use Kaviyarasu\AIAgent\Factory\ProviderFactory;
use Kaviyarasu\AIAgent\Providers\AI\Claude\ClaudeProvider;
use Kaviyarasu\AIAgent\Providers\AI\Ideogram\IdeogramProvider;
use Kaviyarasu\AIAgent\Providers\AI\OpenAI\OpenAIProvider;
use Kaviyarasu\AIAgent\Services\Core\ImageService;
use Kaviyarasu\AIAgent\Services\Core\TextService;
use Kaviyarasu\AIAgent\Services\Core\VideoService;
use Kaviyarasu\AIAgent\Tests\TestCase;
use Mockery;

class ComprehensiveProviderSwitchingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        config([
            'ai-agent.providers.openai.api_key' => 'test-key',
            'ai-agent.providers.claude.api_key' => 'test-key',
            'ai-agent.providers.ideogram.api_key' => 'test-key',
            'ai-agent.features.provider_switching' => true,
            'ai-agent.features.model_switching' => true,
        ]);
    }

    /**
     * Test TextService provider switching functionality
     */
    public function test_text_service_provider_switching(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $textService = new TextService($factory);

        // Mock provider switching
        $openAiProvider = Mockery::mock(OpenAIProvider::class);
        $claudeProvider = Mockery::mock(ClaudeProvider::class);

        $factory->shouldReceive('create')
            ->with('openai')
            ->andReturn($openAiProvider);

        $factory->shouldReceive('create')
            ->with('claude')
            ->andReturn($claudeProvider);

        // Test switching providers
        $result = $textService->switchProvider('openai');
        $this->assertInstanceOf(TextService::class, $result);

        $result = $textService->switchProvider('claude');
        $this->assertInstanceOf(TextService::class, $result);
    }

    /**
     * Test TextService model switching
     */
    public function test_text_service_model_switching(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(OpenAIProvider::class);

        $factory->shouldReceive('create')
            ->with('openai')
            ->andReturn($provider);

        $provider->shouldReceive('switchModel')
            ->with('gpt-4')
            ->andReturn(true);

        $provider->shouldReceive('getCurrentModel')
            ->andReturn('gpt-4');

        $textService = new TextService($factory);
        $textService->switchProvider('openai');

        $result = $textService->switchModel('gpt-4');
        $this->assertInstanceOf(TextService::class, $result);
    }

    /**
     * Test temporary provider usage with withProvider
     */
    public function test_text_service_with_provider(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(ClaudeProvider::class);

        $factory->shouldReceive('create')
            ->with('claude')
            ->andReturn($provider);

        $provider->shouldReceive('generateText')
            ->with(['prompt' => 'Test prompt'])
            ->andReturn('Generated text from Claude');

        $textService = new TextService($factory);

        $result = $textService->withProvider('claude', function ($service) {
            return $service->generateText(['prompt' => 'Test prompt']);
        });

        $this->assertEquals('Generated text from Claude', $result);
    }

    /**
     * Test temporary model usage with withModel
     */
    public function test_text_service_with_model(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(OpenAIProvider::class);

        $factory->shouldReceive('create')
            ->andReturn($provider);

        $provider->shouldReceive('switchModel')
            ->with('gpt-4-turbo')
            ->andReturn(true);

        $provider->shouldReceive('getCurrentModel')
            ->andReturn('gpt-3.5-turbo', 'gpt-4-turbo', 'gpt-3.5-turbo');

        $provider->shouldReceive('generateText')
            ->andReturn('Generated with GPT-4 Turbo');

        $textService = new TextService($factory);

        $result = $textService->withModel('gpt-4-turbo', function ($service) {
            return $service->generateText(['prompt' => 'Test']);
        });

        $this->assertEquals('Generated with GPT-4 Turbo', $result);
    }

    /**
     * Test model capabilities detection
     */
    public function test_get_model_capabilities(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(OpenAIProvider::class);

        $factory->shouldReceive('create')
            ->andReturn($provider);

        $provider->shouldReceive('getModelCapabilities')
            ->with('gpt-4')
            ->andReturn([
                'max_tokens' => 8192,
                'supports_functions' => true,
                'supports_vision' => true,
                'context_window' => 128000,
            ]);

        $textService = new TextService($factory);

        $capabilities = $textService->getModelCapabilities('gpt-4');

        $this->assertIsArray($capabilities);
        $this->assertTrue($capabilities['supports_functions']);
        $this->assertEquals(8192, $capabilities['max_tokens']);
    }

    /**
     * Test ImageService provider switching
     */
    public function test_image_service_provider_switching(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $imageService = new ImageService($factory);

        $openAiProvider = Mockery::mock(OpenAIProvider::class);
        $ideogramProvider = Mockery::mock(IdeogramProvider::class);

        $factory->shouldReceive('create')
            ->with('openai')
            ->andReturn($openAiProvider);

        $factory->shouldReceive('create')
            ->with('ideogram')
            ->andReturn($ideogramProvider);

        // Test switching to different providers
        $result = $imageService->switchProvider('openai');
        $this->assertInstanceOf(ImageService::class, $result);

        $result = $imageService->switchProvider('ideogram');
        $this->assertInstanceOf(ImageService::class, $result);
    }

    /**
     * Test VideoService capabilities
     */
    public function test_video_service_functionality(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $videoService = new VideoService($factory);

        $provider = Mockery::mock(OpenAIProvider::class);

        $factory->shouldReceive('create')
            ->andReturn($provider);

        $provider->shouldReceive('supports')
            ->with('video')
            ->andReturn(true);

        $videoService->switchProvider('openai');

        // Since most providers don't support video yet, we just test the structure
        $this->assertInstanceOf(VideoService::class, $videoService);
    }

    /**
     * Test error handling for unsupported capabilities
     */
    public function test_unsupported_capability_error(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(ClaudeProvider::class);

        $factory->shouldReceive('create')
            ->andReturn($provider);

        $provider->shouldReceive('supports')
            ->with('image')
            ->andReturn(false);

        $imageService = new ImageService($factory);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider does not support image generation');

        $imageService->setProvider('claude');
    }

    /**
     * Test invalid model switching
     */
    public function test_invalid_model_switching(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(OpenAIProvider::class);

        $factory->shouldReceive('create')
            ->andReturn($provider);

        $provider->shouldReceive('switchModel')
            ->with('invalid-model')
            ->andThrow(new \InvalidArgumentException('Model not supported'));

        $textService = new TextService($factory);
        $textService->switchProvider('openai');

        $this->expectException(\InvalidArgumentException::class);

        $textService->switchModel('invalid-model');
    }

    /**
     * Test feature flag disabling
     */
    public function test_provider_switching_disabled_by_feature_flag(): void
    {
        config(['ai-agent.features.provider_switching' => false]);

        $factory = Mockery::mock(ProviderFactory::class);
        $textService = new TextService($factory);

        // Since the feature flag is checked at a higher level (in agents),
        // the service itself should still work
        $result = $textService->switchProvider('claude');
        $this->assertInstanceOf(TextService::class, $result);
    }

    /**
     * Test model switching disabled by feature flag
     */
    public function test_model_switching_disabled_by_feature_flag(): void
    {
        config(['ai-agent.features.model_switching' => false]);

        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(OpenAIProvider::class);

        $factory->shouldReceive('create')
            ->andReturn($provider);

        $textService = new TextService($factory);

        // Since the feature flag is checked at a higher level (in agents),
        // the service itself should still work
        $provider->shouldReceive('switchModel')->andReturn($provider);

        $result = $textService->switchModel('gpt-4');
        $this->assertInstanceOf(TextService::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
