<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Agent Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures the AI Agent package with support for multiple
    | AI providers and their respective models.
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'claude'),

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Configure all AI providers and their models. Each provider can have
    | multiple models with different capabilities and configurations.
    |
    */

    'providers' => [
        'claude' => [
            'api_key' => env('CLAUDE_API_KEY'),
            'class' => \WebsiteLearners\AIAgent\Providers\AI\Claude\ClaudeProvider::class,
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
            'default_model' => env('CLAUDE_MODEL', 'claude-3-sonnet-20241022'),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'class' => \WebsiteLearners\AIAgent\Providers\AI\OpenAI\OpenAIProvider::class,
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
            'default_model' => env('OPENAI_MODEL', 'gpt-4'),
        ],

        'ideogram' => [
            'api_key' => env('IDEOGRAM_API_KEY'),
            'class' => \WebsiteLearners\AIAgent\Providers\AI\Ideogram\IdeogramProvider::class,
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
            'default_model' => env('IDEOGRAM_MODEL', 'ideogram-v2'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Providers by Capability
    |--------------------------------------------------------------------------
    |
    | Configure default providers for each capability type.
    |
    */

    'default_providers' => [
        'text' => env('AI_TEXT_PROVIDER', 'claude'),
        'image' => env('AI_IMAGE_PROVIDER', 'ideogram'),
        'video' => env('AI_VIDEO_PROVIDER', 'openai'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Providers
    |--------------------------------------------------------------------------
    |
    | Configure fallback providers for each capability in case the primary fails.
    |
    */

    'fallback_providers' => [
        'text' => ['claude', 'openai'],
        'image' => ['ideogram', 'openai'],
        'video' => ['openai'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module-Specific Configurations
    |--------------------------------------------------------------------------
    |
    | Configure specific providers for different modules or use cases.
    |
    */

    'modules' => [
        'storyboard' => [
            'character_provider' => env('STORYBOARD_CHARACTER_PROVIDER'),
            'shot_provider' => env('STORYBOARD_SHOT_PROVIDER'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the AI Agent package.
    |
    */

    'features' => [
        'rate_limiting' => [
            'enabled' => env('AI_RATE_LIMITING_ENABLED', true),
            'requests_per_minute' => env('AI_RATE_LIMIT_PER_MINUTE', 60),
        ],
        'cache' => [
            'enabled' => env('AI_CACHE_ENABLED', true),
            'ttl' => env('AI_CACHE_TTL', 3600), // 1 hour
            'store' => env('AI_CACHE_STORE', 'redis'),
        ],
        'logging' => [
            'enabled' => env('AI_LOGGING_ENABLED', true),
            'channel' => env('AI_LOG_CHANNEL', 'ai'),
            'log_requests' => env('AI_LOG_REQUESTS', true),
            'log_responses' => env('AI_LOG_RESPONSES', false),
            'log_errors' => env('AI_LOG_ERRORS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Passport Integration
    |--------------------------------------------------------------------------
    |
    | Configure Laravel Passport integration for API authentication.
    |
    */

    'passport' => [
        'enabled' => env('AI_PASSPORT_ENABLED', false),
        'redirect_url' => env('AI_PASSPORT_REDIRECT_URL', '/home'),
        'client_id' => env('AI_PASSPORT_CLIENT_ID'),
        'client_secret' => env('AI_PASSPORT_CLIENT_SECRET'),
    ],
];
