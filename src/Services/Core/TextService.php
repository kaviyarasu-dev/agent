<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Services\Core;

use WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface;
use WebsiteLearners\AIAgent\Contracts\HasProviderSwitching;
use WebsiteLearners\AIAgent\Contracts\HasModelSwitching;
use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use WebsiteLearners\AIAgent\Exceptions\AIAgentException;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;

class TextService implements TextServiceInterface, HasProviderSwitching, HasModelSwitching
{
    private ProviderFactory $providerFactory;

    private ?TextGenerationInterface $currentProvider = null;

    private string $currentProviderName = '';

    private string $originalProviderName = '';

    private string $originalModel = '';

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $provider = $this->getProvider();

        $params = array_merge([
            'prompt' => $prompt,
            'temperature' => 1,
            'max_tokens' => 1000,
        ], $options);

        try {
            return $provider->generateText($params);
        } catch (\Exception $e) {
            logger()->error('Text generation failed', [
                'provider' => get_class($provider),
                'error' => $e->getMessage(),
            ]);

            // Attempt with fallback provider
            $this->currentProvider = null;

            return $this->generateText($prompt, $options);
        }
    }

    public function streamText(string $prompt, array $options = []): iterable
    {
        $provider = $this->getProvider();

        $params = array_merge([
            'prompt' => $prompt,
            'stream' => true,
        ], $options);

        return $provider->streamText($params);
    }

    public function setProvider(string $providerName): void
    {
        $provider = $this->providerFactory->create($providerName);

        if (! $provider instanceof TextGenerationInterface) {
            throw new \InvalidArgumentException('Provider does not support text generation');
        }

        $this->currentProvider = $provider;
        $this->currentProviderName = $providerName;
    }

    /**
     * {@inheritdoc}
     */
    public function switchProvider(string $providerName): self
    {
        $this->setProvider($providerName);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentProvider(): string
    {
        return $this->currentProviderName ?: 'default';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableProviders(): array
    {
        $providers = $this->providerFactory->getAvailableProviders('text');
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

    /**
     * {@inheritdoc}
     */
    public function hasProvider(string $providerName): bool
    {
        try {
            $provider = $this->providerFactory->create($providerName);
            return $provider->supports('text') && $provider->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
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
                        // Log but don't throw
                        logger()->warning('Could not restore original model: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function switchModel(string $model): self
    {
        $provider = $this->getProvider();

        if (!method_exists($provider, 'switchModel')) {
            throw new AIAgentException('Current provider does not support model switching');
        }

        $provider->switchModel($model);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentModel(): string
    {
        $provider = $this->getProvider();

        if (!method_exists($provider, 'getCurrentModel')) {
            return 'unknown';
        }

        return $provider->getCurrentModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableModels(): array
    {
        $provider = $this->getProvider();

        if (!method_exists($provider, 'getAvailableModels')) {
            return [];
        }

        return $provider->getAvailableModels();
    }

    /**
     * {@inheritdoc}
     */
    public function hasModel(string $model): bool
    {
        return in_array($model, $this->getAvailableModels(), true);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getModelCapabilities(?string $model = null): array
    {
        $provider = $this->getProvider();

        if (!method_exists($provider, 'getModelCapabilities')) {
            return [];
        }

        return $provider->getModelCapabilities($model);
    }

    private function getProvider(): TextGenerationInterface
    {
        if ($this->currentProvider === null) {
            $provider = $this->providerFactory->createForCapability('text');

            if (! $provider instanceof TextGenerationInterface) {
                throw new AIAgentException('Provider does not implement TextGenerationInterface');
            }

            $this->currentProvider = $provider;
            $this->currentProviderName = $provider->getName();
        }

        return $this->currentProvider;
    }
}
