<?php

declare(strict_types=1);

namespace App\AI\Contracts\Capabilities;

interface ImageGenerationInterface
{
    public function generateImage(array $params): string;
    
    public function generateImages(array $params): array;
    
    public function getSupportedFormats(): array;
    
    public function getMaxResolution(): array;
}