<?php

declare(strict_types=1);

namespace App\AI\Contracts\Services;

interface ImageServiceInterface
{
    public function generateImage(string $prompt, array $options = []): string;
    
    public function generateMultipleImages(string $prompt, int $count, array $options = []): array;
    
    public function setProvider(string $providerName): void;
}