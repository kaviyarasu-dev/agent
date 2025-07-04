<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Providers\AI;

use Kaviyarasu\AIAgent\Contracts\ProviderInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected array $config;

    protected string $currentModel;

    protected array $supportedModels = [];

    protected array $modelCapabilities = [];

    public function __construct(array $config)
    {
        $this->config = $config;

        // Load models from config if not already loaded
        if (empty($this->supportedModels)) {
            $this->loadModelsFromConfig();
        }

        $this->currentModel = $config['model'] ?? $this->getDefaultModel();
        $this->validateModel();
    }

    /**
     * Load supported models from configuration.
     * This method can be overridden by child classes for custom loading logic.
     */
    protected function loadModelsFromConfig(): void
    {
        $providerKey = strtolower($this->getName());
        $models = config("ai-agent.providers.{$providerKey}.models", []);

        if (! empty($models)) {
            $this->supportedModels = array_keys($models);
            $this->modelCapabilities = $models;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function switchModel(string $model): self
    {
        if (! $this->hasModel($model)) {
            throw new \InvalidArgumentException(
                "Model '{$model}' is not supported by {$this->getName()}. ".
                    'Available models: '.implode(', ', $this->supportedModels)
            );
        }

        $this->currentModel = $model;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentModel(): string
    {
        return $this->currentModel;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableModels(): array
    {
        return $this->supportedModels;
    }

    /**
     * {@inheritdoc}
     */
    public function hasModel(string $model): bool
    {
        return in_array($model, $this->supportedModels, true);
    }

    /**
     * {@inheritdoc}
     */
    public function withModel(string $model, callable $callback)
    {
        $originalModel = $this->currentModel;

        try {
            $this->switchModel($model);

            return $callback($this);
        } finally {
            $this->switchModel($originalModel);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getModelCapabilities(?string $model = null): array
    {
        $model = $model ?? $this->currentModel;

        if (isset($this->modelCapabilities[$model])) {
            return $this->modelCapabilities[$model];
        }

        // Return default capabilities
        return [
            'max_tokens' => 4096,
            'supports_streaming' => true,
            'supports_functions' => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return ! empty($this->config['api_key']);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultModel(): string
    {
        if (! empty($this->supportedModels)) {
            return $this->supportedModels[0];
        }

        // Try to get from config
        $providerKey = strtolower($this->getName());

        return config("ai-agent.providers.{$providerKey}.default_model", '');
    }

    /**
     * {@inheritdoc}
     */
    public function validateConfiguration(): bool
    {
        if (empty($this->config['api_key'])) {
            throw new \Exception("API key is required for {$this->getName()} provider");
        }

        if (empty($this->supportedModels)) {
            throw new \Exception("No models configured for {$this->getName()} provider");
        }

        $this->validateModel();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(): array
    {
        // Return config without sensitive data
        $safeConfig = $this->config;
        if (isset($safeConfig['api_key'])) {
            $safeConfig['api_key'] = str_repeat('*', 8).substr($safeConfig['api_key'], -4);
        }

        return $safeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        if (isset($config['model'])) {
            $this->currentModel = $config['model'];
            $this->validateModel();
        }

        return $this;
    }

    /**
     * Validate the current model
     *
     * @throws \InvalidArgumentException
     */
    protected function validateModel(): void
    {
        if (empty($this->currentModel)) {
            throw new \InvalidArgumentException("No model specified for provider {$this->getName()}");
        }

        if (! $this->hasModel($this->currentModel)) {
            throw new \InvalidArgumentException(
                "Invalid model '{$this->currentModel}' for provider {$this->getName()}. ".
                    'Available models: '.implode(', ', $this->supportedModels)
            );
        }
    }

    /**
     * Get API key from configuration
     */
    protected function getApiKey(): string
    {
        return $this->config['api_key'] ?? '';
    }

    /**
     * Get base URL from configuration
     */
    protected function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? '';
    }

    /**
     * Get model display name
     */
    public function getModelDisplayName(?string $model = null): string
    {
        $model = $model ?? $this->currentModel;

        return $this->modelCapabilities[$model]['name'] ?? $model;
    }

    /**
     * Get model version
     */
    public function getModelVersion(?string $model = null): string
    {
        $model = $model ?? $this->currentModel;

        return $this->modelCapabilities[$model]['version'] ?? 'unknown';
    }
}
