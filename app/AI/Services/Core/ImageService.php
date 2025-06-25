<?php

declare(strict_types=1);

namespace App\AI\Services\Core;

use App\AI\Contracts\Services\ImageServiceInterface;
use App\AI\Contracts\Capabilities\ImageGenerationInterface;
use App\AI\Factory\ProviderFactory;

class ImageService implements ImageServiceInterface
{
    private ProviderFactory $providerFactory;
    private ?ImageGenerationInterface $currentProvider = null;
    
    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }
    
    public function generateImage(string $prompt, array $options = []): string
    {
        $provider = $this->getProvider();
        
        $params = array_merge([
            'prompt' => $prompt,
            'size' => '1024x1024',
            'quality' => 'standard',
        ], $options);
        
        try {
            return $provider->generateImage($params);
        } catch (\Exception $e) {
            logger()->error('Image generation failed', [
                'provider' => get_class($provider),
                'error' => $e->getMessage(),
            ]);
            
            // Attempt with fallback provider
            $this->currentProvider = null;
            return $this->generateImage($prompt, $options);
        }
    }
    
    public function generateMultipleImages(string $prompt, int $count, array $options = []): array
    {
        $provider = $this->getProvider();
        
        $params = array_merge([
            'prompt' => $prompt,
            'count' => $count,
            'size' => '1024x1024',
        ], $options);
        
        try {
            return $provider->generateImages($params);
        } catch (\Exception $e) {
            logger()->error('Multiple image generation failed', [
                'provider' => get_class($provider),
                'error' => $e->getMessage(),
            ]);
            
            // Attempt with fallback provider
            $this->currentProvider = null;
            return $this->generateMultipleImages($prompt, $count, $options);
        }
    }
    
    public function setProvider(string $providerName): void
    {
        $provider = $this->providerFactory->create($providerName);
        
        if (!$provider instanceof ImageGenerationInterface) {
            throw new \InvalidArgumentException("Provider does not support image generation");
        }
        
        $this->currentProvider = $provider;
    }
    
    private function getProvider(): ImageGenerationInterface
    {
        if ($this->currentProvider === null) {
            $provider = $this->providerFactory->createForCapability('image');
            
            if (!$provider instanceof ImageGenerationInterface) {
                throw new \RuntimeException("Provider does not implement ImageGenerationInterface");
            }
            
            $this->currentProvider = $provider;
        }
        
        return $this->currentProvider;
    }
}