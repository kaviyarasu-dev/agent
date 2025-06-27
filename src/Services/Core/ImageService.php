<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Services\Core;

use WebsiteLearners\AIAgent\Contracts\Capabilities\ImageGenerationInterface;
use WebsiteLearners\AIAgent\Contracts\HasProviderSwitching;
use WebsiteLearners\AIAgent\Contracts\HasModelSwitching;
use WebsiteLearners\AIAgent\Contracts\Services\ImageServiceInterface;
use WebsiteLearners\AIAgent\Exceptions\AIAgentException;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;

class ImageService implements ImageServiceInterface, HasProviderSwitching, HasModelSwitching
{
    private ProviderFactory $providerFactory;

    private ?ImageGenerationInterface $currentProvider = null;

    private string $currentProviderName = '';

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }

    public function generateImage(string $prompt, array $options = []): string
    {
        $provider = $this->getProvider();

        $params = array_merge([
            'prompt' => $prompt,
            'size' => '1024x1024',
            'quality' => 'standard',
            'n' => 1,
        ], $options);

        return $provider->generateImage($params);
    }

    public function generateMultipleImages(string $prompt, int $count, array $options = []): array
    {
        $provider = $this->getProvider();

        $params = array_merge([
            'prompt' => $prompt,
            'size' => '1024x1024',
            'quality' => 'standard',
            'n' => $count,
        ], $options);

        return $provider->generateImages($params);
    }

    public function setProvider(string $providerName): void
    {
        $provider = $this->providerFactory->create($providerName);
        if (! $provider instanceof ImageGenerationInterface) {
            throw new \InvalidArgumentException('Provider does not support image generation');
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
        $providers = $this->providerFactory->getAvailableProviders('image');
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
            return $provider->supports('image') && $provider->isAvailable();
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

    private function getProvider(): ImageGenerationInterface
    {
        if ($this->currentProvider === null) {
            $provider = $this->providerFactory->createForCapability('image');

            if (! $provider instanceof ImageGenerationInterface) {
                throw new AIAgentException('Provider does not implement ImageGenerationInterface');
            }

            $this->currentProvider = $provider;
            $this->currentProviderName = $provider->getName();
        }

        return $this->currentProvider;
    }
}
