<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Services\Core;

use WebsiteLearners\AIAgent\Contracts\Capabilities\VideoGenerationInterface;
use WebsiteLearners\AIAgent\Contracts\HasProviderSwitching;
use WebsiteLearners\AIAgent\Contracts\HasModelSwitching;
use WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface;
use WebsiteLearners\AIAgent\Exceptions\AIAgentException;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;

class VideoService implements VideoServiceInterface, HasProviderSwitching, HasModelSwitching
{
    private ProviderFactory $providerFactory;

    private ?VideoGenerationInterface $currentProvider = null;

    private string $currentProviderName = '';

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }

    public function generateVideo(string $prompt, array $options = []): string
    {
        $provider = $this->getProvider();

        $params = array_merge([
            'prompt' => $prompt,
            'duration' => 5,
            'fps' => 30,
            'resolution' => '1280x720',
        ], $options);

        return $provider->generateVideo($params);
    }

    public function getVideoStatus(string $jobId): array
    {
        $provider = $this->getProvider();

        return $provider->checkVideoStatus($jobId);
    }

    public function setProvider(string $providerName): void
    {
        $provider = $this->providerFactory->create($providerName);

        if (! $provider instanceof VideoGenerationInterface) {
            throw new \InvalidArgumentException('Provider does not support video generation');
        }

        $this->currentProvider = $provider;
        $this->currentProviderName = $providerName;
    }

    public function switchProvider(string $providerName): self
    {
        $this->setProvider($providerName);
        return $this;
    }

    public function getCurrentProvider(): string
    {
        return $this->currentProviderName ?: 'default';
    }

    public function getAvailableProviders(): array
    {
        $providers = $this->providerFactory->getAvailableProviders('video');
        $result = [];

        foreach ($providers as $name => $provider) {
            $result[$name] = [
                'name' => $provider->getName(),
                'available' => $provider->isAvailable(),
                'capabilities' => $provider->getCapabilities(),
            ];
        }

        return $result;
    }

    public function hasProvider(string $providerName): bool
    {
        try {
            $provider = $this->providerFactory->create($providerName);
            return $provider->supports('video') && $provider->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function withProvider(string $providerName, callable $callback)
    {
        $originalProvider = $this->currentProviderName;
        $originalModel = $this->getCurrentModel();

        try {
            $this->switchProvider($providerName);
            return $callback($this);
        } finally {
            if ($originalProvider) {
                $this->switchProvider($originalProvider);
                if ($originalModel && $originalModel !== 'unknown') {
                    try {
                        $this->switchModel($originalModel);
                    } catch (\Exception $e) {
                        logger()->warning('Could not restore original model: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    public function switchModel(string $model): self
    {
        $provider = $this->getProvider();

        if (!method_exists($provider, 'switchModel')) {
            throw new AIAgentException('Current provider does not support model switching');
        }

        $provider->switchModel($model);
        return $this;
    }

    public function getCurrentModel(): string
    {
        $provider = $this->getProvider();

        if (!method_exists($provider, 'getCurrentModel')) {
            return 'unknown';
        }

        return $provider->getCurrentModel();
    }

    public function getAvailableModels(): array
    {
        $provider = $this->getProvider();

        if (!method_exists($provider, 'getAvailableModels')) {
            return [];
        }

        return $provider->getAvailableModels();
    }

    public function hasModel(string $model): bool
    {
        return in_array($model, $this->getAvailableModels(), true);
    }

    public function withModel(string $model, callable $callback)
    {
        $originalModel = $this->getCurrentModel();

        try {
            $this->switchModel($model);
            return $callback($this);
        } finally {
            if ($originalModel && $originalModel !== 'unknown') {
                try {
                    $this->switchModel($originalModel);
                } catch (\Exception $e) {
                    logger()->warning('Could not restore original model: ' . $e->getMessage());
                }
            }
        }
    }

    public function getModelCapabilities(?string $model = null): array
    {
        $provider = $this->getProvider();

        if (!method_exists($provider, 'getModelCapabilities')) {
            return [];
        }

        return $provider->getModelCapabilities($model);
    }

    private function getProvider(): VideoGenerationInterface
    {
        if ($this->currentProvider === null) {
            $provider = $this->providerFactory->createForCapability('video');

            if (! $provider instanceof VideoGenerationInterface) {
                throw new AIAgentException('Provider does not implement VideoGenerationInterface');
            }

            $this->currentProvider = $provider;
            $this->currentProviderName = $provider->getName();
        }

        return $this->currentProvider;
    }
}
