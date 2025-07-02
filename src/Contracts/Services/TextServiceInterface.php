<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Contracts\Services;

interface TextServiceInterface
{
    public function generateText(string $prompt, array $options = []): string;

    public function streamText(string $prompt, array $options = []): iterable;

    public function setProvider(string $providerName): void;
}
