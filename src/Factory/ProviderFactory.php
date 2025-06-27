<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Factory;

use Illuminate\Support\Facades\Cache;
use WebsiteLearners\AIAgent\Config\AIConfigManager;
use WebsiteLearners\AIAgent\Contracts\ProviderInterface;
use WebsiteLearners\AIAgent\Exceptions\AIAgentException;

class ProviderFactory
{
    private AIConfigManager $configManager;

    private array $providerInstances = [];

    public function __construct(AIConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function create(string $providerName): ProviderInterface
    {
        if (isset($this->providerInstances[$providerName])) {
            return $this->providerInstances[$providerName];
        }

        $config = $this->configManager->getProviderConfig($providerName);
        $providerClass = $this->configManager->getProviderClass($providerName);

        if (empty($config) || ! $providerClass) {
            throw new \InvalidArgumentException("Provider {$providerName} not configured");
        }

        if (! class_exists($providerClass)) {
            throw new AIAgentException("Provider class {$providerClass} not found");
        }

        $provider = new $providerClass($config);

        // Cache the instance
        $this->providerInstances[$providerName] = $provider;

        return $provider;
    }

    public function createForCapability(string $capability): ProviderInterface
    {
        $defaultProvider = $this->configManager->getDefaultProvider($capability);

        try {
            $provider = $this->create($defaultProvider);

            if ($provider->isAvailable() && $provider->supports($capability)) {
                return $provider;
            }
        } catch (\Exception $e) {
            // Log the error
            logger()->warning("Default provider {$defaultProvider} failed: " . $e->getMessage());
        }

        // Try fallback providers
        $fallbackProviders = $this->configManager->getFallbackProviders($capability);

        foreach ($fallbackProviders as $fallbackProvider) {
            try {
                $provider = $this->create($fallbackProvider);

                if ($provider->isAvailable() && $provider->supports($capability)) {
                    return $provider;
                }
            } catch (\Exception $e) {
                logger()->warning("Fallback provider {$fallbackProvider} failed: " . $e->getMessage());
            }
        }

        throw new AIAgentException("No available provider found for capability: {$capability}");
    }

    public function getAvailableProviders(string $capability): array
    {
        $providers = [];
        $allProviders = $this->configManager->getProvidersForCapability($capability);

        foreach ($allProviders as $name => $config) {
            try {
                $provider = $this->create($name);
                if ($provider->isAvailable()) {
                    $providers[$name] = $provider;
                }
            } catch (\Exception $e) {
                logger()->debug("Provider {$name} not available: " . $e->getMessage());
            }
        }

        return $providers;
    }

    public function clearCache(): void
    {
        $this->providerInstances = [];
    }
}
