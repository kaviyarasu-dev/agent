<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent;

use WebsiteLearners\AIAgent\Contracts\Services\ImageServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface;
use WebsiteLearners\AIAgent\Factory\ServiceFactory;

class AIAgent
{
    public function __construct(
        protected ServiceFactory $serviceFactory
    ) {}

    /**
     * Get the text service
     */
    public function text(): TextServiceInterface
    {
        return $this->serviceFactory->createTextService();
    }

    /**
     * Get the image service
     */
    public function image(): ImageServiceInterface
    {
        return $this->serviceFactory->createImageService();
    }

    /**
     * Get the video service
     */
    public function video(): VideoServiceInterface
    {
        return $this->serviceFactory->createVideoService();
    }

    /**
     * Set the provider for subsequent operations
     */
    public function provider(string $name): self
    {
        $this->serviceFactory->setDefaultProvider($name);

        return $this;
    }

    /**
     * Get the service factory
     */
    public function getServiceFactory(): ServiceFactory
    {
        return $this->serviceFactory;
    }
}
