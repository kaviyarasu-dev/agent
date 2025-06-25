<?php

declare(strict_types=1);

namespace App\AI\Config;

class AIConfigManager
{
    private array $config;
    
    public function __construct()
    {
        $this->config = $this->loadConfiguration();
    }
    
    private function loadConfiguration(): array
    {
        return [
            'default_providers' => [
                'text' => env('AI_TEXT_PROVIDER', 'claude'),
                'image' => env('AI_IMAGE_PROVIDER', 'ideogram'),
                'video' => env('AI_VIDEO_PROVIDER', 'openai'),
            ],
            'providers' => [
                'claude' => [
                    'class' => \App\AI\Providers\Claude\ClaudeProvider::class,
                    'model' => env('CLAUDE_MODEL', 'claude-3-sonnet-20241022'),
                    'api_key' => env('CLAUDE_API_KEY'),
                    'capabilities' => ['text'],
                ],
                'openai' => [
                    'class' => \App\AI\Providers\OpenAI\OpenAIProvider::class,
                    'model' => env('OPENAI_MODEL', 'gpt-4'),
                    'api_key' => env('OPENAI_API_KEY'),
                    'capabilities' => ['text', 'image', 'video'],
                ],
                'ideogram' => [
                    'class' => \App\AI\Providers\Ideogram\IdeogramProvider::class,
                    'model' => env('IDEOGRAM_MODEL', 'ideogram-v2'),
                    'api_key' => env('IDEOGRAM_API_KEY'),
                    'capabilities' => ['image'],
                ],
            ],
            'fallback_providers' => [
                'text' => ['claude', 'openai'],
                'image' => ['ideogram', 'openai'],
                'video' => ['openai'],
            ],
        ];
    }
    
    public function getProviderConfig(string $provider): array
    {
        return $this->config['providers'][$provider] ?? [];
    }
    
    public function getDefaultProvider(string $capability): string
    {
        return $this->config['default_providers'][$capability] ?? '';
    }
    
    public function getFallbackProviders(string $capability): array
    {
        return $this->config['fallback_providers'][$capability] ?? [];
    }
    
    public function getAllProviders(): array
    {
        return $this->config['providers'];
    }
    
    public function getProvidersForCapability(string $capability): array
    {
        $providers = [];
        
        foreach ($this->config['providers'] as $name => $config) {
            if (in_array($capability, $config['capabilities'])) {
                $providers[$name] = $config;
            }
        }
        
        return $providers;
    }
}