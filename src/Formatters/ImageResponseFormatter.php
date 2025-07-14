<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Formatters;

use Kaviyarasu\AIAgent\Contracts\Formatters\ResponseFormatterInterface;
use Kaviyarasu\AIAgent\Responses\AIResponse;
use Kaviyarasu\AIAgent\Responses\ImageResponse;

class ImageResponseFormatter implements ResponseFormatterInterface
{
    public function formatSuccess(mixed $data, array $metadata = []): AIResponse
    {
        if (is_array($data) && count($data) > 1) {
            return ImageResponse::fromUrls(
                $data,
                $metadata['provider'] ?? null,
                $metadata['model'] ?? null,
                $metadata
            );
        }

        $url = is_array($data) ? ($data[0] ?? '') : $data;

        return ImageResponse::fromUrl(
            $url,
            $metadata['provider'] ?? null,
            $metadata['model'] ?? null,
            $metadata
        );
    }

    public function formatError(string $error, array $metadata = []): AIResponse
    {
        return ImageResponse::fromError(
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
        return 'image';
    }

    /**
     * Format multiple images response
     */
    public function formatMultipleImages(
        array $urls,
        array $metadata = []
    ): ImageResponse {
        $metadata['count'] = count($urls);

        return ImageResponse::fromUrls(
            $urls,
            $metadata['provider'] ?? null,
            $metadata['model'] ?? null,
            $metadata
        );
    }

    /**
     * Format single image response
     */
    public function formatSingleImage(
        string $url,
        array $metadata = []
    ): ImageResponse {
        $metadata['count'] = 1;

        return ImageResponse::fromUrl(
            $url,
            $metadata['provider'] ?? null,
            $metadata['model'] ?? null,
            $metadata
        );
    }

    /**
     * Format response with image specifications
     */
    public function formatWithSpecs(
        mixed $data,
        ?string $size = null,
        ?string $quality = null,
        ?string $style = null,
        array $metadata = []
    ): ImageResponse {
        if ($size) {
            $metadata['size'] = $size;
        }
        if ($quality) {
            $metadata['quality'] = $quality;
        }
        if ($style) {
            $metadata['style'] = $style;
        }

        return $this->formatSuccess($data, $metadata);
    }
}
