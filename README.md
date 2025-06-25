# AI Architecture Package
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel/ai-architecture.svg?style=flat-square)](https://packagist.org/packages/laravel/ai-architecture)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/laravel/ai-architecture/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laravel/ai-architecture/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/laravel/ai-architecture/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/laravel/ai-architecture/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel/ai-architecture.svg?style=flat-square)](https://packagist.org/packages/laravel/ai-architecture)

A flexible, modular AI service architecture for Laravel that supports multiple AI providers (Claude, OpenAI, Ideogram) with easy switching via configuration.

## Features

- **Multi-Provider Support**: Seamlessly switch between Claude, OpenAI, and Ideogram
- **Capability-Based Design**: Services are organized by capabilities (text, image, video)
- **Automatic Fallback**: Fallback to alternative providers when primary fails
- **Model Flexibility**: Easy switching between model versions
- **Module Independence**: Each module can use different providers
- **Configuration-Driven**: All settings managed through environment variables
- **SOLID Principles**: Clean architecture following best practices
## Installation

```bash
composer require laravel/ai-architecture
```

Register the service provider in `config/app.php`:
```php
'providers' => [
    App\Providers\AIServiceProvider::class,
],
```

Publish configuration:
```bash
php artisan vendor:publish --tag=ai-config
```

Configure environment variables:

```env
AI_TEXT_PROVIDER=claude
AI_IMAGE_PROVIDER=ideogram
AI_VIDEO_PROVIDER=openai

CLAUDE_API_KEY=your-claude-api-key
OPENAI_API_KEY=your-openai-api-key
IDEOGRAM_API_KEY=your-ideogram-api-key
```

## Usage

```php
use App\AI\Contracts\Services\TextServiceInterface;
use App\AI\Contracts\Services\ImageServiceInterface;

// Text generation
$textService = app(TextServiceInterface::class);
$response = $textService->generateText('Write a story about a robot');

// Image generation
$imageService = app(ImageServiceInterface::class);
$imageUrl = $imageService->generateImage('A futuristic city at sunset');

// Switch provider at runtime
$textService->setProvider('openai');
$response = $textService->generateText('Hello world');

// Module-specific usage
use App\AI\Services\Modules\Storyboard\CharacterService;

$characterService = app(CharacterService::class);

$description = $characterService->generateCharacterDescription([
    'name' => 'John Doe',
    'age' => '35',
    'occupation' => 'Detective',
]);

$characterSheet = $characterService->generateCharacterSheet([
    'attributes' => [
        'name' => 'Jane Smith',
        'appearance' => 'Tall, athletic build',
        'personality' => 'Confident and mysterious',
    ],
]);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Taylor Otwell](https://github.com/taylorotwell)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
