<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Contracts\Services;

use Kaviyarasu\AIAgent\Responses\TextResponse;

interface TextServiceInterface
{
    /**
     * Generate text with structured response
     */
    public function generateText(string $prompt, array $options = []): TextResponse;

    /**
     * Generate text with raw string response (backward compatibility)
     */
    public function generateTextRaw(string $prompt, array $options = []): string;

    /**
     * Stream text generation
     */
    public function streamText(string $prompt, array $options = []): iterable;

    /**
     * Set provider for text generation
     */
    public function setProvider(string $providerName): void;
}
