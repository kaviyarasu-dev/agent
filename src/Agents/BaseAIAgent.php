<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Agents;

use WebsiteLearners\AIAgent\Contracts\HasProviderSwitching;
use WebsiteLearners\AIAgent\Contracts\HasModelSwitching;
use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\ImageServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface;
use WebsiteLearners\AIAgent\Exceptions\AIAgentException;
use WebsiteLearners\AIAgent\Factory\ServiceFactory;

/**
 * Base class for all AI Agents with provider and model switching capabilities.
 */
abstract class BaseAIAgent implements HasProviderSwitching, HasModelSwitching
{
    protected ?TextServiceInterface $textService = null;
    protected ?ImageServiceInterface $imageService = null;
    protected ?VideoServiceInterface $videoService = null;
    protected ServiceFactory $serviceFactory;

    /**
     * Track current provider and model for restoration
     */
    protected array $providerStack = [];
    protected array $modelStack = [];

    /**
     * Service requirements that the agent needs
     */
    protected array $requiredServices = [];

    /**
     * Current provider name for the agent
     */
    protected string $currentProvider = '';

    /**
     * Current model name for the agent
     */
    protected string $currentModel = '';

    public function __construct(ServiceFactory $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;
        $this->initializeServices();
    }

    /**
     * Initialize required services based on agent configuration
     */
    protected function initializeServices(): void
    {
        if (in_array('text', $this->requiredServices)) {
            $this->textService = $this->serviceFactory->createTextService();
        }

        if (in_array('image', $this->requiredServices)) {
            $this->imageService = $this->serviceFactory->createImageService();
        }

        if (in_array('video', $this->requiredServices)) {
            $this->videoService = $this->serviceFactory->createVideoService();
        }
    }

    /**
     * Execute the agent's main logic
     *
     * @param array $data
     * @return mixed
     */
    abstract public function execute(array $data);

    /**
     * {@inheritdoc}
     */
    public function switchProvider(string $providerName): self
    {
        // Store current provider for potential restoration
        if ($this->currentProvider) {
            $this->providerStack[] = $this->currentProvider;
        }

        // Switch provider on all services
        if ($this->textService && method_exists($this->textService, 'switchProvider')) {
            $this->textService->switchProvider($providerName);
        }

        if ($this->imageService && method_exists($this->imageService, 'switchProvider')) {
            $this->imageService->switchProvider($providerName);
        }

        if ($this->videoService && method_exists($this->videoService, 'switchProvider')) {
            $this->videoService->switchProvider($providerName);
        }

        $this->currentProvider = $providerName;
        $this->serviceFactory->setDefaultProvider($providerName);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentProvider(): string
    {
        return $this->currentProvider ?: 'default';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableProviders(): array
    {
        $providers = [];

        // Aggregate providers from all services
        if ($this->textService && method_exists($this->textService, 'getAvailableProviders')) {
            $textProviders = $this->textService->getAvailableProviders();
            foreach ($textProviders as $name => $info) {
                $providers[$name] = $info;
                $providers[$name]['supports'][] = 'text';
            }
        }

        if ($this->imageService && method_exists($this->imageService, 'getAvailableProviders')) {
            $imageProviders = $this->imageService->getAvailableProviders();
            foreach ($imageProviders as $name => $info) {
                if (!isset($providers[$name])) {
                    $providers[$name] = $info;
                    $providers[$name]['supports'] = [];
                }
                $providers[$name]['supports'][] = 'image';
            }
        }

        if ($this->videoService && method_exists($this->videoService, 'getAvailableProviders')) {
            $videoProviders = $this->videoService->getAvailableProviders();
            foreach ($videoProviders as $name => $info) {
                if (!isset($providers[$name])) {
                    $providers[$name] = $info;
                    $providers[$name]['supports'] = [];
                }
                $providers[$name]['supports'][] = 'video';
            }
        }

        return $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function hasProvider(string $providerName): bool
    {
        $providers = $this->getAvailableProviders();
        return isset($providers[$providerName]);
    }

    /**
     * {@inheritdoc}
     */
    public function withProvider(string $providerName, callable $callback)
    {
        $originalProvider = $this->currentProvider;

        try {
            $this->switchProvider($providerName);
            return $callback($this);
        } finally {
            if ($originalProvider) {
                $this->switchProvider($originalProvider);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function switchModel(string $model): self
    {
        // Store current model for potential restoration
        if ($this->currentModel) {
            $this->modelStack[] = $this->currentModel;
        }

        // Switch model on all services
        if ($this->textService && method_exists($this->textService, 'switchModel')) {
            $this->textService->switchModel($model);
        }

        if ($this->imageService && method_exists($this->imageService, 'switchModel')) {
            $this->imageService->switchModel($model);
        }

        if ($this->videoService && method_exists($this->videoService, 'switchModel')) {
            $this->videoService->switchModel($model);
        }

        $this->currentModel = $model;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentModel(): string
    {
        // Get model from the first available service
        if ($this->textService && method_exists($this->textService, 'getCurrentModel')) {
            return $this->textService->getCurrentModel();
        }

        if ($this->imageService && method_exists($this->imageService, 'getCurrentModel')) {
            return $this->imageService->getCurrentModel();
        }

        if ($this->videoService && method_exists($this->videoService, 'getCurrentModel')) {
            return $this->videoService->getCurrentModel();
        }

        return $this->currentModel ?: 'unknown';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableModels(): array
    {
        $models = [];

        // Aggregate models from the primary service based on required services
        if (in_array('text', $this->requiredServices) && $this->textService && method_exists($this->textService, 'getAvailableModels')) {
            return $this->textService->getAvailableModels();
        }

        if (in_array('image', $this->requiredServices) && $this->imageService && method_exists($this->imageService, 'getAvailableModels')) {
            return $this->imageService->getAvailableModels();
        }

        if (in_array('video', $this->requiredServices) && $this->videoService && method_exists($this->videoService, 'getAvailableModels')) {
            return $this->videoService->getAvailableModels();
        }

        return $models;
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
        // Get capabilities from the primary service
        if (in_array('text', $this->requiredServices) && $this->textService && method_exists($this->textService, 'getModelCapabilities')) {
            return $this->textService->getModelCapabilities($model);
        }

        if (in_array('image', $this->requiredServices) && $this->imageService && method_exists($this->imageService, 'getModelCapabilities')) {
            return $this->imageService->getModelCapabilities($model);
        }

        if (in_array('video', $this->requiredServices) && $this->videoService && method_exists($this->videoService, 'getModelCapabilities')) {
            return $this->videoService->getModelCapabilities($model);
        }

        return [];
    }

    /**
     * Execute with specific provider and model configuration
     *
     * @param array $data
     * @param string|null $provider
     * @param string|null $model
     * @return mixed
     */
    public function executeWith(array $data, ?string $provider = null, ?string $model = null)
    {
        if ($provider === null && $model === null) {
            return $this->execute($data);
        }

        $callback = fn() => $this->execute($data);

        if ($provider !== null && $model !== null) {
            return $this->withProvider($provider, fn() => $this->withModel($model, $callback));
        }

        if ($provider !== null) {
            return $this->withProvider($provider, $callback);
        }

        return $this->withModel($model, $callback);
    }

    /**
     * Execute with fallback providers
     *
     * @param array $data
     * @param array $providers List of providers to try in order
     * @return mixed
     * @throws \Exception if all providers fail
     */
    public function executeWithFallback(array $data, array $providers)
    {
        $lastException = null;

        foreach ($providers as $provider) {
            try {
                if (is_array($provider)) {
                    // Provider with specific model
                    return $this->executeWith($data, $provider['provider'] ?? null, $provider['model'] ?? null);
                } else {
                    // Just provider name
                    return $this->executeWith($data, $provider);
                }
            } catch (\Exception $e) {
                $lastException = $e;
                logger()->warning("Provider {$provider} failed: " . $e->getMessage());
                continue;
            }
        }

        throw $lastException ?: new AIAgentException('All providers failed');
    }

    /**
     * Get service requirements for this agent
     *
     * @return array
     */
    public function getRequiredServices(): array
    {
        return $this->requiredServices;
    }

    /**
     * Check if agent has a specific service
     *
     * @param string $service
     * @return bool
     */
    public function hasService(string $service): bool
    {
        return match ($service) {
            'text' => $this->textService !== null,
            'image' => $this->imageService !== null,
            'video' => $this->videoService !== null,
            default => false,
        };
    }

    /**
     * Get text service if available
     *
     * @return TextServiceInterface|null
     */
    public function getTextService(): ?TextServiceInterface
    {
        return $this->textService;
    }

    /**
     * Get image service if available
     *
     * @return ImageServiceInterface|null
     */
    public function getImageService(): ?ImageServiceInterface
    {
        return $this->imageService;
    }

    /**
     * Get video service if available
     *
     * @return VideoServiceInterface|null
     */
    public function getVideoService(): ?VideoServiceInterface
    {
        return $this->videoService;
    }
}
