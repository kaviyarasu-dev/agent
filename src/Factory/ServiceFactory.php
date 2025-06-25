<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Factory;

use WebsiteLearners\AIAgent\Config\AIConfigManager;
use WebsiteLearners\AIAgent\Contracts\Services\ImageServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface;
use WebsiteLearners\AIAgent\Services\Core\ImageService;
use WebsiteLearners\AIAgent\Services\Core\TextService;
use WebsiteLearners\AIAgent\Services\Core\VideoService;

class ServiceFactory
{
    private ProviderFactory $providerFactory;
    private array $serviceInstances = [];
    private ?string $defaultProvider = null;

    /**
     * @phpstan-ignore-next-line
     */
    public function __construct(ProviderFactory $providerFactory, AIConfigManager $configManager)
    {
        $this->providerFactory = $providerFactory;
        // Config manager is passed for future use but not currently needed
    }

    public function createTextService(): TextServiceInterface
    {
        if (! isset($this->serviceInstances['text'])) {
            $service = new TextService($this->providerFactory);
            if ($this->defaultProvider) {
                $service->setProvider($this->defaultProvider);
            }
            $this->serviceInstances['text'] = $service;
        }

        return $this->serviceInstances['text'];
    }

    public function createImageService(): ImageServiceInterface
    {
        if (! isset($this->serviceInstances['image'])) {
            $service = new ImageService($this->providerFactory);
            if ($this->defaultProvider) {
                $service->setProvider($this->defaultProvider);
            }
            $this->serviceInstances['image'] = $service;
        }

        return $this->serviceInstances['image'];
    }

    public function createVideoService(): VideoServiceInterface
    {
        if (! isset($this->serviceInstances['video'])) {
            $service = new VideoService($this->providerFactory);
            if ($this->defaultProvider) {
                $service->setProvider($this->defaultProvider);
            }
            $this->serviceInstances['video'] = $service;
        }

        return $this->serviceInstances['video'];
    }

    public function createService(string $type): object
    {
        return match ($type) {
            'text' => $this->createTextService(),
            'image' => $this->createImageService(),
            'video' => $this->createVideoService(),
            default => throw new \InvalidArgumentException("Unknown service type: {$type}"),
        };
    }

    public function setDefaultProvider(string $provider): void
    {
        $this->defaultProvider = $provider;
        // Clear service instances to force recreation with new provider
        $this->serviceInstances = [];
    }
}
