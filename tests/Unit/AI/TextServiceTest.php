<?php

declare(strict_types=1);

use Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface;
use Kaviyarasu\AIAgent\Contracts\ProviderInterface;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;
use Kaviyarasu\AIAgent\Services\Core\TextService;

beforeEach(function () {
    $this->mockProvider = Mockery::mock(ProviderInterface::class, TextGenerationInterface::class);
    $this->mockProviderFactory = Mockery::mock(ProviderFactory::class);

    $this->textService = new TextService($this->mockProviderFactory);
});

afterEach(function () {
    Mockery::close();
});

it('generates text with default provider', function () {
    $prompt = 'Test prompt';
    $expectedResponse = 'Generated text response';

    $this->mockProviderFactory
        ->shouldReceive('createForCapability')
        ->once()
        ->with('text')
        ->andReturn($this->mockProvider);

    $this->mockProvider
        ->shouldReceive('getName')
        ->andReturn('test-provider');

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

    expect($result)->toBe($expectedResponse);
});

it('generates text with custom options', function () {
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
        ->shouldReceive('getName')
        ->andReturn('test-provider');

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

    expect($result)->toBe($expectedResponse);
});

it('validates provider supports text generation', function () {
    $invalidProvider = Mockery::mock(ProviderInterface::class);

    $this->mockProviderFactory
        ->shouldReceive('create')
        ->once()
        ->with('invalid-provider')
        ->andReturn($invalidProvider);

    $this->textService->setProvider('invalid-provider');
})->throws(\InvalidArgumentException::class, 'Provider does not support text generation');

it('falls back on provider failure', function () {
    $prompt = 'Test prompt';
    $expectedResponse = 'Fallback response';

    $this->mockProviderFactory
        ->shouldReceive('createForCapability')
        ->once()
        ->with('text')
        ->andReturn($this->mockProvider);

    $this->mockProvider
        ->shouldReceive('getName')
        ->andReturn('test-provider');

    $this->mockProvider
        ->shouldReceive('generateText')
        ->once()
        ->andThrow(new \Exception('Provider failed'));

    $fallbackProvider = Mockery::mock(ProviderInterface::class, TextGenerationInterface::class);

    $this->mockProviderFactory
        ->shouldReceive('createForCapability')
        ->once()
        ->with('text')
        ->andReturn($fallbackProvider);

    $fallbackProvider
        ->shouldReceive('getName')
        ->andReturn('fallback-provider');

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

    expect($result)->toBe($expectedResponse);
});

it('streams text', function () {
    $prompt = 'Test prompt';
    $expectedStream = ['chunk1', 'chunk2', 'chunk3'];

    $this->mockProviderFactory
        ->shouldReceive('createForCapability')
        ->once()
        ->with('text')
        ->andReturn($this->mockProvider);

    $this->mockProvider
        ->shouldReceive('getName')
        ->andReturn('test-provider');

    $this->mockProvider
        ->shouldReceive('streamText')
        ->once()
        ->with([
            'prompt' => $prompt,
            'stream' => true,
        ])
        ->andReturn($expectedStream);

    $result = $this->textService->streamText($prompt);

    expect($result)->toBe($expectedStream);
});
