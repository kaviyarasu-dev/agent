<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Providers\AI;

use WebsiteLearners\AIAgent\Contracts\ProviderInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected array $config;

    protected string $currentModel;

    protected array $supportedModels = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->currentModel = $config['model'] ?? $this->getDefaultModel();
        $this->validateModel();
    }

    public function switchModel(string $model): void
    {
        if (! in_array($model, $this->supportedModels)) {
            throw new \InvalidArgumentException("Model {$model} is not supported");
        }

        $this->currentModel = $model;
    }

    public function getCurrentModel(): string
    {
        return $this->currentModel;
    }

    public function isAvailable(): bool
    {
        return ! empty($this->config['api_key']);
    }

    abstract protected function getDefaultModel(): string;

    protected function validateModel(): void
    {
        if (! in_array($this->currentModel, $this->supportedModels)) {
            throw new \InvalidArgumentException("Invalid model: {$this->currentModel}");
        }
    }
}
