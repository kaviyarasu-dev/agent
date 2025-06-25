<?php

declare(strict_types=1);

use WebsiteLearners\AIAgent\Config\AIConfigManager;
use WebsiteLearners\AIAgent\Contracts\ProviderInterface;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;

beforeEach(function () {
    $this->mockConfigManager = Mockery::mock(AIConfigManager::class);
    $this->factory = new ProviderFactory($this->mockConfigManager);
});

afterEach(function () {
    Mockery::close();
});

it('creates provider successfully', function () {
    $providerName = 'claude';
    $providerConfig = [
        'class' => MockProvider::class,
        'api_key' => 'test-key',

        'default_model' => 'test-model',
    ];

    $this->mockConfigManager
        ->shouldReceive('getProviderConfig')
        ->once()
        ->with($providerName)
        ->andReturn($providerConfig);

    $this->mockConfigManager

        ->shouldReceive('getProviderClass')
        ->once()
        ->with($providerName)

        ->andReturn(MockProvider::class);

    $provider = $this->factory->create($providerName);

    expect($provider)->toBeInstanceOf(ProviderInterface::class);
    expect($provider)->toBeInstanceOf(MockProvider::class);
});

it('throws exception when provider not configured', function () {
    $providerName = 'unknown';
    $this->mockConfigManager
        ->shouldReceive('getProviderConfig')

        ->once()

        ->with($providerName)

        ->andReturn([]);

    $this->mockConfigManager
        ->shouldReceive('getProviderClass')
        ->once()
        ->with($providerName)
        ->andReturn(null);
    $this->factory->create($providerName);
})->throws(\InvalidArgumentException::class, 'Provider unknown not configured');

it('caches provider instances', function () {
    $providerName = 'claude';
    $providerConfig = [
        'class' => MockProvider::class,
        'api_key' => 'test-key',
        'default_model' => 'test-model',
    ];

    $this->mockConfigManager
        ->shouldReceive('getProviderConfig')
        ->once()
        ->with($providerName)
        ->andReturn($providerConfig);

    $this->mockConfigManager
        ->shouldReceive('getProviderClass')
        ->once()
        ->with($providerName)
        ->andReturn(MockProvider::class);
    $provider1 = $this->factory->create($providerName);
    $provider2 = $this->factory->create($providerName);

    expect($provider1)->toBe($provider2);
});

it('creates provider for capability using default', function () {
    $capability = 'text';
    $defaultProvider = 'claude';
    $providerConfig = [
        'class' => MockProvider::class,
        'api_key' => 'test-key',
        'default_model' => 'test-model',
    ];

    $this->mockConfigManager
        ->shouldReceive('getDefaultProvider')
        ->once()
        ->with($capability)
        ->andReturn($defaultProvider);

    $this->mockConfigManager
        ->shouldReceive('getProviderConfig')
        ->once()
        ->with($defaultProvider)
        ->andReturn($providerConfig);

    $this->mockConfigManager
        ->shouldReceive('getProviderClass')
        ->once()
        ->with($defaultProvider)
        ->andReturn(MockProvider::class);

    $provider = $this->factory->createForCapability($capability);

    expect($provider)->toBeInstanceOf(MockProvider::class);
});

it('uses fallback provider when default fails', function () {
    $capability = 'text';
    $defaultProvider = 'claude';
    $fallbackProvider = 'openai';
    $fallbackConfig = [
        'class' => MockProvider::class,
        'api_key' => 'test-key',
        'default_model' => 'test-model',
    ];

    $this->mockConfigManager
        ->shouldReceive('getDefaultProvider')
        ->once()
        ->with($capability)
        ->andReturn($defaultProvider);

    $this->mockConfigManager
        ->shouldReceive('getProviderConfig')
        ->once()
        ->with($defaultProvider)
        ->andReturn(['class' => 'NonExistentClass']);

    $this->mockConfigManager
        ->shouldReceive('getProviderClass')
        ->once()
        ->with($defaultProvider)
        ->andReturn('NonExistentClass');

    $this->mockConfigManager
        ->shouldReceive('getFallbackProviders')
        ->once()
        ->with($capability)
        ->andReturn([$fallbackProvider]);

    $this->mockConfigManager
        ->shouldReceive('getProviderConfig')
        ->once()
        ->with($fallbackProvider)
        ->andReturn($fallbackConfig);

    $this->mockConfigManager
        ->shouldReceive('getProviderClass')
        ->once()
        ->with($fallbackProvider)
        ->andReturn(MockProvider::class);

    $provider = $this->factory->createForCapability($capability);

    expect($provider)->toBeInstanceOf(MockProvider::class);
});

it('clears cache', function () {
    $providerName = 'claude';
    $providerConfig = [
        'class' => MockProvider::class,
        'api_key' => 'test-key',
        'default_model' => 'test-model',
    ];

    $this->mockConfigManager
        ->shouldReceive('getProviderConfig')
        ->twice()
        ->with($providerName)
        ->andReturn($providerConfig);

    $this->mockConfigManager
        ->shouldReceive('getProviderClass')
        ->twice()
        ->with($providerName)
        ->andReturn(MockProvider::class);

    $provider1 = $this->factory->create($providerName);
    $this->factory->clearCache();
    $provider2 = $this->factory->create($providerName);

    expect($provider1)->not->toBe($provider2);
});
class MockProvider implements ProviderInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'Mock Provider';
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
        return ['text', 'image'];
    }

    public function isAvailable(): bool
    {
        return ! empty($this->config['api_key']);
    }
}
