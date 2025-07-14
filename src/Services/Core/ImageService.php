<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Services\Core;

use Kaviyarasu\AIAgent\Contracts\Capabilities\ImageGenerationInterface;
use Kaviyarasu\AIAgent\Contracts\HasModelSwitching;
use Kaviyarasu\AIAgent\Contracts\HasProviderSwitching;
use Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface;
use Kaviyarasu\AIAgent\Exceptions\AIAgentException;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;
use Kaviyarasu\AIAgent\Responses\ImageResponse;
use Kaviyarasu\AIAgent\Traits\FormatsResponses;

class ImageService implements HasModelSwitching, HasProviderSwitching, ImageServiceInterface
{
    use FormatsResponses;

    private ProviderFactory $providerFactory;
    private ?ImageGenerationInterface $currentProvider = null;
    private string $currentProviderName = '';

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }

    /**
     * Generate single image with structured response
     */
    public function generateImage(string $prompt, array $options = []): ImageResponse
    {
        $startTime = microtime(true);
        
        try {
            $provider = $this->getProvider();
            $model = $provider->getModelCapabilities();
            $params = $this->buildParams($prompt, $options, $model);
            
            $result = $provider->generateImage($params);
            
            // Extract metadata
            $metadata = $this->extractMetadata($params, $model);
            
            return $this->formatWithTiming($result, $startTime, $metadata);
        } catch (\Exception $e) {
            logger()->error('Image generation failed', [
                'provider' => $this->getCurrentProvider(),
                'error' => $e->getMessage(),
            ]);

            $processingTime = microtime(true) - $startTime;
            $metadata = [
                'processing_time' => round($processingTime, 3),
            ];

            return $this->formatError($e->getMessage(), $metadata);
        }
    }

    /**
     * Generate single image with raw string response (backward compatibility)
     */
    public function generateImageRaw(string $prompt, array $options = []): string
    {
        $response = $this->generateImage($prompt, $options);
        return $response->getUrl() ?? '';
    }

    /**
     * Generate multiple images with structured response
     */
    public function generateMultipleImages(string $prompt, int $count, array $options = []): ImageResponse
    {
        $startTime = microtime(true);
        
        try {
            $provider = $this->getProvider();
            $model = $provider->getModelCapabilities();
            $params = $this->buildParams($prompt, $options, $model);
            $params['n'] = $count;
            
            $result = $provider->generateImages($params);
            
            // Extract metadata
            $metadata = $this->extractMetadata($params, $model);
            $metadata['count'] = $count;
            
            return $this->formatWithTiming($result, $startTime, $metadata);
        } catch (\Exception $e) {
            logger()->error('Multiple image generation failed', [
                'provider' => $this->getCurrentProvider(),
                'error' => $e->getMessage(),
            ]);

            $processingTime = microtime(true) - $startTime;
            $metadata = [
                'processing_time' => round($processingTime, 3),
                'count' => $count,
            ];

            return $this->formatError($e->getMessage(), $metadata);
        }
    }

    /**
     * Generate multiple images with raw array response (backward compatibility)
     */
    public function generateMultipleImagesRaw(string $prompt, int $count, array $options = []): array
    {
        $response = $this->generateMultipleImages($prompt, $count, $options);
        return $response->getUrls();
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
                        logger()->warning('Could not restore original model: '.$e->getMessage());
                    }
                }
            }
        }
    }

    public function switchModel(string $model): self
    {
        $provider = $this->getProvider();

        if (! method_exists($provider, 'switchModel')) {
            throw new AIAgentException('Current provider does not support model switching');
        }

        $provider->switchModel($model);

        return $this;
    }

    public function getCurrentModel(): string
    {
        $provider = $this->getProvider();

        if (! method_exists($provider, 'getCurrentModel')) {
            return 'unknown';
        }

        return $provider->getCurrentModel();
    }

    public function getAvailableModels(): array
    {
        $provider = $this->getProvider();

        if (! method_exists($provider, 'getAvailableModels')) {
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
                    logger()->warning('Could not restore original model: '.$e->getMessage());
                }
            }
        }
    }

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
        return 'image';
    }

    /**
     * Build parameters for image generation
     */
    private function buildParams(string $prompt, array $options, array $model): array
    {
        return array_merge([
            'prompt' => $prompt,
            'size' => $model['default_size'] ?? '',
            'style' => $model['default_style'] ?? '',
            'n' => 1,
        ], $options);
    }

    /**
     * Extract metadata from generation parameters and model
     */
    private function extractMetadata(array $params, array $model): array
    {
        $metadata = [];

        // Extract parameters that were used
        if (isset($params['size'])) {
            $metadata['size'] = $params['size'];
        }
        if (isset($params['style'])) {
            $metadata['style'] = $params['style'];
        }
        if (isset($params['quality'])) {
            $metadata['quality'] = $params['quality'];
        }

        // Add model capabilities
        if (isset($model['default_size'])) {
            $metadata['default_size'] = $model['default_size'];
        }
        if (isset($model['default_style'])) {
            $metadata['default_style'] = $model['default_style'];
        }

        return $metadata;
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
