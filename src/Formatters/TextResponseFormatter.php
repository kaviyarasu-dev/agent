<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Formatters;

use Kaviyarasu\AIAgent\Contracts\Formatters\ResponseFormatterInterface;
use Kaviyarasu\AIAgent\Responses\AIResponse;
use Kaviyarasu\AIAgent\Responses\TextResponse;

class TextResponseFormatter implements ResponseFormatterInterface
{
    public function formatSuccess(mixed $data, array $metadata = []): AIResponse
    {
        return TextResponse::fromText(
            $data,
            $metadata['provider'] ?? null,
            $metadata['model'] ?? null,
            $metadata
        );
    }

    public function formatError(string $error, array $metadata = []): AIResponse
    {
        return TextResponse::fromError(
            $error,
            $metadata['provider'] ?? null,
            $metadata['model'] ?? null,
            $metadata
        );
    }

    public function formatWithTiming(mixed $data, float $startTime, array $metadata = []): AIResponse
    {
        $processingTime = microtime(true) - $startTime;
        $metadata['processing_time'] = round($processingTime, 3);

        return $this->formatSuccess($data, $metadata);
    }

    public function getResponseType(): string
    {
        return 'text';
    }

    /**
     * Format streaming response metadata
     */
    public function formatStreamMetadata(array $metadata = []): array
    {
        return array_merge([
            'stream' => true,
            'chunk_count' => 0,
        ], $metadata);
    }

    /**
     * Format response with token usage information
     */
    public function formatWithTokens(
        string $text,
        int $tokensUsed,
        array $metadata = []
    ): TextResponse {
        $metadata['tokens_used'] = $tokensUsed;

        return TextResponse::fromText(
            $text,
            $metadata['provider'] ?? null,
            $metadata['model'] ?? null,
            $metadata
        );
    }
}
