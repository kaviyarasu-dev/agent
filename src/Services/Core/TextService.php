<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Services\Core;

use WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface;
use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;

class TextService implements TextServiceInterface
{
    private ProviderFactory $providerFactory;

    private ?TextGenerationInterface $currentProvider = null;

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $provider = $this->getProvider();

        $params = array_merge([
            'prompt' => $prompt,
            'temperature' => 0.7,
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
    }

    private function getProvider(): TextGenerationInterface
    {
        if ($this->currentProvider === null) {
            $provider = $this->providerFactory->createForCapability('text');

            if (! $provider instanceof TextGenerationInterface) {
                throw new \RuntimeException('Provider does not implement TextGenerationInterface');
            }

            $this->currentProvider = $provider;
        }

        return $this->currentProvider;
    }
}
