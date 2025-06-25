<?php

declare(strict_types=1);

namespace App\AI\Contracts\Capabilities;

interface VideoGenerationInterface
{
    public function generateVideo(array $params): string;
    
    public function getVideoStatus(string $jobId): array;
    
    public function getSupportedFormats(): array;
    
    public function getMaxDuration(): int;
}