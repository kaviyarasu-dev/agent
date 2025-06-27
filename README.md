# AI Agent for Laravel
[![Latest Version on Packagist](https://img.shields.io/packagist/v/websitelearners/ai-agent.svg?style=flat-square)](https://packagist.org/packages/websitelearners/ai-agent)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/websitelearners/ai-agent/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/websitelearners/ai-agent/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/websitelearners/ai-agent/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/websitelearners/ai-agent/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/websitelearners/ai-agent.svg?style=flat-square)](https://packagist.org/packages/websitelearners/ai-agent)

A flexible, modular AI service architecture for Laravel that supports multiple AI providers (Claude, OpenAI, Ideogram) with easy switching via configuration.

## Features

- 🤖 **Multi-Provider Support**: Claude, OpenAI, and Ideogram
- 🔄 **Easy Provider Switching**: Switch providers at runtime or via configuration
- 📝 **Text Generation**: Support for multiple text models
- 🎨 **Image Generation**: Create images with DALL-E or Ideogram
- 🎬 **Video Generation**: Future-ready video generation support
- 🏗️ **SOLID Architecture**: Clean, maintainable code following SOLID principles
- 🔧 **Modular Design**: Easy to extend with new providers or capabilities
- 💾 **Caching Support**: Built-in caching for API responses
- 📊 **Rate Limiting**: Configurable rate limiting per provider
- 📝 **Comprehensive Logging**: Track all API interactions
- 🛠️ **Artisan Commands**: Scaffolding commands for quick development

## Installation

```bash
composer require websitelearners/ai-agent
```

```bash
php artisan vendor:publish --tag="ai-agent-migrations"
php artisan migrate
```

```bash
php artisan vendor:publish --tag="ai-agent-config"
```

This is the contents of the published config file:

```php
return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'claude'),

    'providers' => [
        'claude' => [
            'api_key' => env('CLAUDE_API_KEY'),
            'models' => [...],
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'models' => [...],
        ],
        'ideogram' => [
            'api_key' => env('IDEOGRAM_API_KEY'),
            'models' => [...],
        ],
    ],

    // ... more configuration options
];
```

## Usage

### Basic Usage

```php
use WebsiteLearners\AIAgent\Facades\AIAgent;

// Text generation
$response = AIAgent::text()->generateText('Write a story about a robot');

// Image generation
$imageUrl = AIAgent::image()->generateImage('A futuristic city at sunset');

// Switch provider at runtime
$response = AIAgent::provider('openai')->text()->generateText('Hello world');
```

### Advanced Usage

#### Working with Specific Models

```php
use WebsiteLearners\AIAgent\Facades\AIAgent;

// Use a specific Claude model
$response = AIAgent::provider('claude')
    ->text()
    ->setModel('claude-3-opus-20240229')
    ->generateText('Explain quantum computing');

// Use DALL-E 3 for image generation
$imageUrl = AIAgent::provider('openai')
    ->image()
    ->setModel('dall-e-3')
    ->generateImage('A serene landscape');
```

#### Module-Specific Services

```php
// Storyboard module with specific providers
$characterService = app(\WebsiteLearners\AIAgent\Services\Modules\Storyboard\CharacterService::class);
$character = $characterService->generateCharacter('A brave knight');

$shotService = app(\WebsiteLearners\AIAgent\Services\Modules\Storyboard\ShotService::class);
$shot = $shotService->generateShot('The knight standing on a hill');
```

#### Direct Service Access

```php
use WebsiteLearners\AIAgent\Services\Core\TextService;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;

// Create services directly
$providerFactory = app(ProviderFactory::class);
$textService = new TextService($providerFactory);
$response = $textService->generateText('Hello world');
```

### Creating AI Agents

The package includes a powerful scaffolding command to create AI agent classes:
```bash
# Create a basic text agent
php artisan ai-agent Blog/BlogAiAgent

# Create an image processing agent
php artisan ai-agent ImageProcessor --capability=image --provider=ideogram

# Create a video agent with logging and fallback
php artisan ai-agent VideoCreator --capability=video --with-logging --with-fallback

# Interactive mode
php artisan ai-agent
```

See the [AI Agent Command Documentation](docs/ai-agent-command.md) for detailed usage and examples.

## Configuration

### Environment Variables

```env
# Default provider
AI_DEFAULT_PROVIDER=claude

# Claude configuration
CLAUDE_API_KEY=your-claude-api-key
CLAUDE_MODEL=claude-3-sonnet-20241022

# OpenAI configuration
OPENAI_API_KEY=your-openai-api-key
OPENAI_MODEL=gpt-4

# Ideogram configuration
IDEOGRAM_API_KEY=your-ideogram-api-key
IDEOGRAM_MODEL=ideogram-v2

# Feature flags
AI_RATE_LIMITING_ENABLED=true
AI_CACHE_ENABLED=true
AI_LOGGING_ENABLED=true

# Module-specific providers
STORYBOARD_CHARACTER_PROVIDER=claude
STORYBOARD_SHOT_PROVIDER=ideogram
```

### Provider Configuration

```php
'providers' => [
    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'models' => [
            'claude-3-sonnet-20241022' => [
                'name' => 'Claude 3 Sonnet',
                'max_tokens' => 4096,
                'capabilities' => ['text'],
            ],
            // ... more models
        ],
    ],
],
```

## Testing

```bash
composer test
```

```bash
# Run unit tests only
composer test -- --filter=Unit

# Run architecture tests
composer test -- --filter=Arch

# Generate coverage report
composer test-coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [WebsiteLearners](https://github.com/websitelearners)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
