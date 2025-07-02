<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Tests\Feature;

use Kaviyarasu\AIAgent\Tests\TestCase;
use Kaviyarasu\AIAgent\Agents\BaseAIAgent;
use Kaviyarasu\AIAgent\Services\Core\TextService;
use Kaviyarasu\AIAgent\Services\Core\ImageService;
use Kaviyarasu\AIAgent\Services\Core\VideoService;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;
use Kaviyarasu\AIAgent\Factory\ServiceFactory;
use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;
use Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface;
use Kaviyarasu\AIAgent\Contracts\Services\VideoServiceInterface;
use Mockery;

class TestAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text'];

    public function execute(array $params): string
    {
        $prompt = $params['prompt'] ?? 'Default prompt';
        return $this->textService->generateText($prompt);
    }

    public function getServices(): array
    {
        return [
            'text' => $this->textService,
            'image' => $this->imageService,
            'video' => $this->videoService,
        ];
    }
}

class MultiServiceAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text', 'image'];

    public function execute(array $params): array
    {
        $textPrompt = $params['text_prompt'] ?? 'Default text prompt';
        $imagePrompt = $params['image_prompt'] ?? 'Default image prompt';

        return [
            'text' => $this->textService->generateText($textPrompt),
            'image' => $this->imageService->generateImage($imagePrompt),
        ];
    }

    public function getServices(): array
    {
        return [
            'text' => $this->textService,
            'image' => $this->imageService,
            'video' => $this->videoService,
        ];
    }
}

class BaseAIAgentTest extends TestCase
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

    public function test_base_agent_initialization(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);
        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);

        $agent = new TestAgent($serviceFactory);

        $services = $agent->getServices();

        $this->assertInstanceOf(TextServiceInterface::class, $services['text']);
        $this->assertNull($services['image']);
        $this->assertNull($services['video']);
    }

    public function test_multi_service_agent_initialization(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);
        $imageService = Mockery::mock(ImageServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);
        $serviceFactory->shouldReceive('createImageService')
            ->andReturn($imageService);

        $agent = new MultiServiceAgent($serviceFactory);

        $services = $agent->getServices();

        $this->assertInstanceOf(TextServiceInterface::class, $services['text']);
        $this->assertInstanceOf(ImageServiceInterface::class, $services['image']);
        $this->assertNull($services['video']);
    }

    public function test_base_agent_provider_switching(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);
        $serviceFactory->shouldReceive('setDefaultProvider')
            ->with('claude');
        $serviceFactory->shouldReceive('setDefaultProvider')
            ->with('openai');

        $textService->shouldReceive('switchProvider')
            ->with('claude')
            ->andReturn($textService);
        $textService->shouldReceive('switchProvider')
            ->with('openai')
            ->andReturn($textService);

        $agent = new TestAgent($serviceFactory);

        $result = $agent->switchProvider('claude');
        $this->assertInstanceOf(TestAgent::class, $result);
        $this->assertEquals('claude', $agent->getCurrentProvider());

        $result = $agent->switchProvider('openai');
        $this->assertInstanceOf(TestAgent::class, $result);
        $this->assertEquals('openai', $agent->getCurrentProvider());
    }

    public function test_base_agent_model_switching(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);

        $textService->shouldReceive('switchModel')
            ->with('gpt-4')
            ->andReturn($textService);

        $agent = new TestAgent($serviceFactory);

        $result = $agent->switchModel('gpt-4');
        $this->assertInstanceOf(TestAgent::class, $result);
    }

    public function test_base_agent_with_provider(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);
        $serviceFactory->shouldReceive('setDefaultProvider')->times(2);

        $textService->shouldReceive('switchProvider')->times(2);
        $textService->shouldReceive('generateText')
            ->andReturn('Generated with Claude');

        $agent = new TestAgent($serviceFactory);

        $result = $agent->withProvider('claude', function ($agent) {
            return $agent->execute(['prompt' => 'Test']);
        });

        $this->assertEquals('Generated with Claude', $result);
    }

    public function test_base_agent_with_model(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);

        $textService->shouldReceive('getCurrentModel')
            ->andReturn('gpt-3.5-turbo');
        $textService->shouldReceive('switchModel')->times(2);
        $textService->shouldReceive('generateText')
            ->andReturn('Generated with GPT-4');

        $agent = new TestAgent($serviceFactory);

        $result = $agent->withModel('gpt-4', function ($agent) {
            return $agent->execute(['prompt' => 'Test']);
        });

        $this->assertEquals('Generated with GPT-4', $result);
    }

    public function test_base_agent_execute_with(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);
        $serviceFactory->shouldReceive('setDefaultProvider')->times(2);

        $textService->shouldReceive('switchProvider')->times(2);
        $textService->shouldReceive('getCurrentModel')
            ->andReturn('claude-3-sonnet');
        $textService->shouldReceive('switchModel')->times(2);
        $textService->shouldReceive('generateText')
            ->andReturn('Executed with temporary config');

        $agent = new TestAgent($serviceFactory);

        $result = $agent->executeWith(
            ['prompt' => 'Test'],
            'claude',
            'claude-3-opus'
        );

        $this->assertEquals('Executed with temporary config', $result);
    }

    public function test_base_agent_execute_with_fallback(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);
        $serviceFactory->shouldReceive('setDefaultProvider')->times(3);

        $textService->shouldReceive('switchProvider')
            ->with('openai')
            ->andReturn($textService);

        $textService->shouldReceive('generateText')
            ->once()
            ->andThrow(new \Exception('OpenAI failed'));

        $textService->shouldReceive('switchProvider')
            ->with('claude')
            ->andReturn($textService);

        $textService->shouldReceive('generateText')
            ->once()
            ->andReturn('Fallback succeeded');

        $textService->shouldReceive('switchProvider')
            ->once()
            ->andReturn($textService);

        $agent = new TestAgent($serviceFactory);

        $result = $agent->executeWithFallback(
            ['prompt' => 'Test'],
            ['openai', 'claude']
        );

        $this->assertEquals('Fallback succeeded', $result);
    }

    /**
     * Test getting model capabilities
     */
    public function test_base_agent_get_model_capabilities(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);

        $textService->shouldReceive('getModelCapabilities')
            ->with('gpt-4')
            ->andReturn([
                'max_tokens' => 8192,
                'supports_functions' => true,
                'supports_streaming' => true,
            ]);

        $agent = new TestAgent($serviceFactory);

        $capabilities = $agent->getModelCapabilities('gpt-4');

        $this->assertIsArray($capabilities);
        $this->assertArrayHasKey('max_tokens', $capabilities);
        $this->assertEquals(8192, $capabilities['max_tokens']);
        $this->assertArrayHasKey('supports_functions', $capabilities);
        $this->assertTrue($capabilities['supports_functions']);
    }

    public function test_multi_service_different_providers(): void
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock(TextServiceInterface::class);
        $imageService = Mockery::mock(ImageServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')
            ->andReturn($textService);
        $serviceFactory->shouldReceive('createImageService')
            ->andReturn($imageService);
        $serviceFactory->shouldReceive('setDefaultProvider')
            ->with('claude');

        $textService->shouldReceive('switchProvider')
            ->with('claude')
            ->andReturn($textService);

        $textService->shouldReceive('generateText')
            ->andReturn('Text from Claude');

        $imageService->shouldReceive('switchProvider')
            ->with('claude')
            ->andReturn($imageService);

        $imageService->shouldReceive('generateImage')
            ->andReturn('image_url.jpg');

        $agent = new MultiServiceAgent($serviceFactory);

        $agent->switchProvider('claude');

        $result = $agent->execute([
            'text_prompt' => 'Generate text',
            'image_prompt' => 'Generate image'
        ]);

        $this->assertEquals('Text from Claude', $result['text']);
        $this->assertEquals('image_url.jpg', $result['image']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
