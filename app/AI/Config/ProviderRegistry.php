<?php

declare(strict_types=1);

namespace App\AI\Config;

use App\AI\Contracts\ProviderInterface;

class ProviderRegistry
{
    private array $providers = [];
    private array $capabilityMap = [];
    
    public function register(string $name, ProviderInterface $provider): void
    {
        $this->providers[$name] = $provider;
        
        // Update capability map
        foreach ($provider->getCapabilities() as $capability) {
            if (!isset($this->capabilityMap[$capability])) {
                $this->capabilityMap[$capability] = [];
            }
            $this->capabilityMap[$capability][] = $name;
        }
    }
    
    public function get(string $name): ?ProviderInterface
    {
        return $this->providers[$name] ?? null;
    }
    
    public function getProvidersForCapability(string $capability): array
    {
        $providerNames = $this->capabilityMap[$capability] ?? [];
        $providers = [];
        
        foreach ($providerNames as $name) {
            if (isset($this->providers[$name])) {
                $providers[$name] = $this->providers[$name];
            }
        }
        
        return $providers;
    }
    
    public function getAllProviders(): array
    {
        return $this->providers;
    }
    
    public function hasProvider(string $name): bool
    {
        return isset($this->providers[$name]);
    }
    
    public function getCapabilities(): array
    {
        return array_keys($this->capabilityMap);
    }
}