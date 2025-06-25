<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Providers\AI\Ideogram;

use Illuminate\Support\Facades\Http;
use WebsiteLearners\AIAgent\Contracts\Capabilities\ImageGenerationInterface;
use WebsiteLearners\AIAgent\Providers\AI\AbstractProvider;

class IdeogramProvider extends AbstractProvider implements ImageGenerationInterface
{
    protected array $supportedModels = [
        'ideogram-v1',
        'ideogram-v2',
    ];

    private const API_URL = 'https://api.ideogram.ai/v1/generate';

    public function getName(): string
    {
        return 'Ideogram';
    }

    public function getVersion(): string
    {
        return '1.0';
    }

    public function supports(string $capability): bool
    {
        return $capability === 'image';
    }

    public function getCapabilities(): array
    {
        return ['image'];
    }

    public function generateImage(array $params): string
    {
        $response = Http::withHeaders([
            'Api-Key' => $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::API_URL, [
            'image_request' => [
                'prompt' => $params['prompt'],
                'aspect_ratio' => $params['aspect_ratio'] ?? 'ASPECT_1_1',
                'model' => $this->currentModel,
                'magic_prompt_option' => $params['magic_prompt'] ?? 'AUTO',
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Ideogram API error: '.$response->body());
        }

        $data = $response->json();

        return $data['data'][0]['url'] ?? '';
    }

    public function generateImages(array $params): array
    {
        $response = Http::withHeaders([
            'Api-Key' => $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::API_URL, [
            'image_request' => [
                'prompt' => $params['prompt'],
                'aspect_ratio' => $params['aspect_ratio'] ?? 'ASPECT_1_1',
                'model' => $this->currentModel,
                'num_images' => $params['count'] ?? 1,
                'magic_prompt_option' => $params['magic_prompt'] ?? 'AUTO',
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Ideogram API error: '.$response->body());
        }

        $data = $response->json();

        return array_column($data['data'] ?? [], 'url');
    }

    public function getSupportedFormats(): array
    {
        return ['png', 'jpg', 'webp'];
    }

    public function getMaxResolution(): array
    {
        return ['width' => 1024, 'height' => 1024];
    }

    protected function getDefaultModel(): string
    {
        return 'ideogram-v2';
    }
}
