# Provider and Model Switching Guide

This guide explains how to use the comprehensive provider and model switching capabilities in the AI Agent Laravel package.

## Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Advanced Features](#advanced-features)
- [Creating Custom Agents](#creating-custom-agents)
- [Provider Configuration](#provider-configuration)
- [Best Practices](#best-practices)
- [Migration Guide](#migration-guide)

## Overview

The AI Agent package now supports dynamic provider and model switching across all agent classes. This allows you to:

- Switch between different AI providers (Claude, OpenAI, etc.) at runtime
- Change models within a provider based on task requirements
- Use different providers for different services (text, image, video)
- Implement fallback strategies for reliability
- Optimize costs by selecting appropriate models

## Basic Usage

### Using the Enhanced Trait

The simplest way to add provider/model switching to any class is using the `HasDynamicProvider` trait:

```php
use App\Agents\Traits\HasDynamicProvider;
use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;

class MyCustomAgent
{
    use HasDynamicProvider;
    
    protected TextServiceInterface $textService;
    
    public function __construct(TextServiceInterface $textService)
    {
        $this->textService = $textService;
    }
    
    public function generateContent($prompt)
    {
        // Switch to Claude with Opus model
        return $this->useProvider('claude')
            ->useModel('claude-3-opus-20240229')
            ->withConfiguration(function() use ($prompt) {
                return $this->textService->generateText($prompt);
            });
    }
}
```

### Extending BaseAIAgent

For more complex agents, extend the `BaseAIAgent` class:

```php
use Kaviyarasu\AIAgent\Agents\BaseAIAgent;

class BlogWriterAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text'];
    
    public function execute(array $data)
    {
        // The agent automatically has access to $this->textService
        return $this->textService->generateText($data['prompt']);
    }
}

// Usage
$agent = app(BlogWriterAgent::class);
$result = $agent->switchProvider('openai')
    ->switchModel('gpt-4-turbo-preview')
    ->execute(['prompt' => 'Write about AI']);
```

### Direct Service Usage

Services can be used directly with provider/model switching:

```php
use Kaviyarasu\AIAgent\Facades\AIAgent;

// Switch provider for text generation
$text = AIAgent::text()
    ->switchProvider('claude')
    ->switchModel('claude-3-sonnet-20241022')
    ->generateText('Explain quantum computing');

// Switch provider for image generation
$image = AIAgent::image()
    ->switchProvider('openai')
    ->switchModel('dall-e-3')
    ->generateImage('A futuristic city at sunset');
```

## Advanced Features

### Temporary Provider/Model Switching

Use temporary configurations without affecting the default settings:

```php
$agent = app(MyAgent::class);

// Temporarily use a different provider
$result = $agent->withProvider('openai', function($agent) {
    return $agent->execute(['task' => 'complex analysis']);
});

// Temporarily use a different model
$result = $agent->withModel('gpt-4-turbo-preview', function($agent) {
    return $agent->execute(['task' => 'detailed writing']);
});
```

### Fallback Providers

Implement automatic fallback when providers fail:

```php
$agent = app(ContentCreatorAgent::class);

// Try providers in order until one succeeds
$result = $agent->executeWithFallback(
    ['topic' => 'AI Ethics'],
    [
        ['provider' => 'claude', 'model' => 'claude-3-opus-20240229'],
        ['provider' => 'openai', 'model' => 'gpt-4'],
        ['provider' => 'openai', 'model' => 'gpt-3.5-turbo'], // Fallback
    ]
);
```

### Multi-Service Agents

Create agents that use different providers for different capabilities:

```php
class ContentCreatorAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text', 'image'];
    
    public function execute(array $data)
    {
        // Use Claude for text
        $this->textService->switchProvider('claude')
            ->switchModel('claude-3-opus-20240229');
        $text = $this->textService->generateText($data['prompt']);
        
        // Use DALL-E for images
        $this->imageService->switchProvider('openai')
            ->switchModel('dall-e-3');
        $image = $this->imageService->generateImage($data['image_prompt']);
        
        return compact('text', 'image');
    }
}
```

### Model Capabilities Detection

Query model capabilities before using them:

```php
$capabilities = $agent->getModelCapabilities('gpt-4-turbo-preview');
// Returns:
// [
//     'max_tokens' => 128000,
//     'supports_streaming' => true,
//     'supports_functions' => true,
// ]

if ($capabilities['supports_streaming']) {
    $stream = $agent->textService->streamText($prompt);
}
```

### Provider Information

Get detailed information about providers:

```php
// Get all available providers for text generation
$providers = $agent->getAvailableProviders('text');

// Get current provider info
$info = $agent->getProviderInfo();
// Returns:
// [
//     'name' => 'claude',
//     'version' => '2024.1',
//     'available' => true,
//     'capabilities' => ['text'],
//     'models' => ['claude-3-opus-20240229', ...],
//     'current_model' => 'claude-3-sonnet-20241022'
// ]
```

## Creating Custom Agents

### Simple Agent with Trait

```php
use App\Agents\Traits\HasDynamicProvider;

class TranslationAgent
{
    use HasDynamicProvider;
    
    protected TextServiceInterface $textService;
    
    public function translate($text, $from, $to)
    {
        // Use GPT-4 for accurate translations
        return $this->useProvider('openai')
            ->useModel('gpt-4')
            ->withConfiguration(function() use ($text, $from, $to) {
                $prompt = "Translate from {$from} to {$to}: {$text}";
                return $this->textService->generateText($prompt);
            });
    }
}
```

### Advanced Agent with BaseAIAgent

```php
use Kaviyarasu\AIAgent\Agents\BaseAIAgent;

class ResearchAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text'];
    
    public function execute(array $data)
    {
        $topic = $data['topic'];
        $depth = $data['depth'] ?? 'standard';
        
        // Choose model based on research depth
        $model = match($depth) {
            'shallow' => 'gpt-3.5-turbo',
            'deep' => 'claude-3-opus-20240229',
            default => 'claude-3-sonnet-20241022',
        };
        
        $provider = str_contains($model, 'claude') ? 'claude' : 'openai';
        
        return $this->switchProvider($provider)
            ->switchModel($model)
            ->generateResearch($topic);
    }
    
    private function generateResearch($topic)
    {
        // Implementation
    }
}
```

### Adaptive Agent Example

```php
class AdaptiveWritingAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text'];
    
    public function execute(array $data)
    {
        $length = strlen($data['context'] ?? '');
        
        // Adapt model based on context length
        if ($length > 10000) {
            // Use model with larger context window
            $this->switchProvider('claude')
                ->switchModel('claude-3-sonnet-20241128'); // 8K tokens
        } else {
            // Use faster model for shorter contexts
            $this->switchProvider('openai')
                ->switchModel('gpt-3.5-turbo');
        }
        
        return $this->textService->generateText($data['prompt']);
    }
}
```

## Provider Configuration

### Configuration File

Update your `config/ai-agent.php`:

```php
'providers' => [
    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'models' => [
            'claude-3-opus-20240229' => [
                'max_tokens' => 4096,
                'supports_streaming' => true,
            ],
            // ... other models
        ],
        'default_model' => 'claude-3-sonnet-20241022',
    ],
    // ... other providers
],

'model_selection' => [
    'strategies' => [
        'cost_optimized' => [
            'text' => ['claude-3-haiku-20240307', 'gpt-3.5-turbo'],
        ],
        'quality_optimized' => [
            'text' => ['claude-3-opus-20240229', 'gpt-4-turbo-preview'],
        ],
    ],
],
```

### Environment Variables

```env
# Default providers
AI_DEFAULT_PROVIDER=claude
AI_TEXT_PROVIDER=claude
AI_IMAGE_PROVIDER=openai

# Default models
CLAUDE_MODEL=claude-3-sonnet-20241022
OPENAI_MODEL=gpt-4

# Feature flags
AI_PROVIDER_SWITCHING_ENABLED=true
AI_MODEL_SWITCHING_ENABLED=true
AI_RUNTIME_SWITCHING=true
```

## Best Practices

### 1. Model Selection Strategy

Choose models based on task requirements:

```php
class SmartAgent extends BaseAIAgent
{
    private function selectModel(string $task): array
    {
        return match($task) {
            'code_generation' => ['provider' => 'openai', 'model' => 'gpt-4'],
            'creative_writing' => ['provider' => 'claude', 'model' => 'claude-3-opus-20240229'],
            'quick_response' => ['provider' => 'openai', 'model' => 'gpt-3.5-turbo'],
            'image_generation' => ['provider' => 'openai', 'model' => 'dall-e-3'],
            default => ['provider' => 'claude', 'model' => 'claude-3-sonnet-20241022'],
        };
    }
}
```

### 2. Error Handling

Always handle provider switching errors:

```php
try {
    $result = $agent->switchProvider('openai')
        ->switchModel('gpt-4')
        ->execute($data);
} catch (\InvalidArgumentException $e) {
    // Handle invalid provider/model
    logger()->error('Invalid provider configuration: ' . $e->getMessage());
    
    // Fall back to default
    $result = $agent->execute($data);
} catch (\Exception $e) {
    // Handle other errors
    logger()->error('Agent execution failed: ' . $e->getMessage());
    throw $e;
}
```

### 3. Cost Optimization

Implement cost-aware model selection:

```php
class CostAwareAgent extends BaseAIAgent
{
    public function execute(array $data)
    {
        $budget = $data['budget'] ?? 'standard';
        
        if ($budget === 'low') {
            $this->switchProvider('openai')->switchModel('gpt-3.5-turbo');
        } elseif ($budget === 'high') {
            $this->switchProvider('claude')->switchModel('claude-3-opus-20240229');
        }
        
        return parent::execute($data);
    }
}
```

### 4. Performance Monitoring

Track provider/model performance:

```php
class MonitoredAgent extends BaseAIAgent
{
    public function execute(array $data)
    {
        $start = microtime(true);
        $provider = $this->getCurrentProvider();
        $model = $this->getCurrentModel();
        
        try {
            $result = parent::execute($data);
            
            $duration = microtime(true) - $start;
            logger()->info('Agent execution completed', [
                'provider' => $provider,
                'model' => $model,
                'duration' => $duration,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            logger()->error('Agent execution failed', [
                'provider' => $provider,
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

## Migration Guide

### Migrating from Old Agents

If you have existing agents without provider switching:

1. **Option 1: Add the trait**
   ```php
   class YourExistingAgent
   {
       use HasDynamicProvider; // Add this
       
       // Your existing code
   }
   ```

2. **Option 2: Extend BaseAIAgent**
   ```php
   class YourExistingAgent extends BaseAIAgent
   {
       protected array $requiredServices = ['text']; // Define required services
       
       // Move your main logic to execute() method
       public function execute(array $data)
       {
           // Your existing logic
       }
   }
   ```

### Updating Service Calls

Replace direct service usage with provider-aware calls:

```php
// Old way
$text = $textService->generateText($prompt);

// New way with provider switching
$text = $textService->switchProvider('claude')
    ->switchModel('claude-3-sonnet-20241022')
    ->generateText($prompt);

// Or use temporary switching
$text = $textService->withProvider('openai', function($service) use ($prompt) {
    return $service->generateText($prompt);
});
```

### Backward Compatibility

The system maintains backward compatibility:

- Existing agents continue to work without modification
- Default providers are used when not explicitly specified
- Model switching is optional

## Troubleshooting

### Common Issues

1. **"Provider not found" error**
   - Check provider is configured in `config/ai-agent.php`
   - Verify API keys are set in `.env`

2. **"Model not supported" error**
   - Ensure model is listed in provider configuration
   - Check model name spelling

3. **Performance issues**
   - Use provider caching (enabled by default)
   - Consider using lighter models for simple tasks

### Debug Commands

```bash
# List available providers
php artisan ai:providers

# Test provider connection
php artisan ai:test-provider claude

# Clear provider cache
php artisan ai:clear-cache
```

## Conclusion

The provider and model switching system gives you complete control over AI service selection, enabling you to optimize for cost, quality, and performance based on your specific needs.
