<?php

declare(strict_types=1);

namespace App\AI\Providers\OpenAI;

use App\AI\Providers\AbstractProvider;
use App\AI\Contracts\Capabilities\TextGenerationInterface;
use App\AI\Contracts\Capabilities\ImageGenerationInterface;
use App\AI\Contracts\Capabilities\VideoGenerationInterface;
use Illuminate\Support\Facades\Http;

class OpenAIProvider extends AbstractProvider implements 
    TextGenerationInterface, 
    ImageGenerationInterface,
    VideoGenerationInterface
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
        return in_array($capability, ['text', 'image', 'video']);
    }
    
    public function getCapabilities(): array
    {
        return ['text', 'image', 'video'];
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
        
        if (!$response->successful()) {
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
        
        foreach ($response->getBody() as $chunk) {
            yield $chunk;
        }
    }
    
    public function getMaxTokens(): int
    {
        return match ($this->currentModel) {
            'gpt-4' => 8192,
            'gpt-4-turbo' => 128000,
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
            'model' => $params['model'] ?? 'dall-e-3',
            'prompt' => $params['prompt'],
            'n' => 1,
            'size' => $params['size'] ?? '1024x1024',
            'quality' => $params['quality'] ?? 'standard',
        ]);
        
        if (!$response->successful()) {
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
            'model' => $params['model'] ?? 'dall-e-2',
            'prompt' => $params['prompt'],
            'n' => $params['count'] ?? 1,
            'size' => $params['size'] ?? '1024x1024',
        ]);
        
        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }
        
        return array_column($response->json()['data'] ?? [], 'url');
    }
    
    public function getSupportedFormats(): array
    {
        return ['png', 'jpg'];
    }
    
    public function getMaxResolution(): array
    {
        return ['width' => 1024, 'height' => 1024];
    }
    
    public function generateVideo(array $params): string
    {
        // OpenAI doesn't have direct video generation yet
        // This is a placeholder for future implementation
        throw new \RuntimeException('Video generation not yet implemented for OpenAI');
    }
    
    public function getVideoStatus(string $jobId): array
    {
        throw new \RuntimeException('Video generation not yet implemented for OpenAI');
    }
    
    public function getMaxDuration(): int
    {
        return 0;
    }
    
    protected function getDefaultModel(): string
    {
        return 'gpt-4';
    }
}