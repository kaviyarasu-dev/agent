<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Factory;

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

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }

    public function createTextService(): TextServiceInterface
    {
        if (! isset($this->serviceInstances['text'])) {
            $this->serviceInstances['text'] = new TextService($this->providerFactory);
        }

        return $this->serviceInstances['text'];
    }

    public function createImageService(): ImageServiceInterface
    {
        if (! isset($this->serviceInstances['image'])) {
            $this->serviceInstances['image'] = new ImageService($this->providerFactory);
        }

        return $this->serviceInstances['image'];
    }

    public function createVideoService(): VideoServiceInterface
    {
        if (! isset($this->serviceInstances['video'])) {
            $this->serviceInstances['video'] = new VideoService($this->providerFactory);
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
}
