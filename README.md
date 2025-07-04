# AI Agent - Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kaviyarasu/ai-agent.svg?style=flat-square)](https://packagist.org/packages/kaviyarasu/ai-agent)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/kaviyarasu-dev/agent/run-tests?label=tests)](https://github.com/kaviyarasu-dev/agent/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/kaviyarasu-dev/agent/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/kaviyarasu-dev/agent/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/kaviyarasu/ai-agent.svg?style=flat-square)](https://packagist.org/packages/kaviyarasu/ai-agent)

A flexible, modular AI service architecture for Laravel that supports multiple AI providers with easy switching via configuration. Built with SOLID principles and designed for enterprise-grade applications.

## Core Features

- **🤖 Multi-Provider Support**: Claude, OpenAI, Ideogram, and more
- **🔄 Runtime Provider Switching**: Switch providers dynamically based on requirements
- **📝 Text Generation**: Support for multiple text models with customizable parameters
- **🎨 Image Generation**: Create images with DALL-E, Ideogram, or other providers
- **🎬 Video Generation**: Future-ready video generation support
- **🏗️ SOLID Architecture**: Clean, maintainable code following SOLID principles
- **🔧 Modular Design**: Easy to extend with new providers or capabilities
- **🛠️ Artisan Commands**: Scaffolding commands for rapid development

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.0 or Laravel 11.0
- Composer

## Installation

```bash
composer require kaviyarasu/ai-agent
```

### 2. Publish and Run Migrations

```bash
# Quick install (publishes config and runs migrations)
php artisan ai-agent:install

# Or manually
php artisan vendor:publish --tag="ai-agent-migrations"
php artisan migrate
php artisan vendor:publish --tag="ai-agent-config"
```

## Configuration
### 1. Set Up Environment Variables

Add your API keys to your `.env` file:

```env
CLAUDE_API_KEY=your-claude-api-key
OPENAI_API_KEY=your-openai-api-key
IDEOGRAM_API_KEY=your-ideogram-api-key
```

## Artisan Commands

### Basic Agent Creation

```bash
# Create a agent with text or image or video capability
php artisan ai-agent
```

## 📚 Detailed Usage Guide

### Provider Management

#### Available Providers

| Provider | Text | Image | Video | Status |
|----------|------|-------|-------|---------|
| Claude   | ✅   | ❌    | ❌    | Stable |
| OpenAI   | ✅   | ✅    | ❌    | Stable |
| Ideogram | ❌   | ✅    | ❌    | Stable |

### Basic Usage

```php
use Kaviyarasu\AIAgent\Facades\AIAgent;

// Text generation
$response = AIAgent::text()->generateText('Write a story about a robot');

// Stream text generation
foreach (AIAgent::text()->streamText('Explain the theory of relativity') as $chunk) {
    echo $chunk;
}

// Image generation
$imageUrl = AIAgent::image()->generateImage('A futuristic city at sunset');

// Switch provider at runtime
$response = AIAgent::provider('openai')->text()->generateText('Hello world');
```

### Advanced Usage

```php
use Kaviyarasu\AIAgent\Facades\AIAgent;

// Use specific model with custom parameters
$response = AIAgent::provider('claude')
    ->text()
    ->switchModel('claude-3-opus-20240229')
    ->generateText('Explain quantum computing', [
        'max_tokens' => 1000,
        'temperature' => 0.7,
        'top_p' => 0.9
    ]);

// Generate image with specific dimensions
$imageUrl = AIAgent::provider('openai')
    ->image()
    ->switchModel('dall-e-3')
    ->generateImage('A serene landscape with mountains', [
        'size' => '1024x1024',
        'quality' => 'hd',
        'style' => 'vivid'
    ]);

// Generate multiple images
$imageUrls = AIAgent::provider('openai')
    ->image()
    ->generateMultipleImages('A serene landscape with mountains', 3);
```

## Quick Start

```php
use Kaviyarasu\Agent\Facades\Agent;

// Generate text
$response = Agent::generateText('Write a haiku about Laravel');

// Create an image
$image = Agent::provider('openai')->createImage('A coding workspace');

// Switch providers dynamically
$result = Agent::provider('claude')->generateText('Explain quantum computing');
```

## 📚 Documentation

- [Custom Agent Documentation](docs/CUSTOM_AGENT.md)
- [Service Agent Documentation](docs/SERVICE_AGENT.md)

## 🆘 Support

- **Issues**: [GitHub Issues](https://github.com/kaviyarasu-dev/agent/issues)
- **Discussions**: [GitHub Discussions](https://github.com/kaviyarasu-dev/agent/discussions)

## 📄 License

This package is licensed under the MIT License. See the [LICENSE](LICENSE.md) file for details.

---

<p align="center">
    <strong>Built with ❤️ for the Laravel community</strong>
</p>
