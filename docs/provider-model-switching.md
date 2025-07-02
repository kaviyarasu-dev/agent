# Provider and Model Switching in AI Agents

This guide explains how to switch between different AI providers (Claude, OpenAI, etc.) and models within your AI Agents.

## Table of Contents
- [Overview](#overview)
- [Method 1: Advanced Agent with Direct Access](#method-1-advanced-agent-with-direct-access)
- [Method 2: Using the HasDynamicProvider Trait](#method-2-using-the-hasdynamicprovider-trait)
- [Method 3: Direct Provider Factory](#method-3-direct-provider-factory)
- [Method 4: Configuration-Based](#method-4-configuration-based)
- [Best Practices](#best-practices)

## Overview

The AI Agent package supports multiple providers and models. You can switch between them in several ways:

1. **BlogAiAgentAdvanced** - Direct provider access with built-in switching
2. **HasDynamicProvider Trait** - Reusable trait for any agent
3. **Direct Provider Factory** - Maximum control
4. **Configuration** - Environment variables and config files

## Method 1: Advanced Agent with Direct Access

The `BlogAiAgentAdvanced` class provides built-in methods for switching providers and models.

### Basic Usage

```php
use App\Agents\Blog\BlogAiAgentAdvanced;

$agent = app(BlogAiAgentAdvanced::class);

// Switch provider
$agent->switchProvider('openai');

// Switch model
$agent->switchModel('gpt-4');

// Chain methods
$agent->switchProvider('claude')
      ->switchModel('claude-3-opus-20240229');

// Execute
$content = $agent->execute([
    'prompt' => 'Write about Laravel',
    'options' => ['tone' => 'professional']
]);
```

### Getting Information

```php
// Get current provider and model
$provider = $agent->getCurrentProvider(); // 'claude'
$model = $agent->getCurrentModel();       // 'claude-3-opus-20240229'

// Get available models for current provider
$models = $agent->getAvailableModels();
// Returns: ['claude-3-sonnet-20241022', 'claude-3-opus-20240229', ...]
```

### Temporary Configuration

Execute with a different provider/model without changing the default:

```php
$content = $agent->executeWith(
    ['prompt' => 'Write about AI', 'options' => []],
    'openai',     // Provider
    'gpt-4-turbo' // Model
);
// Original provider/model configuration is restored after execution
```

## Method 2: Using the HasDynamicProvider Trait

The trait can be added to any AI Agent class to provide switching capabilities.

### Creating an Agent with the Trait

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
        $this->useProvider('claude'); // Set default
    }
    
    public function generate(string $prompt): string
    {
        return $this->textService->generateText($prompt);
    }
}
```

### Using the Trait Methods

```php
$agent = app(MyCustomAgent::class);

// Switch provider and model
$agent->useProvider('openai')
      ->useModel('gpt-4');

// Get available providers for a capability
$textProviders = $agent->getAvailableProviders('text');
// Returns: [
//   'claude' => ['name' => 'Claude', 'version' => '3.0', ...],
//   'openai' => ['name' => 'OpenAI', 'version' => '1.0', ...]
// ]

// Get models for a specific provider
$models = $agent->getAvailableModels('claude');

// Execute with temporary configuration
$result = $agent->withConfiguration(
    fn() => $agent->generate('Hello world'),
    'openai',
    'gpt-3.5-turbo'
);
```

## Method 3: Direct Provider Factory

For maximum control, use the ProviderFactory directly:

```php
use Kaviyarasu\AIAgent\Factory\ProviderFactory;

$factory = app(ProviderFactory::class);

// Create a specific provider
$claude = $factory->create('claude');
$claude->switchModel('claude-3-sonnet-20241128');

// Use the provider directly
$result = $claude->generateText([
    'prompt' => 'Write a poem',
    'temperature' => 0.7,
    'max_tokens' => 500
]);

// Get all available providers for a capability
$textProviders = $factory->getAvailableProviders('text');
```

## Method 4: Configuration-Based

### Environment Variables

Set default models in your `.env` file:

```env
# Default providers
AI_DEFAULT_PROVIDER=claude
AI_TEXT_PROVIDER=claude
AI_IMAGE_PROVIDER=ideogram

# Model configuration
CLAUDE_MODEL=claude-3-sonnet-20241022
OPENAI_MODEL=gpt-4
```

### Config File

Update `config/ai-agent.php`:

```php
'providers' => [
    'claude' => [
        'default_model' => 'claude-3-opus-20240229',
        // ...
    ],
    'openai' => [
        'default_model' => 'gpt-4-turbo',
        // ...
    ],
],
```

### Runtime Configuration

```php
// Change default model at runtime
config(['ai-agent.providers.claude.default_model' => 'claude-3-opus-20240229']);

// Then create new agents with the updated configuration
$agent = app(BlogAiAgent::class);
```

## Available Providers and Models

### Claude (Anthropic)
- `claude-3-sonnet-20241022` - Balanced performance
- `claude-3-sonnet-20241128` - Latest Sonnet
- `claude-3-opus-20240229` - Most capable
- `claude-3-haiku-20240307` - Fastest

### OpenAI
- `gpt-4` - Most capable
- `gpt-4-turbo` - Faster GPT-4
- `gpt-3.5-turbo` - Fast and economical

### Ideogram (Image Generation)
- `ideogram-v2` - Latest version
- `ideogram-v1` - Previous version

## Best Practices

### 1. Model Selection Strategy

```php
class SmartAgent extends BlogAiAgentAdvanced
{
    public function selectOptimalModel(array $requirements): self
    {
        $complexity = $requirements['complexity'] ?? 'medium';
        $speed = $requirements['speed'] ?? 'normal';
        
        if ($complexity === 'high' && $speed !== 'fast') {
            $this->switchProvider('claude')->switchModel('claude-3-opus-20240229');
        } elseif ($speed === 'fast') {
            $this->switchProvider('claude')->switchModel('claude-3-haiku-20240307');
        } else {
            $this->switchProvider('claude')->switchModel('claude-3-sonnet-20241022');
        }
        
        return $this;
    }
}
```

### 2. Fallback Handling

```php
public function generateWithFallback(string $prompt): string
{
    $providers = [
        ['provider' => 'claude', 'model' => 'claude-3-sonnet-20241022'],
        ['provider' => 'openai', 'model' => 'gpt-4'],
        ['provider' => 'openai', 'model' => 'gpt-3.5-turbo'],
    ];
    
    foreach ($providers as $config) {
        try {
            $this->switchProvider($config['provider'])
                 ->switchModel($config['model']);
            
            return $this->execute(['prompt' => $prompt, 'options' => []]);
        } catch (\Exception $e) {
            logger()->warning("Failed with {$config['provider']}: " . $e->getMessage());
            continue;
        }
    }
    
    throw new \RuntimeException('All providers failed');
}
```

### 3. Cost Optimization

```php
public function optimizeForCost(array $data): string
{
    $length = $data['options']['length'] ?? 'medium';
    
    // Use cheaper models for shorter content
    if ($length === 'short') {
        $this->switchProvider('openai')->switchModel('gpt-3.5-turbo');
    } else {
        $this->switchProvider('claude')->switchModel('claude-3-sonnet-20241022');
    }
    
    return $this->execute($data);
}
```

### 4. Testing Multiple Providers

```php
public function compareProviders(string $prompt): array
{
    $providers = [
        'claude' => 'claude-3-sonnet-20241022',
        'openai' => 'gpt-4',
    ];
    
    $results = [];
    
    foreach ($providers as $provider => $model) {
        $results[$provider] = $this->executeWith(
            ['prompt' => $prompt, 'options' => []],
            $provider,
            $model
        );
    }
    
    return $results;
}
```

## Error Handling

Always handle potential errors when switching providers:

```php
try {
    $agent->switchProvider('openai')
          ->switchModel('gpt-4');
} catch (\InvalidArgumentException $e) {
    // Provider not configured
    logger()->error('Provider error: ' . $e->getMessage());
} catch (\RuntimeException $e) {
    // Model not supported
    logger()->error('Model error: ' . $e->getMessage());
}
```

## Performance Considerations

1. **Provider instances are cached** - Switching between providers is efficient
2. **Model switching is immediate** - No additional API calls needed
3. **Use `executeWith()` for one-off changes** - Avoids permanent configuration changes
4. **Consider provider latency** - Some providers/models are faster than others

## Summary

Choose the method that best fits your use case:

- **Simple switching**: Use `BlogAiAgentAdvanced`
- **Reusable pattern**: Use `HasDynamicProvider` trait
- **Maximum control**: Use ProviderFactory directly
- **Static configuration**: Use environment variables
