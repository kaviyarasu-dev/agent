<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Tests\Feature;

use Kaviyarasu\AIAgent\Tests\TestCase;
use Kaviyarasu\AIAgent\Services\Core\TextService;
use Kaviyarasu\AIAgent\Services\Core\ImageService;
use Kaviyarasu\AIAgent\Services\Core\VideoService;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;
use Kaviyarasu\AIAgent\Examples\EmailAIAgent;
use Kaviyarasu\AIAgent\Examples\ContentCreatorAgent;
use Mockery;

class SimpleProviderSwitchingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai-agent.providers.openai.api_key' => 'test-key',
            'ai-agent.providers.claude.api_key' => 'test-key',
            'ai-agent.features.provider_switching' => true,
            'ai-agent.features.model_switching' => true,
        ]);
    }

    public function test_text_service_can_switch_providers(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);

        $openaiProvider = Mockery::mock(
            \Kaviyarasu\AIAgent\Contracts\ProviderInterface::class . ',' .
                \Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface::class
        );

        $claudeProvider = Mockery::mock(
            \Kaviyarasu\AIAgent\Contracts\ProviderInterface::class . ',' .
                \Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface::class
        );

        $openaiProvider->shouldReceive('getName')->andReturn('openai');
        $claudeProvider->shouldReceive('getName')->andReturn('claude');

        $factory->shouldReceive('create')
            ->with('openai')
            ->andReturn($openaiProvider);

        $factory->shouldReceive('create')
            ->with('claude')
            ->andReturn($claudeProvider);

        $textService = new TextService($factory);

        $result = $textService->switchProvider('openai');
        $this->assertSame($textService, $result);
        $this->assertEquals('openai', $textService->getCurrentProvider());

        $result = $textService->switchProvider('claude');
        $this->assertSame($textService, $result);
        $this->assertEquals('claude', $textService->getCurrentProvider());
    }

    public function test_email_agent_provider_switching(): void
    {
        if (!class_exists(\Kaviyarasu\AIAgent\Examples\EmailAIAgent::class)) {
            $this->markTestSkipped('EmailAIAgent example not yet implemented');
        }

        $textService = Mockery::mock(\Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface::class);
        $textService->shouldReceive('generateText')
            ->andReturn('Generated email content');

        $this->app->instance(\Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface::class, $textService);

        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(\Kaviyarasu\AIAgent\Contracts\ProviderInterface::class);

        $factory->shouldReceive('create')->andReturn($provider);
        $factory->shouldReceive('getAvailableProviders')->andReturn(['openai' => $provider]);

        $provider->shouldReceive('getName')->andReturn('OpenAI');
        $provider->shouldReceive('getVersion')->andReturn('1.0');
        $provider->shouldReceive('isAvailable')->andReturn(true);
        $provider->shouldReceive('getCapabilities')->andReturn(['text']);

        $this->app->instance(ProviderFactory::class, $factory);

        $agent = new EmailAIAgent($textService);

        $this->assertEquals('openai', $agent->getCurrentProvider());

        $result = $agent->useProvider('claude');
        $this->assertSame($agent, $result);
        $this->assertEquals('claude', $agent->getCurrentProvider());
    }

    public function test_content_creator_agent_initialization(): void
    {
        if (!class_exists(\Kaviyarasu\AIAgent\Examples\ContentCreatorAgent::class)) {
            $this->markTestSkipped('ContentCreatorAgent example not yet implemented');
        }

        $textService = Mockery::mock(\Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface::class);
        $imageService = Mockery::mock(\Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface::class);

        $textService->shouldReceive('switchProvider')->with('claude')->andReturn($textService);
        $textService->shouldReceive('switchModel')->with('claude-3-opus-20240229')->andReturn($textService);

        $imageService->shouldReceive('switchProvider')->with('ideogram')->andReturn($imageService);

        $this->app->instance(\Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface::class, $textService);
        $this->app->instance(\Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface::class, $imageService);

        $agent = new ContentCreatorAgent();

        $this->assertInstanceOf(ContentCreatorAgent::class, $agent);
        $this->assertEquals('claude', $agent->getCurrentProvider());
    }

    public function test_model_switching(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(
            \Kaviyarasu\AIAgent\Contracts\ProviderInterface::class . ',' .
                \Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface::class
        );

        $provider->shouldReceive('getName')->andReturn('openai');

        $factory->shouldReceive('create')->andReturn($provider);

        $provider->shouldReceive('switchModel')
            ->with('gpt-4')
            ->andReturn($provider);

        $provider->shouldReceive('getCurrentModel')
            ->andReturn('gpt-4');

        $textService = new TextService($factory);
        $textService->switchProvider('openai');

        $result = $textService->switchModel('gpt-4');
        $this->assertSame($textService, $result);
        $this->assertEquals('gpt-4', $textService->getCurrentModel());
    }

    public function test_temporary_provider_usage(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);

        $defaultProvider = Mockery::mock(
            \Kaviyarasu\AIAgent\Contracts\ProviderInterface::class . ',' .
                \Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface::class
        );
        $defaultProvider->shouldReceive('getName')->andReturn('default');

        $factory->shouldReceive('createForCapability')
            ->with('text')
            ->andReturn($defaultProvider);

        $openaiProvider = Mockery::mock(
            \Kaviyarasu\AIAgent\Contracts\ProviderInterface::class . ',' .
                \Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface::class
        );
        $claudeProvider = Mockery::mock(
            \Kaviyarasu\AIAgent\Contracts\ProviderInterface::class . ',' .
                \Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface::class
        );
        $openaiProvider->shouldReceive('getName')->andReturn('openai');
        $openaiProvider->shouldReceive('getCurrentModel')->andReturn('gpt-3.5-turbo');

        $claudeProvider->shouldReceive('getName')->andReturn('claude');
        $claudeProvider->shouldReceive('getCurrentModel')->andReturn('claude-3');

        $factory->shouldReceive('create')
            ->with('openai')
            ->andReturn($openaiProvider);

        $factory->shouldReceive('create')
            ->with('claude')
            ->andReturn($claudeProvider);

        $claudeProvider->shouldReceive('generateText')
            ->andReturn('Generated with Claude');
        $textService = new TextService($factory);

        $textService->switchProvider('openai');
        $this->assertEquals('openai', $textService->getCurrentProvider());

        $result = $textService->withProvider('claude', function ($service) {
            return 'Generated with Claude';
        });

        $this->assertEquals('Generated with Claude', $result);

        $this->assertEquals('openai', $textService->getCurrentProvider());
    }

    public function test_model_capabilities(): void
    {
        $factory = Mockery::mock(ProviderFactory::class);
        $provider = Mockery::mock(
            \Kaviyarasu\AIAgent\Contracts\ProviderInterface::class . ',' .
                \Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface::class
        );

        $provider->shouldReceive('getName')->andReturn('openai');

        $factory->shouldReceive('createForCapability')
            ->with('text')
            ->andReturn($provider);

        $factory->shouldReceive('create')->andReturn($provider);

        $provider->shouldReceive('getModelCapabilities')
            ->with('gpt-4')
            ->andReturn([
                'max_tokens' => 8192,
                'supports_functions' => true,
                'supports_vision' => true,
            ]);

        $textService = new TextService($factory);

        $capabilities = $textService->getModelCapabilities('gpt-4');

        $this->assertIsArray($capabilities);
        $this->assertEquals(8192, $capabilities['max_tokens']);
        $this->assertTrue($capabilities['supports_functions']);
        $this->assertTrue($capabilities['supports_vision']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
