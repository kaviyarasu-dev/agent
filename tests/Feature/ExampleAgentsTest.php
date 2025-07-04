<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Tests\Feature;

use Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface;
use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;
use Kaviyarasu\AIAgent\Examples\AdaptiveCodeAgent;
use Kaviyarasu\AIAgent\Examples\ContentCreatorAgent;
use Kaviyarasu\AIAgent\Examples\EmailAIAgent;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;
use Kaviyarasu\AIAgent\Tests\TestCase;
use Mockery;

class ExampleAgentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai-agent.providers.openai.api_key' => 'test-key',
            'ai-agent.providers.claude.api_key' => 'test-key',
            'ai-agent.providers.ideogram.api_key' => 'test-key',
            'ai-agent.features.provider_switching' => true,
            'ai-agent.features.model_switching' => true,
        ]);
    }

    /**
     * Test ContentCreatorAgent functionality
     */
    public function test_content_creator_agent(): void
    {
        // Mock services
        $textService = Mockery::mock(TextServiceInterface::class);
        $imageService = Mockery::mock(ImageServiceInterface::class);

        // Set up text service expectations
        $textService->shouldReceive('switchProvider')
            ->with('claude')
            ->andReturn($textService);

        $textService->shouldReceive('switchModel')
            ->with('claude-3-opus-20240229')
            ->andReturn($textService);

        $textService->shouldReceive('generateText')
            ->andReturn('Generated article content about AI');

        // Set up image service expectations
        $imageService->shouldReceive('switchProvider')
            ->with('ideogram')
            ->andReturn($imageService);

        $imageService->shouldReceive('generateImage')
            ->andReturn('generated_image.jpg');

        // Bind mocks to container
        $this->app->instance(TextServiceInterface::class, $textService);
        $this->app->instance(ImageServiceInterface::class, $imageService);

        // Create and test agent
        $agent = new ContentCreatorAgent;

        $result = $agent->execute([
            'topic' => 'Artificial Intelligence',
            'style' => 'professional',
            'include_images' => true,
        ]);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('images', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals('Generated article content about AI', $result['content']);
        $this->assertCount(1, $result['images']);
    }

    /**
     * Test EmailAIAgent with trait-based implementation
     */
    public function test_email_ai_agent(): void
    {
        // Mock text service
        $textService = Mockery::mock(TextServiceInterface::class);

        $textService->shouldReceive('generateText')
            ->with(Mockery::on(function ($params) {
                return isset($params['prompt']) &&
                    str_contains($params['prompt'], 'professional email');
            }))
            ->andReturn('Dear John, Thank you for your inquiry...');

        $this->app->instance(TextServiceInterface::class, $textService);

        // Mock ProviderFactory for trait usage
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(\Kaviyarasu\AIAgent\Contracts\ProviderInterface::class);

        $factory->shouldReceive('create')->andReturn($provider);
        $factory->shouldReceive('getAvailableProviders')->andReturn(['openai' => $provider]);

        $provider->shouldReceive('getName')->andReturn('OpenAI');
        $provider->shouldReceive('switchModel')->andReturn(true);
        $provider->shouldReceive('getCurrentModel')->andReturn('gpt-3.5-turbo');

        $this->app->instance(ProviderFactory::class, $factory);

        // Create and test agent
        $agent = new EmailAIAgent($textService);

        $result = $agent->execute([
            'recipient' => 'John Doe',
            'subject' => 'Meeting Request',
            'tone' => 'professional',
            'key_points' => ['Schedule a meeting', 'Discuss project updates'],
        ]);

        $this->assertStringContainsString('Dear John', $result);
        $this->assertStringContainsString('Thank you', $result);
    }

    /**
     * Test EmailAIAgent provider switching
     */
    public function test_email_agent_provider_switching(): void
    {
        $textService = Mockery::mock(TextServiceInterface::class);
        $this->app->instance(TextServiceInterface::class, $textService);

        // Mock ProviderFactory
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(\Kaviyarasu\AIAgent\Contracts\ProviderInterface::class);

        $factory->shouldReceive('create')
            ->with('claude')
            ->andReturn($provider);

        $provider->shouldReceive('getName')->andReturn('Claude');
        $provider->shouldReceive('getVersion')->andReturn('3.0');
        $provider->shouldReceive('isAvailable')->andReturn(true);
        $provider->shouldReceive('getCapabilities')->andReturn(['text']);

        $this->app->instance(ProviderFactory::class, $factory);

        $agent = new EmailAIAgent($textService);

        // Test provider switching
        $result = $agent->useProvider('claude');
        $this->assertInstanceOf(EmailAIAgent::class, $result);
        $this->assertEquals('claude', $agent->getCurrentProvider());
    }

    /**
     * Test AdaptiveCodeAgent with complexity-based provider selection
     */
    public function test_adaptive_code_agent_simple_task(): void
    {
        // Mock text service
        $textService = Mockery::mock(TextServiceInterface::class);

        // For simple tasks, it should use gpt-3.5-turbo
        $textService->shouldReceive('switchProvider')
            ->with('openai')
            ->andReturn($textService);

        $textService->shouldReceive('switchModel')
            ->with('gpt-3.5-turbo')
            ->andReturn($textService);

        $textService->shouldReceive('generateText')
            ->andReturn('function add(a, b) { return a + b; }');

        $this->app->instance(TextServiceInterface::class, $textService);

        $agent = new AdaptiveCodeAgent;

        $result = $agent->execute([
            'task' => 'Create a function to add two numbers',
            'language' => 'javascript',
            'complexity' => 'simple',
        ]);

        $this->assertStringContainsString('function add', $result['code']);
        $this->assertEquals('openai', $result['provider_used']);
        $this->assertEquals('gpt-3.5-turbo', $result['model_used']);
    }

    /**
     * Test AdaptiveCodeAgent with complex task
     */
    public function test_adaptive_code_agent_complex_task(): void
    {
        // Mock text service
        $textService = Mockery::mock(TextServiceInterface::class);

        // For complex tasks, it should use Claude
        $textService->shouldReceive('switchProvider')
            ->with('claude')
            ->andReturn($textService);

        $textService->shouldReceive('switchModel')
            ->with('claude-3-opus-20240229')
            ->andReturn($textService);

        $textService->shouldReceive('generateText')
            ->andReturn('class NeuralNetwork { /* complex implementation */ }');

        $this->app->instance(TextServiceInterface::class, $textService);

        $agent = new AdaptiveCodeAgent;

        $result = $agent->execute([
            'task' => 'Implement a neural network with backpropagation',
            'language' => 'python',
            'complexity' => 'complex',
        ]);

        $this->assertStringContainsString('NeuralNetwork', $result['code']);
        $this->assertEquals('claude', $result['provider_used']);
        $this->assertEquals('claude-3-opus-20240229', $result['model_used']);
    }

    /**
     * Test AdaptiveCodeAgent fallback mechanism
     */
    public function test_adaptive_code_agent_with_fallback(): void
    {
        $textService = Mockery::mock(TextServiceInterface::class);

        // First attempt with Claude fails
        $textService->shouldReceive('switchProvider')
            ->with('claude')
            ->andReturn($textService);

        $textService->shouldReceive('switchModel')
            ->with('claude-3-opus-20240229')
            ->andReturn($textService);

        $textService->shouldReceive('generateText')
            ->once()
            ->andThrow(new \Exception('Claude API error'));

        // Fallback to GPT-4 succeeds
        $textService->shouldReceive('switchProvider')
            ->with('openai')
            ->andReturn($textService);

        $textService->shouldReceive('switchModel')
            ->with('gpt-4')
            ->andReturn($textService);

        $textService->shouldReceive('generateText')
            ->once()
            ->andReturn('class ComplexAlgorithm { /* fallback implementation */ }');

        $this->app->instance(TextServiceInterface::class, $textService);

        $agent = new AdaptiveCodeAgent;

        $result = $agent->execute([
            'task' => 'Complex algorithm implementation',
            'language' => 'java',
            'complexity' => 'complex',
        ]);

        $this->assertStringContainsString('ComplexAlgorithm', $result['code']);
        $this->assertEquals('openai', $result['provider_used']);
        $this->assertEquals('gpt-4', $result['model_used']);
    }

    /**
     * Test ContentCreatorAgent with model selection strategy
     */
    public function test_content_creator_with_model_strategy(): void
    {
        config(['ai-agent.model_selection.strategy' => 'quality_optimized']);

        $textService = Mockery::mock(TextServiceInterface::class);
        $imageService = Mockery::mock(ImageServiceInterface::class);

        // Quality optimized should use best models
        $textService->shouldReceive('switchProvider')->andReturn($textService);
        $textService->shouldReceive('switchModel')
            ->with('claude-3-opus-20240229')
            ->andReturn($textService);
        $textService->shouldReceive('generateText')
            ->andReturn('High quality content');

        $imageService->shouldReceive('switchProvider')->andReturn($imageService);
        $imageService->shouldReceive('generateImage')
            ->andReturn('high_quality_image.jpg');

        $this->app->instance(TextServiceInterface::class, $textService);
        $this->app->instance(ImageServiceInterface::class, $imageService);

        $agent = new ContentCreatorAgent;

        $result = $agent->execute([
            'topic' => 'Technology',
            'style' => 'detailed',
            'include_images' => true,
        ]);

        $this->assertEquals('High quality content', $result['content']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
