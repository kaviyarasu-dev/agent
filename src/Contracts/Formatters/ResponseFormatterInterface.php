<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Contracts\Formatters;

use Kaviyarasu\AIAgent\Responses\AIResponse;

interface ResponseFormatterInterface
{
    /**
     * Format a successful response
     */
    public function formatSuccess(mixed $data, array $metadata = []): AIResponse;

    /**
     * Format an error response
     */
    public function formatError(string $error, array $metadata = []): AIResponse;

    /**
     * Format response with timing information
     */
    public function formatWithTiming(mixed $data, float $startTime, array $metadata = []): AIResponse;

    /**
     * Get the response type this formatter handles
     */
    public function getResponseType(): string;
}
