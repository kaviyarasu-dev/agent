<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Config;

class AIConfigManager
{
    private array $config;

    public function __construct()
    {
        $this->config = $this->loadConfiguration();
    }

    private function loadConfiguration(): array
    {
        return config('ai-agent', []);
    }

    public function getProviderConfig(string $provider): array
    {
        return $this->config['providers'][$provider] ?? [];
    }

    public function getDefaultProvider(string $capability): string
    {
        return $this->config['default_providers'][$capability] ?? $this->config['default_provider'] ?? '';
    }

    public function getFallbackProviders(string $capability): array
    {
        return $this->config['fallback_providers'][$capability] ?? [];
    }

    public function getAllProviders(): array
    {
        return $this->config['providers'] ?? [];
    }

    public function getProvidersForCapability(string $capability): array
    {
        $providers = [];

        foreach ($this->config['providers'] ?? [] as $name => $config) {
            // Check if any model in this provider supports the capability
            foreach ($config['models'] ?? [] as $modelConfig) {
                if (in_array($capability, $modelConfig['capabilities'] ?? [])) {
                    $providers[$name] = $config;

                    break;
                }
            }
        }

        return $providers;
    }

    public function getProviderClass(string $provider): ?string
    {
        return $this->config['providers'][$provider]['class'] ?? null;
    }

    public function getProviderApiKey(string $provider): ?string
    {
        return $this->config['providers'][$provider]['api_key'] ?? null;
    }

    public function getProviderDefaultModel(string $provider): ?string
    {
        return $this->config['providers'][$provider]['default_model'] ?? null;
    }

    public function getProviderModels(string $provider): array
    {
        return $this->config['providers'][$provider]['models'] ?? [];
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return $this->config['features'][$feature]['enabled'] ?? false;
    }

    public function getFeatureConfig(string $feature): array
    {
        return $this->config['features'][$feature] ?? [];
    }

    public function getModuleConfig(string $module): array
    {
        return $this->config['modules'][$module] ?? [];
    }

    public function getModelsForProvider(string $provider): array
    {
        return $this->config['providers'][$provider]['models'] ?? [];
    }
}
