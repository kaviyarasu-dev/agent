<?php

declare(strict_types=1);

namespace App\AI\Providers\Claude;

use App\AI\Providers\AbstractProvider;
use App\AI\Contracts\Capabilities\TextGenerationInterface;
use Illuminate\Support\Facades\Http;

class ClaudeProvider extends AbstractProvider implements TextGenerationInterface
{
    protected array $supportedModels = [
        'claude-3-sonnet-20241022',
        'claude-3-sonnet-20241128',
        'claude-3-opus-20240229',
        'claude-3-haiku-20240307',
    ];
    
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    
    public function getName(): string
    {
        return 'Claude';
    }
    
    public function getVersion(): string
    {
        return '3.0';
    }
    
    public function supports(string $capability): bool
    {
        return in_array($capability, ['text']);
    }
    
    public function getCapabilities(): array
    {
        return ['text'];
    }
    
    public function generateText(array $params): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post(self::API_URL, [
            'model' => $this->currentModel,
            'max_tokens' => $params['max_tokens'] ?? 1000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $params['prompt'],
                ],
            ],
            'temperature' => $params['temperature'] ?? 0.7,
        ]);
        
        if (!$response->successful()) {
            throw new \RuntimeException('Claude API error: ' . $response->body());
        }
        
        return $response->json()['content'][0]['text'] ?? '';
    }
    
    public function streamText(array $params): iterable
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->withOptions([
            'stream' => true,
        ])->post(self::API_URL, [
            'model' => $this->currentModel,
            'max_tokens' => $params['max_tokens'] ?? 1000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $params['prompt'],
                ],
            ],
            'temperature' => $params['temperature'] ?? 0.7,
            'stream' => true,
        ]);
        
        foreach ($response->getBody() as $chunk) {
            yield $chunk;
        }
    }
    
    public function getMaxTokens(): int
    {
        return match ($this->currentModel) {
            'claude-3-sonnet-20241128' => 8192,
            'claude-3-opus-20240229' => 4096,
            default => 4096,
        };
    }
    
    protected function getDefaultModel(): string
    {
        return 'claude-3-sonnet-20241022';
    }
}