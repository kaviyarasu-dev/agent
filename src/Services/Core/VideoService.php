<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Services\Core;

use WebsiteLearners\AIAgent\Contracts\Capabilities\VideoGenerationInterface;
use WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;

class VideoService implements VideoServiceInterface
{
    private ProviderFactory $providerFactory;

    private ?VideoGenerationInterface $currentProvider = null;

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }

    public function generateVideo(string $prompt, array $options = []): string
    {
        $provider = $this->getProvider();

        $params = array_merge([
            'prompt' => $prompt,
            'duration' => 10,
            'fps' => 30,
        ], $options);

        try {
            return $provider->generateVideo($params);
        } catch (\Exception $e) {
            logger()->error('Video generation failed', [
                'provider' => get_class($provider),
                'error' => $e->getMessage(),
            ]);

            // Attempt with fallback provider
            $this->currentProvider = null;

            return $this->generateVideo($prompt, $options);
        }
    }

    public function getVideoStatus(string $jobId): array
    {
        $provider = $this->getProvider();

        return $provider->getVideoStatus($jobId);
    }

    public function setProvider(string $providerName): void
    {
        $provider = $this->providerFactory->create($providerName);

        if (! $provider instanceof VideoGenerationInterface) {
            throw new \InvalidArgumentException('Provider does not support video generation');
        }

        $this->currentProvider = $provider;
    }

    private function getProvider(): VideoGenerationInterface
    {
        if ($this->currentProvider === null) {
            $provider = $this->providerFactory->createForCapability('video');

            if (! $provider instanceof VideoGenerationInterface) {
                throw new \RuntimeException('Provider does not implement VideoGenerationInterface');
            }

            $this->currentProvider = $provider;
        }

        return $this->currentProvider;
    }
}
