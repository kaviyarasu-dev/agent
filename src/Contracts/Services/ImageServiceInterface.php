<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Contracts\Services;

use Kaviyarasu\AIAgent\Responses\ImageResponse;

interface ImageServiceInterface
{
    /**
     * Generate single image with structured response
     */
    public function generateImage(string $prompt, array $options = []): ImageResponse;

    /**
     * Generate single image with raw string response (backward compatibility)
     */
    public function generateImageRaw(string $prompt, array $options = []): string;

    /**
     * Generate multiple images with structured response
     */
    public function generateMultipleImages(string $prompt, int $count, array $options = []): ImageResponse;

    /**
     * Generate multiple images with raw array response (backward compatibility)
     */
    public function generateMultipleImagesRaw(string $prompt, int $count, array $options = []): array;

    /**
     * Set provider for image generation
     */
    public function setProvider(string $providerName): void;
}
