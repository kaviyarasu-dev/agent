<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Services\Core;

use Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface;
use Kaviyarasu\AIAgent\Contracts\HasModelSwitching;
use Kaviyarasu\AIAgent\Contracts\HasProviderSwitching;
use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;
use Kaviyarasu\AIAgent\Exceptions\AIAgentException;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;
use Kaviyarasu\AIAgent\Responses\TextResponse;
use Kaviyarasu\AIAgent\Traits\FormatsResponses;

class TextService implements HasModelSwitching, HasProviderSwitching, TextServiceInterface
{
    use FormatsResponses;

    private ProviderFactory $providerFactory;

    private ?TextGenerationInterface $currentProvider = null;

    private string $currentProviderName = '';

    private string $originalProviderName = '';

    private string $originalModel = '';

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }

    /**
     * Generate text with structured response
     */
    public function generateText(string $prompt, array $options = []): TextResponse
    {
        $startTime = microtime(true);

        try {
            $provider = $this->getProvider();
            $params = $this->buildParams($prompt, $options);

            $result = $provider->generateText($params);

            // Extract token usage if available
            $metadata = $this->extractMetadata($params, $result);

            return $this->formatWithTiming($result, $startTime, $metadata);
        } catch (\Exception $e) {
            logger()->error('Text generation failed', [
                'provider' => $this->getCurrentProvider(),
                'error' => $e->getMessage(),
            ]);

            // Attempt fallback
            $processingTime = microtime(true) - $startTime;
            $metadata = [
                'processing_time' => round($processingTime, 3),
                'attempted_fallback' => true,
            ];

            try {
                $this->currentProvider = null;

                return $this->generateText($prompt, $options);
            } catch (\Exception $fallbackError) {
                return $this->formatError($fallbackError->getMessage(), $metadata);
            }
        }
    }

    /**
     * Generate text with raw string response (backward compatibility)
     */
    public function generateTextRaw(string $prompt, array $options = []): string
    {
        $response = $this->generateText($prompt, $options);

        return $response->getText() ?? '';
    }

    public function streamText(string $prompt, array $options = []): iterable
    {
        $provider = $this->getProvider();
        $params = $this->buildParams($prompt, $options);
        $params['stream'] = true;

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
                        logger()->warning('Could not restore original model: '.$e->getMessage());
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

        if (! method_exists($provider, 'switchModel')) {
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

        if (! method_exists($provider, 'getCurrentModel')) {
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

        if (! method_exists($provider, 'getAvailableModels')) {
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
                    logger()->warning('Could not restore original model: '.$e->getMessage());
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

        if (! method_exists($provider, 'getModelCapabilities')) {
            return [];
        }

        return $provider->getModelCapabilities($model);
    }

    /**
     * Get the formatter type for this service
     */
    protected function getFormatterType(): string
    {
        return 'text';
    }

    /**
     * Build parameters for text generation
     */
    private function buildParams(string $prompt, array $options): array
    {
        return array_merge([
            'prompt' => $prompt,
            'temperature' => 1,
            'max_tokens' => 1000,
        ], $options);
    }

    /**
     * Extract metadata from generation result
     */
    private function extractMetadata(array $params, mixed $result): array
    {
        $metadata = [];

        // Extract parameters that were used
        if (isset($params['temperature'])) {
            $metadata['temperature'] = $params['temperature'];
        }
        if (isset($params['max_tokens'])) {
            $metadata['max_tokens'] = $params['max_tokens'];
        }

        // Try to extract token usage from result if it's structured
        if (is_array($result) && isset($result['usage'])) {
            $metadata['tokens_used'] = $result['usage']['total_tokens'] ?? 0;
        }

        return $metadata;
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
