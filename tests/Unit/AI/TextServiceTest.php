<?php

declare(strict_types=1);

namespace Tests\Unit\AI;

use Tests\TestCase;
use App\AI\Services\Core\TextService;
use App\AI\Factory\ProviderFactory;
use App\AI\Contracts\Capabilities\TextGenerationInterface;
use App\AI\Contracts\ProviderInterface;
use Mockery;

class TextServiceTest extends TestCase
{
    private TextService $textService;
    private $mockProviderFactory;
    private $mockProvider;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockProvider = Mockery::mock(ProviderInterface::class, TextGenerationInterface::class);
        $this->mockProviderFactory = Mockery::mock(ProviderFactory::class);
        
        $this->textService = new TextService($this->mockProviderFactory);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_generate_text_with_default_provider()
    {
        $prompt = 'Test prompt';
        $expectedResponse = 'Generated text response';
        
        $this->mockProviderFactory
            ->shouldReceive('createForCapability')
            ->once()
            ->with('text')
            ->andReturn($this->mockProvider);
        
        $this->mockProvider
            ->shouldReceive('generateText')
            ->once()
            ->with([
                'prompt' => $prompt,
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ])
            ->andReturn($expectedResponse);
        
        $result = $this->textService->generateText($prompt);
        
        $this->assertEquals($expectedResponse, $result);
    }
    
    public function test_generate_text_with_custom_options()
    {
        $prompt = 'Test prompt';
        $options = [
            'temperature' => 0.9,
            'max_tokens' => 2000,
        ];
        $expectedResponse = 'Generated text response';
        
        $this->mockProviderFactory
            ->shouldReceive('createForCapability')
            ->once()
            ->with('text')
            ->andReturn($this->mockProvider);
        
        $this->mockProvider
            ->shouldReceive('generateText')
            ->once()
            ->with([
                'prompt' => $prompt,
                'temperature' => 0.9,
                'max_tokens' => 2000,
            ])
            ->andReturn($expectedResponse);
        
        $result = $this->textService->generateText($prompt, $options);
        
        $this->assertEquals($expectedResponse, $result);
    }
    
    public function test_set_provider_validates_text_generation_support()
    {
        $invalidProvider = Mockery::mock(ProviderInterface::class);
        
        $this->mockProviderFactory
            ->shouldReceive('create')
            ->once()
            ->with('invalid-provider')
            ->andReturn($invalidProvider);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider does not support text generation');
        
        $this->textService->setProvider('invalid-provider');
    }
    
    public function test_fallback_on_provider_failure()
    {
        $prompt = 'Test prompt';
        $expectedResponse = 'Fallback response';
        
        // First attempt fails
        $this->mockProviderFactory
            ->shouldReceive('createForCapability')
            ->once()
            ->with('text')
            ->andReturn($this->mockProvider);
        
        $this->mockProvider
            ->shouldReceive('generateText')
            ->once()
            ->andThrow(new \Exception('Provider failed'));
        
        // Second attempt succeeds with fallback
        $fallbackProvider = Mockery::mock(ProviderInterface::class, TextGenerationInterface::class);
        
        $this->mockProviderFactory
            ->shouldReceive('createForCapability')
            ->once()
            ->with('text')
            ->andReturn($fallbackProvider);
        
        $fallbackProvider
            ->shouldReceive('generateText')
            ->once()
            ->with([
                'prompt' => $prompt,
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ])
            ->andReturn($expectedResponse);
        
        $result = $this->textService->generateText($prompt);
        
        $this->assertEquals($expectedResponse, $result);
    }
    
    public function test_stream_text()
    {
        $prompt = 'Test prompt';
        $expectedStream = ['chunk1', 'chunk2', 'chunk3'];
        
        $this->mockProviderFactory
            ->shouldReceive('createForCapability')
            ->once()
            ->with('text')
            ->andReturn($this->mockProvider);
        
        $this->mockProvider
            ->shouldReceive('streamText')
            ->once()
            ->with([
                'prompt' => $prompt,
                'stream' => true,
            ])
            ->andReturn($expectedStream);
        
        $result = $this->textService->streamText($prompt);
        
        $this->assertEquals($expectedStream, $result);
    }
}