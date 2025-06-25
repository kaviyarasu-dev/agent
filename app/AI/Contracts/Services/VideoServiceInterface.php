<?php

declare(strict_types=1);

namespace App\AI\Contracts\Services;

interface VideoServiceInterface
{
    public function generateVideo(string $prompt, array $options = []): string;
    
    public function getVideoStatus(string $jobId): array;
    
    public function setProvider(string $providerName): void;
}