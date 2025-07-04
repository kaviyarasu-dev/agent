<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Traits;

use Kaviyarasu\AIAgent\Factory\ProviderFactory;

/**
 * Trait to add dynamic provider switching capabilities to any class.
 */
trait HasDynamicProvider
{
    protected ?ProviderFactory $providerFactory = null;

    protected string $currentProvider = '';

    /**
     * Initialize the provider factory if not already set.
     */
    protected function initializeProviderFactory(): void
    {
        if ($this->providerFactory === null) {
            $this->providerFactory = app(ProviderFactory::class);
        }
    }

    /**
     * Switch to a different provider.
     */
    public function useProvider(string $providerName): self
    {
        $this->initializeProviderFactory();

        // Update services if they exist
        if (property_exists($this, 'textService') && $this->textService !== null) {
            $this->textService->setProvider($providerName);
        }

        if (property_exists($this, 'imageService') && $this->imageService !== null) {
            $this->imageService->setProvider($providerName);
        }

        if (property_exists($this, 'videoService') && $this->videoService !== null) {
            $this->videoService->setProvider($providerName);
        }

        $this->currentProvider = $providerName;

        return $this;
    }

    /**
     * Get the current provider name.
     */
    public function getCurrentProvider(): string
    {
        return $this->currentProvider ?: 'default';
    }

    /**
     * Get available providers for a capability.
     */
    public function getAvailableProviders(string $capability = 'text'): array
    {
        $this->initializeProviderFactory();

        $providers = $this->providerFactory->getAvailableProviders($capability);

        return array_map(function ($provider) {
            return [
                'name' => $provider->getName(),
                'available' => $provider->isAvailable(),
                'capabilities' => $provider->getCapabilities(),
            ];
        }, $providers);
    }
}
