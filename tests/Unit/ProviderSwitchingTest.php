<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Tests\Unit;

use Mockery;
use WebsiteLearners\AIAgent\Tests\TestCase;
use WebsiteLearners\AIAgent\Services\Core\TextService;
use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\ImageServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;
use WebsiteLearners\AIAgent\Factory\ServiceFactory;
use WebsiteLearners\AIAgent\Agents\BaseAIAgent;
use WebsiteLearners\AIAgent\Examples\EmailAIAgent;
use App\Agents\Examples\AdaptiveCodeAgent;
use App\Agents\Examples\ContentCreatorAgent;

class ProviderSwitchingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_text_service_can_switch_providers()
    {
        $providerFactory = Mockery::mock(ProviderFactory::class);
        $textService = new TextService($providerFactory);

        $mockProvider = Mockery::mock(
            'WebsiteLearners\AIAgent\Contracts\ProviderInterface,' .
                'WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface'
        );
        $mockProvider->shouldReceive('getName')->andReturn('openai');
        $mockProvider->shouldReceive('isAvailable')->andReturn(true);
        $mockProvider->shouldReceive('getCapabilities')->andReturn(['text']);

        $providerFactory->shouldReceive('create')
            ->with('openai')
            ->andReturn($mockProvider);

        $textService->switchProvider('openai');

        $this->assertEquals('openai', $textService->getCurrentProvider());
    }

    public function test_model_switching_works_correctly()
    {
        $providerFactory = Mockery::mock(ProviderFactory::class);
        $textService = new TextService($providerFactory);

        $mockProvider = Mockery::mock(
            'WebsiteLearners\AIAgent\Contracts\ProviderInterface,' .
                'WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface'
        );

        $mockProvider->shouldReceive('getName')->andReturn('openai');
        $mockProvider->shouldReceive('switchModel')->with('gpt-4')->andReturnSelf();
        $mockProvider->shouldReceive('getCurrentModel')->andReturn('gpt-4');
        $mockProvider->shouldReceive('getAvailableModels')->andReturn(['gpt-3.5-turbo', 'gpt-4']);

        $providerFactory->shouldReceive('create')
            ->with('openai')
            ->andReturn($mockProvider);

        $providerFactory->shouldReceive('createForCapability')
            ->with('text')
            ->andReturn($mockProvider);

        $textService->switchProvider('openai');
        $textService->switchModel('gpt-4');

        $this->assertEquals('gpt-4', $textService->getCurrentModel());
        $this->assertTrue($textService->hasModel('gpt-4'));
    }

    public function test_with_provider_restores_original_provider()
    {
        $providerFactory = Mockery::mock(ProviderFactory::class);
        $textService = new TextService($providerFactory);

        $claudeProvider = Mockery::mock(
            'WebsiteLearners\AIAgent\Contracts\ProviderInterface,' .
                'WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface'
        );
        $claudeProvider->shouldReceive('getName')->andReturn('claude');
        $claudeProvider->shouldReceive('getCurrentModel')->andReturn('claude-3-sonnet');

        $openaiProvider = Mockery::mock(
            'WebsiteLearners\AIAgent\Contracts\ProviderInterface,' .
                'WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface'
        );
        $openaiProvider->shouldReceive('getName')->andReturn('openai');

        $providerFactory->shouldReceive('create')->with('claude')->andReturn($claudeProvider);
        $providerFactory->shouldReceive('create')->with('openai')->andReturn($openaiProvider);
        $providerFactory->shouldReceive('createForCapability')->with('text')->andReturn($claudeProvider);

        $textService->switchProvider('claude');
        $this->assertEquals('claude', $textService->getCurrentProvider());

        $result = $textService->withProvider('openai', function ($service) {
            return $service->getCurrentProvider();
        });

        $this->assertEquals('openai', $result);

        $this->assertEquals('claude', $textService->getCurrentProvider());
    }

    public function test_has_dynamic_provider_trait_works()
    {
        $textService = Mockery::mock(TextServiceInterface::class);
        $emailAgent = new EmailAIAgent($textService);

        $textService->shouldReceive('setProvider')->with('openai');
        $textService->shouldReceive('generateText')
            ->andReturn('Test email body');

        $result = $emailAgent->useProvider('openai')
            ->execute([
                'recipient' => 'John Doe',
                'subject' => 'Test Subject',
                'purpose' => 'Testing'
            ]);

        $this->assertIsString($result);
        $this->assertEquals('Test email body', $result);
        $this->assertEquals('openai', $emailAgent->getCurrentProvider());
    }

    public function test_base_ai_agent_switches_all_services()
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);

        $textService = Mockery::mock(TextServiceInterface::class);
        $imageService = Mockery::mock(ImageServiceInterface::class);

        $serviceFactory->shouldReceive('createTextService')->andReturn($textService);
        $serviceFactory->shouldReceive('createImageService')->andReturn($imageService);
        $serviceFactory->shouldReceive('setDefaultProvider')->with('openai');

        $textService->shouldReceive('switchProvider')->with('openai');
        $imageService->shouldReceive('switchProvider')->with('openai');

        $agent = new class($serviceFactory) extends BaseAIAgent {
            protected array $requiredServices = ['text', 'image'];

            public function execute(array $data)
            {
                return [
                    'text' => $this->textService->generateText($data['prompt'] ?? 'test'),
                    'image' => $this->imageService->generateImage($data['image_prompt'] ?? 'test image')
                ];
            }
        };

        $agent->switchProvider('openai');

        $this->assertEquals('openai', $agent->getCurrentProvider());
    }

    public function test_get_model_capabilities()
    {
        $providerFactory = Mockery::mock(ProviderFactory::class);
        $textService = new TextService($providerFactory);

        $mockProvider = Mockery::mock(
            'WebsiteLearners\AIAgent\Contracts\ProviderInterface,' .
                'WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface'
        );

        $mockProvider->shouldReceive('getName')->andReturn('openai');
        $mockProvider->shouldReceive('getModelCapabilities')
            ->with('gpt-4')
            ->andReturn([
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'supports_functions' => true,
            ]);

        $providerFactory->shouldReceive('createForCapability')
            ->with('text')
            ->andReturn($mockProvider);

        $capabilities = $textService->getModelCapabilities('gpt-4');

        $this->assertEquals(8192, $capabilities['max_tokens']);
        $this->assertTrue($capabilities['supports_streaming']);
        $this->assertTrue($capabilities['supports_functions']);
    }

    public function test_execute_with_fallback_providers()
    {
        $serviceFactory = Mockery::mock(ServiceFactory::class);
        $textService = Mockery::mock('WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface');

        $serviceFactory->shouldReceive('createTextService')->andReturn($textService);
        $serviceFactory->shouldReceive('setDefaultProvider')->once();

        $textService->shouldReceive('switchProvider')->with('claude')
            ->andThrow(new \Exception('Claude unavailable'));
        $textService->shouldReceive('switchProvider')->with('openai')
            ->once();
        $textService->shouldReceive('generateText')->andReturn('Success with fallback');
        $agent = new class($serviceFactory) extends BaseAIAgent {
            protected array $requiredServices = ['text'];

            public function execute(array $data)
            {
                $prompt = $data['prompt'] ?? 'test';
                return $this->textService->generateText($prompt);
            }
        };

        $result = $agent->executeWithFallback(
            ['prompt' => 'test'],
            ['claude', 'openai']
        );

        $this->assertEquals('Success with fallback', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
