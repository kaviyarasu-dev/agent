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
            'class' => \Kaviyarasu\AIAgent\Providers\AI\Claude\ClaudeProvider::class,
            'models' => [
                'claude-sonnet-4-20250514' => [
                    'name' => 'Claude 4 Sonnet (Latest)',
                    'version' => '4.0',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'claude-opus-4-20250514' => [
                    'name' => 'Claude 4 Opus (Latest)',
                    'version' => '4.0',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'claude-3-7-sonnet-20250219' => [
                    'name' => 'Claude 3.7 Sonnet',
                    'version' => '3.7',
                    'max_tokens' => 8192,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'claude-3-5-sonnet-20241022' => [
                    'name' => 'Claude 3.5 Sonnet',
                    'version' => '3.5',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'claude-3-5-haiku-20241022' => [
                    'name' => 'Claude 3.5 Haiku',
                    'version' => '3.7',
                    'max_tokens' => 8192,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'claude-3-sonnet-20240229' => [
                    'name' => 'Claude 3 Sonnet',
                    'version' => '3.0',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'claude-3-opus-20240229' => [
                    'name' => 'Claude 3 Opus',
                    'version' => '3.0',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
                'claude-3-haiku-20240307' => [
                    'name' => 'Claude 3 Haiku (Previous)',
                    'version' => '3.0',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => false,
                ],
            ],
            'default_model' => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20241022'),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'class' => \Kaviyarasu\AIAgent\Providers\AI\OpenAI\OpenAIProvider::class,
            'models' => [
                'o4-mini-2025-04-16' => [
                    'name' => 'o4-Mini',
                    'version' => '4.0-turbo',
                    'max_tokens' => 4096,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => true,
                ],
                'o3-2025-04-16' => [
                    'name' => 'o3',
                    'version' => '4.0-full',
                    'max_tokens' => 128000,
                    'capabilities' => ['text', 'vision'],
                    'supports_streaming' => true,
                    'supports_functions' => true,
                ],
                'gpt-4.1-2025-04-14' => [
                    'name' => 'GPT-4.1',
                    'version' => '4.1',
                    'max_tokens' => 128000,
                    'capabilities' => ['text'],
                    'supports_streaming' => true,
                    'supports_functions' => true,
                ],
                'dall-e-3' => [
                    'name' => 'DALL-E 3',
                    'version' => '3.0',
                    'capabilities' => ['image'],
                    'sizes' => ['1024x1024', '1792x1024', '1024x1792'],
                    'quality' => ['standard', 'hd'],
                ],
                'dall-e-2' => [
                    'name' => 'DALL-E 2',
                    'version' => '2.0',
                    'capabilities' => ['image'],
                    'sizes' => ['256x256', '512x512', '1024x1024'],
                ],
            ],
            'default_model' => env('OPENAI_MODEL', 'o4-mini-2025-04-16'),
        ],

        'ideogram' => [
            'api_key' => env('IDEOGRAM_API_KEY'),
            'class' => \Kaviyarasu\AIAgent\Providers\AI\Ideogram\IdeogramProvider::class,
            'models' => [
                'ideogram-v2' => [
                    'name' => 'Ideogram V2',
                    'version' => '2.0',
                    'capabilities' => ['image'],
                    'styles' => ['realistic', 'design', 'anime', '3d'],
                ],
                'ideogram-v1' => [
                    'name' => 'Ideogram V1',
                    'version' => '1.0',
                    'capabilities' => ['image'],
                    'styles' => ['realistic', 'design'],
                ],
            ],
            'default_model' => env('IDEOGRAM_MODEL', 'ideogram-v2'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Selection Strategies
    |--------------------------------------------------------------------------
    |
    | Configure how models are selected for different use cases.
    |
    */

    'model_selection' => [
        'strategies' => [
            'cost_optimized' => [
                'text' => ['claude-3-haiku-20240307', 'gpt-4.1-2025-04-14'],
                'image' => ['dall-e-2', 'ideogram-v1'],
            ],
            'quality_optimized' => [
                'text' => ['claude-3-opus-20240229', 'o3'],
                'image' => ['dall-e-3', 'ideogram-v2'],
            ],
            'balanced' => [
                'text' => ['claude-3-5-sonnet-20241022', 'o4-mini-2025-04-16'],
                'image' => ['dall-e-3', 'ideogram-v2'],
            ],
        ],
        'default_strategy' => env('AI_MODEL_STRATEGY', 'balanced'),
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
        'provider_switching' => [
            'enabled' => env('AI_PROVIDER_SWITCHING_ENABLED', true),
            'allow_runtime_switching' => env('AI_RUNTIME_SWITCHING', true),
            'log_switches' => env('AI_LOG_PROVIDER_SWITCHES', true),
        ],
        'model_switching' => [
            'enabled' => env('AI_MODEL_SWITCHING_ENABLED', true),
            'validate_models' => env('AI_VALIDATE_MODELS', true),
            'auto_fallback' => env('AI_MODEL_AUTO_FALLBACK', true),
        ],
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
];
