<?php

declare(strict_types=1);

namespace Tests\Unit\AI;

use Tests\TestCase;
use App\AI\Factory\ProviderFactory;
use App\AI\Config\AIConfigManager;
use App\AI\Contracts\ProviderInterface;
use Mockery;

class ProviderFactoryTest extends TestCase
{
    private ProviderFactory $factory;
    private $mockConfigManager;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockConfigManager = Mockery::mock(AIConfigManager::class);
        $this->factory = new ProviderFactory($this->mockConfigManager);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_create_provider_successfully()
    {
        $providerName = 'claude';
        $providerConfig = [
            'class' => MockProvider::class,
            'api_key' => 'test-key',
            'model' => 'test-model',
        ];
        
        $this->mockConfigManager
            ->shouldReceive('getProviderConfig')
            ->once()
            ->with($providerName)
            ->andReturn($providerConfig);
        
        $provider = $this->factory->create($providerName);
        
        $this->assertInstanceOf(ProviderInterface::class, $provider);
        $this->assertInstanceOf(MockProvider::class, $provider);
    }
    
    public function test_create_provider_throws_exception_when_not_configured()
    {
        $providerName = 'unknown';
        
        $this->mockConfigManager
            ->shouldReceive('getProviderConfig')
            ->once()
            ->with($providerName)
            ->andReturn([]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider {$providerName} not configured");
        
        $this->factory->create($providerName);
    }
    
    public function test_create_provider_caches_instances()
    {
        $providerName = 'claude';
        $providerConfig = [
            'class' => MockProvider::class,
            'api_key' => 'test-key',
            'model' => 'test-model',
        ];
        
        $this->mockConfigManager
            ->shouldReceive('getProviderConfig')
            ->once() // Should only be called once due to caching
            ->with($providerName)
            ->andReturn($providerConfig);
        
        $provider1 = $this->factory->create($providerName);
        $provider2 = $this->factory->create($providerName);
        
        $this->assertSame($provider1, $provider2);
    }
    
    public function test_create_for_capability_returns_default_provider()
    {
        $capability = 'text';
        $defaultProvider = 'claude';
        $providerConfig = [
            'class' => MockProvider::class,
            'api_key' => 'test-key',
            'model' => 'test-model',
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
        
        $provider = $this->factory->createForCapability($capability);
        
        $this->assertInstanceOf(MockProvider::class, $provider);
    }
    
    public function test_create_for_capability_uses_fallback_when_default_fails()
    {
        $capability = 'text';
        $defaultProvider = 'claude';
        $fallbackProvider = 'openai';
        $fallbackConfig = [
            'class' => MockProvider::class,
            'api_key' => 'test-key',
            'model' => 'test-model',
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
            ->shouldReceive('getFallbackProviders')
            ->once()
            ->with($capability)
            ->andReturn([$fallbackProvider]);
        
        $this->mockConfigManager
            ->shouldReceive('getProviderConfig')
            ->once()
            ->with($fallbackProvider)
            ->andReturn($fallbackConfig);
        
        $provider = $this->factory->createForCapability($capability);
        
        $this->assertInstanceOf(MockProvider::class, $provider);
    }
    
    public function test_clear_cache()
    {
        $providerName = 'claude';
        $providerConfig = [
            'class' => MockProvider::class,
            'api_key' => 'test-key',
            'model' => 'test-model',
        ];
        
        $this->mockConfigManager
            ->shouldReceive('getProviderConfig')
            ->twice() // Should be called twice since cache is cleared
            ->with($providerName)
            ->andReturn($providerConfig);
        
        $provider1 = $this->factory->create($providerName);
        $this->factory->clearCache();
        $provider2 = $this->factory->create($providerName);
        
        $this->assertNotSame($provider1, $provider2);
    }
}

// Mock provider for testing
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
        return !empty($this->config['api_key']);
    }
}