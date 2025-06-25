<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Providers\AI\OpenAI;

use Illuminate\Support\Facades\Http;
use WebsiteLearners\AIAgent\Contracts\Capabilities\ImageGenerationInterface;
use WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface;
use WebsiteLearners\AIAgent\Contracts\Capabilities\VideoGenerationInterface;
use WebsiteLearners\AIAgent\Providers\AI\AbstractProvider;

class OpenAIProvider extends AbstractProvider implements TextGenerationInterface, ImageGenerationInterface, VideoGenerationInterface
{
    protected array $supportedModels = [
        'gpt-4',
        'gpt-4-turbo',
        'gpt-3.5-turbo',
        'dall-e-3',
        'dall-e-2',
    ];

    private const API_BASE_URL = 'https://api.openai.com/v1';

    public function getName(): string
    {
        return 'OpenAI';
    }

    public function getVersion(): string
    {
        return '1.0';
    }

    public function supports(string $capability): bool
    {
        return match ($capability) {
            'text' => in_array($this->currentModel, ['gpt-4', 'gpt-4-turbo', 'gpt-3.5-turbo']),
            'image' => in_array($this->currentModel, ['dall-e-3', 'dall-e-2']),
            'video' => false, // Not yet supported
            default => false,
        };
    }

    public function getCapabilities(): array
    {
        return ['text', 'image'];
    }

    public function generateText(array $params): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::API_BASE_URL . '/chat/completions', [
            'model' => $this->currentModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $params['prompt'],
                ],
            ],
            'temperature' => $params['temperature'] ?? 0.7,
            'max_tokens' => $params['max_tokens'] ?? 1000,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json()['choices'][0]['message']['content'] ?? '';
    }

    public function streamText(array $params): iterable
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->withOptions([
            'stream' => true,
        ])->post(self::API_BASE_URL . '/chat/completions', [
            'model' => $this->currentModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $params['prompt'],
                ],
            ],
            'temperature' => $params['temperature'] ?? 0.7,
            'max_tokens' => $params['max_tokens'] ?? 1000,
            'stream' => true,
        ]);

        // Handle streaming response
        $body = $response->getBody();
        while (!$body->eof()) {
            $chunk = $body->read(1024);
            if ($chunk) {
                yield $chunk;
            }
        }
    }

    public function getMaxTokens(): int
    {
        return match ($this->currentModel) {
            'gpt-4-turbo' => 128000,
            'gpt-4' => 8192,
            'gpt-3.5-turbo' => 4096,
            default => 4096,
        };
    }

    public function generateImage(array $params): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::API_BASE_URL . '/images/generations', [
            'model' => $this->currentModel,
            'prompt' => $params['prompt'],
            'n' => 1,
            'size' => $params['size'] ?? '1024x1024',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json()['data'][0]['url'] ?? '';
    }

    public function generateImages(array $params): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::API_BASE_URL . '/images/generations', [
            'model' => $this->currentModel,
            'prompt' => $params['prompt'],
            'n' => $params['n'] ?? 1,
            'size' => $params['size'] ?? '1024x1024',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        return array_map(fn($item) => $item['url'], $response->json()['data'] ?? []);
    }

    public function getSupportedFormats(): array
    {
        return ['png', 'jpg'];
    }

    public function getMaxResolution(): array
    {
        return match ($this->currentModel) {
            'dall-e-3' => ['width' => 1792, 'height' => 1024],
            'dall-e-2' => ['width' => 1024, 'height' => 1024],
            default => ['width' => 1024, 'height' => 1024],
        };
    }

    public function generateVideo(array $params): string
    {
        throw new \RuntimeException('Video generation not yet supported by OpenAI');
    }

    public function getVideoStatus(string $videoId): array
    {
        throw new \RuntimeException('Video generation not yet supported by OpenAI');
    }

    public function getMaxDuration(): int
    {
        return 0; // Not supported
    }

    protected function getDefaultModel(): string
    {
        return 'gpt-4';
    }
}
