<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can configure all AI providers and their models. Each provider
    | can have multiple models with different capabilities and configurations.
    |
    */
    
    'providers' => [
        'claude' => [
            'models' => [
                'claude-3-sonnet-20241022' => [
                    'name' => 'Claude 3 Sonnet',
                    'version' => '3.5',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                ],
                'claude-3-sonnet-20241128' => [
                    'name' => 'Claude 3 Sonnet',
                    'version' => '3.7',
                    'max_tokens' => 8192,
                    'capabilities' => ['text'],
                ],
                'claude-3-opus-20240229' => [
                    'name' => 'Claude 3 Opus',
                    'version' => '3.0',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                ],
                'claude-3-haiku-20240307' => [
                    'name' => 'Claude 3 Haiku',
                    'version' => '3.0',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                ],
            ],
        ],
        
        'openai' => [
            'models' => [
                'gpt-4' => [
                    'name' => 'GPT-4',
                    'version' => '4.0',
                    'max_tokens' => 8192,
                    'capabilities' => ['text'],
                ],
                'gpt-4-turbo' => [
                    'name' => 'GPT-4 Turbo',
                    'version' => '4.0-turbo',
                    'max_tokens' => 128000,
                    'capabilities' => ['text'],
                ],
                'gpt-3.5-turbo' => [
                    'name' => 'GPT-3.5 Turbo',
                    'version' => '3.5-turbo',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                ],
                'dall-e-3' => [
                    'name' => 'DALL-E 3',
                    'version' => '3.0',
                    'capabilities' => ['image'],
                ],
                'dall-e-2' => [
                    'name' => 'DALL-E 2',
                    'version' => '2.0',
                    'capabilities' => ['image'],
                ],
            ],
        ],
        
        'ideogram' => [
            'models' => [
                'ideogram-v2' => [
                    'name' => 'Ideogram V2',
                    'version' => '2.0',
                    'capabilities' => ['image'],
                ],
                'ideogram-v1' => [
                    'name' => 'Ideogram V1',
                    'version' => '1.0',
                    'capabilities' => ['image'],
                ],
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Module-Specific Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Configure specific providers for different modules or use cases.
    |
    */
    
    'storyboard' => [
        'character_provider' => env('STORYBOARD_CHARACTER_PROVIDER'),
        'shot_provider' => env('STORYBOARD_SHOT_PROVIDER'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting and Caching
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting and caching for AI provider requests.
    |
    */
    
    'rate_limiting' => [
        'enabled' => env('AI_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('AI_RATE_LIMIT_PER_MINUTE', 60),
    ],
    
    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', true),
        'ttl' => env('AI_CACHE_TTL', 3600), // 1 hour
        'store' => env('AI_CACHE_STORE', 'redis'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for AI provider requests and responses.
    |
    */
    
    'logging' => [
        'enabled' => env('AI_LOGGING_ENABLED', true),
        'channel' => env('AI_LOG_CHANNEL', 'ai'),
        'log_requests' => env('AI_LOG_REQUESTS', true),
        'log_responses' => env('AI_LOG_RESPONSES', false),
        'log_errors' => env('AI_LOG_ERRORS', true),
    ],
];