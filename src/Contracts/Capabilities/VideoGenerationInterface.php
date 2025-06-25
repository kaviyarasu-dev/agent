<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Contracts\Capabilities;

interface VideoGenerationInterface
{
    public function generateVideo(array $params): string;

    public function getVideoStatus(string $jobId): array;

    public function getSupportedFormats(): array;

    public function getMaxDuration(): int;
}
