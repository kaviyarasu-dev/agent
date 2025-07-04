<?php

declare(strict_types=1);

use Kaviyarasu\AIAgent\Config\AIConfigManager;
use Kaviyarasu\AIAgent\Contracts\HasModelSwitching;
use Kaviyarasu\AIAgent\Contracts\ProviderInterface;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;

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

    private string $currentModel;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->currentModel = $config['default_model'] ?? 'default-model';
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

    public function getDefaultModel(): string
    {
        return $this->config['default_model'] ?? 'default-model';
    }

    public function validateConfiguration(): bool
    {
        return ! empty($this->config['api_key']);
    }

    public function getConfiguration(): array
    {
        return $this->config;
    }

    public function setConfiguration(array $config): ProviderInterface
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    public function switchModel(string $model): HasModelSwitching
    {
        $this->currentModel = $model;

        return $this;
    }

    public function getCurrentModel(): string
    {
        return $this->currentModel;
    }

    public function getModelCapabilities(?string $model = null): array
    {
        return [
            'max_tokens' => 4096,
            'supports_functions' => true,
            'supports_vision' => false,
        ];
    }

    public function getSupportedModels(): array
    {
        return ['default-model', 'advanced-model'];
    }

    public function validateModel(string $model): bool
    {
        return in_array($model, $this->getSupportedModels());
    }

    public function getModelConfig(string $model): array
    {
        return [
            'name' => $model,
            'max_tokens' => 4096,
        ];
    }

    public function getAvailableModels(): array
    {
        return $this->getSupportedModels();
    }

    public function hasModel(string $model): bool
    {
        return $this->validateModel($model);
    }

    public function withModel(string $model, callable $callback): mixed
    {
        $previousModel = $this->currentModel;
        $this->switchModel($model);

        try {
            return $callback($this);
        } finally {
            $this->currentModel = $previousModel;
        }
    }
}
