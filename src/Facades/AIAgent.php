<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Facades;

use Illuminate\Support\Facades\Facade;
use Kaviyarasu\AIAgent\Responses\ImageResponse;
use Kaviyarasu\AIAgent\Responses\TextResponse;

/**
 * @method static \Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface text()
 * @method static \Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface image()
 * @method static \Kaviyarasu\AIAgent\Contracts\Services\VideoServiceInterface video()
 * @method static \Kaviyarasu\AIAgent\AIAgent provider(string $name)
 *
 * Structured Response Methods:
 * @method static TextResponse generateText(string $prompt, array $options = [])
 * @method static ImageResponse generateImage(string $prompt, array $options = [])
 * @method static ImageResponse generateMultipleImages(string $prompt, int $count, array $options = [])
 *
 * Raw Response Methods (Backward Compatibility):
 * @method static string generateTextRaw(string $prompt, array $options = [])
 * @method static string generateImageRaw(string $prompt, array $options = [])
 * @method static array generateMultipleImagesRaw(string $prompt, int $count, array $options = [])
 *
 * @see \Kaviyarasu\AIAgent\AIAgent
 */
class AIAgent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kaviyarasu\AIAgent\AIAgent::class;
    }

    /**
     * Generate text with structured response
     */
    public static function generateText(string $prompt, array $options = []): TextResponse
    {
        return static::text()->generateText($prompt, $options);
    }

    /**
     * Generate text with raw string response (backward compatibility)
     */
    public static function generateTextRaw(string $prompt, array $options = []): string
    {
        return static::text()->generateTextRaw($prompt, $options);
    }

    /**
     * Generate image with structured response
     */
    public static function generateImage(string $prompt, array $options = []): ImageResponse
    {
        return static::image()->generateImage($prompt, $options);
    }

    /**
     * Generate image with raw string response (backward compatibility)
     */
    public static function generateImageRaw(string $prompt, array $options = []): string
    {
        return static::image()->generateImageRaw($prompt, $options);
    }

    /**
     * Generate multiple images with structured response
     */
    public static function generateMultipleImages(string $prompt, int $count, array $options = []): ImageResponse
    {
        return static::image()->generateMultipleImages($prompt, $count, $options);
    }

    /**
     * Generate multiple images with raw array response (backward compatibility)
     */
    public static function generateMultipleImagesRaw(string $prompt, int $count, array $options = []): array
    {
        return static::image()->generateMultipleImagesRaw($prompt, $count, $options);
    }

    /**
     * Stream text generation
     */
    public static function streamText(string $prompt, array $options = []): iterable
    {
        return static::text()->streamText($prompt, $options);
    }

    /**
     * Create instance with specific provider
     */
    public static function withProvider(string $provider): \Kaviyarasu\AIAgent\AIAgent
    {
        return static::provider($provider);
    }

    /**
     * Switch model for current provider
     */
    public static function withModel(string $model): \Kaviyarasu\AIAgent\AIAgent
    {
        $instance = static::getFacadeRoot();
        $instance->text()->switchModel($model);

        return $instance;
    }

    /**
     * Get available providers for capability
     */
    public static function getAvailableProviders(string $capability = 'text'): array
    {
        return match ($capability) {
            'text' => static::text()->getAvailableProviders(),
            'image' => static::image()->getAvailableProviders(),
            'video' => static::video()->getAvailableProviders(),
            default => [],
        };
    }

    /**
     * Check if provider supports capability
     */
    public static function hasProvider(string $provider, string $capability = 'text'): bool
    {
        return match ($capability) {
            'text' => static::text()->hasProvider($provider),
            'image' => static::image()->hasProvider($provider),
            'video' => static::video()->hasProvider($provider),
            default => false,
        };
    }
}
