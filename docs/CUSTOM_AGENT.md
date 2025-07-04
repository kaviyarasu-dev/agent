# 🤖 Custom AI Agents Documentation

> Build powerful AI agents with provider switching, model selection, and automatic fallbacks

## 📋 Table of Contents

- [Quick Start](#-quick-start)
- [Creating Custom Agents](#-creating-custom-agents)
- [Execute Methods](#-execute-methods)
- [Advanced Features](#-advanced-features)
- [Real-World Examples](#-real-world-examples)

---

## 🚀 Quick Start

Create your first AI agent in seconds:

```bash
php artisan make:ai-agent BlogAgent
```

This creates a fully functional AI agent ready to use!

---

## 🛠️ Creating Custom Agents

### Interactive Agent Creation

The `make:ai-agent` command provides an interactive experience:

```bash
php artisan make:ai-agent MyCustomAgent
```

You'll be prompted with:

```
Choose capability:
  [0] text
  [1] image
  [2] video
> 0

Choose default provider (optional):
  [0] claude
  [1] openai
  [2] ideogram
> 0

Choose default model (optional):
  [0] claude-4-sonnet-20250514
  [1] claude-4-opus-20250514
  [2] claude-3-7-sonnet-20250219
> 1

Include logging functionality? (yes/no) [no]: yes
Include fallback provider functionality? (yes/no) [no]: yes
```

### Command Options

Create agents with specific configurations:

```bash
# Text generation agent with Claude
php artisan make:ai-agent ContentAgent --capability=text --provider=claude --model=claude-4-opus-20250514

# Image generation agent with DALL-E
php artisan make:ai-agent ImageAgent --capability=image --provider=openai --model=dall-e-3

# Agent with advanced features
php artisan make:ai-agent SmartAgent --with-logging --with-fallback
```

### Nested Agents

Organize agents in subdirectories:

```bash
# Creates App\AI\Agents\Blog\ContentAgent
php artisan make:ai-agent Blog/ContentAgent

# Creates App\AI\Agents\Marketing\CampaignAgent
php artisan make:ai-agent Marketing/CampaignAgent
```

---

## 🎯 Execute Methods

### Basic Execution

```php
use App\AI\Agents\BlogAgent;

$agent = app(BlogAgent::class);
$result = $agent->execute([
    'topic' => 'AI in Healthcare',
    'tone' => 'professional'
]);
```

### executeWith() - Dynamic Provider/Model Selection

Execute with a specific provider or model without permanently changing the agent:

```php
// Execute with specific provider
$result = $agent->executeWith(
    data: ['prompt' => 'Generate content'],
    provider: 'openai'
);

// Execute with specific model
$result = $agent->executeWith(
    data: ['prompt' => 'Generate content'],
    model: 'gpt-4.1-2025-04-14'
);

// Execute with both provider and model
$result = $agent->executeWith(
    data: ['prompt' => 'Generate content'],
    provider: 'openai',
    model: 'o3-2025-04-16'
);
```

#### Visual Flow: executeWith()

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Agent     │────▶│ Switch to    │────▶│  Execute    │
│  (Claude)   │     │   OpenAI     │     │   Task      │
└─────────────┘     └──────────────┘     └─────────────┘
                             │                    │
                             ▼                    ▼
                    ┌──────────────┐     ┌─────────────┐
                    │ Auto-restore │◀────│   Return    │
                    │  to Claude   │     │   Result    │
                    └──────────────┘     └─────────────┘
```

### executeWithFallback() - Automatic Failover

Automatically try multiple providers if one fails:

```php
// Simple fallback chain
$result = $agent->executeWithFallback(
    data: ['prompt' => 'Critical task'],
    providers: ['claude', 'openai', 'ideogram']
);

// Fallback with specific models
$result = $agent->executeWithFallback(
    data: ['prompt' => 'Generate image'],
    providers: [
        ['provider' => 'openai', 'model' => 'dall-e-3'],
        ['provider' => 'openai', 'model' => 'dall-e-2'],
        ['provider' => 'ideogram', 'model' => 'V_2'],
    ]
);
```

#### Visual Flow: executeWithFallback()

```
┌──────────┐
│  Claude  │──❌ Fails
└──────────┘
     │
     ▼
┌──────────┐
│  OpenAI  │──❌ Fails
└──────────┘
     │
     ▼
┌──────────┐
│ Ideogram │──✅ Success!
└──────────┘
     │
     ▼
┌──────────┐
│  Result  │
└──────────┘
```

---

## 🔥 Advanced Features

### 1. Provider Switching

Switch providers at runtime:

```php
class MarketingAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text', 'image'];
    
    public function generateCampaign(array $data)
    {
        // Use Claude for copywriting
        $this->switchProvider('claude');
        $copy = $this->textService->generateText($data['brief']);
        
        // Switch to DALL-E for visuals
        $this->switchProvider('openai');
        $this->switchModel('dall-e-3');
        $images = $this->imageService->generateMultipleImages($copy, 3);
        
        return compact('copy', 'images');
    }
}
```

### 2. Model Capabilities

Check model capabilities before using:

```php
public function generateContent(array $data)
{
    $capabilities = $this->getModelCapabilities();
    
    if ($capabilities['supports_streaming'] ?? false) {
        return $this->streamContent($data);
    }
    
    if ($capabilities['max_tokens'] < 2000) {
        $this->switchModel('claude-4-opus-20250514');
    }
    
    return $this->textService->generateText($data['prompt']);
}
```

### 3. Temporary Operations

Execute with temporary provider/model without affecting the agent state:

```php
public function compareProviders(string $prompt)
{
    $results = [];
    
    // Test Claude
    $results['claude'] = $this->withProvider('claude', function($agent) use ($prompt) {
        return $agent->textService->generateText($prompt);
    });
    
    // Test OpenAI
    $results['openai'] = $this->withProvider('openai', function($agent) use ($prompt) {
        return $agent->textService->generateText($prompt);
    });
    
    // Agent automatically returns to original provider
    return $results;
}
```

### 4. Service Requirements

Define which services your agent needs:

```php
class MultiModalAgent extends BaseAIAgent
{
    // This agent requires all three services
    protected array $requiredServices = ['text', 'image', 'video'];
    
    public function execute(array $data)
    {
        // All services are automatically initialized
        $script = $this->textService->generateText($data['concept']);
        $storyboard = $this->imageService->generateMultipleImages($script, 5);
        $preview = $this->videoService->generateVideo($script);
        
        return compact('script', 'storyboard', 'preview');
    }
}
```

### 5. Error Handling

Built-in error handling with graceful fallbacks:

```php
public function robustGeneration(array $data)
{
    try {
        // Try with preferred provider
        return $this->executeWith($data, 'claude', 'claude-4-opus-20250514');
    } catch (\Exception $e) {
        logger()->warning('Primary provider failed', ['error' => $e->getMessage()]);
        
        // Automatic fallback chain
        return $this->executeWithFallback($data, [
            'openai',
            'claude',
            ['provider' => 'openai', 'model' => 'gpt-4.1-2025-04-14']
        ]);
    }
}
```

---

## 💡 Real-World Examples

### Example 1: Content Generation Agent

```php
<?php

namespace App\AI\Agents\Content;

use App\AI\Agents\BaseAIAgent;

class BlogPostAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text'];
    
    public function execute(array $data)
    {
        $topic = $data['topic'];
        $tone = $data['tone'] ?? 'professional';
        
        // Generate with the best model
        return $this->executeWith(
            data: [
                'prompt' => "Write a blog post about {$topic} in a {$tone} tone",
                'max_tokens' => 2000,
                'temperature' => 0.7
            ],
            provider: 'claude',
            model: 'claude-4-opus-20250514'
        );
    }
    
    public function generateWithResearch(array $data)
    {
        // Use different models for different tasks
        $research = $this->withModel('claude-3-7-sonnet-20250219', function() use ($data) {
            return $this->textService->generateText(
                "Research key points about: " . $data['topic']
            );
        });
        
        $outline = $this->withModel('claude-4-sonnet-20250514', function() use ($research) {
            return $this->textService->generateText(
                "Create blog outline based on: " . $research
            );
        });
        
        $content = $this->withModel('claude-4-opus-20250514', function() use ($outline) {
            return $this->textService->generateText(
                "Write full blog post following: " . $outline
            );
        });
        
        return compact('research', 'outline', 'content');
    }
}
```

### Example 2: Social Media Agent

```php
<?php

namespace App\AI\Agents\Marketing;

use App\AI\Agents\BaseAIAgent;

class SocialMediaAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text', 'image'];
    
    public function execute(array $data)
    {
        $campaign = $data['campaign'];
        
        // Generate content with fallback support
        return $this->executeWithFallback(
            data: $data,
            providers: [
                ['provider' => 'claude', 'model' => 'claude-4-opus-20250514'],
                ['provider' => 'openai', 'model' => 'o3-2025-04-16'],
                'claude' // Will use default model
            ]
        );
    }
    
    public function createInstagramPost(string $topic)
    {
        // Generate caption
        $caption = $this->textService->generateText(
            "Write an engaging Instagram caption about {$topic}. Include relevant hashtags."
        );
        
        // Generate image with specific style
        $image = $this->withProvider('ideogram', function() use ($topic) {
            return $this->imageService->generateImage($topic, [
                'size' => 'ASPECT_1_1',
                'style' => 'REALISTIC'
            ]);
        });
        
        return [
            'caption' => $caption,
            'image' => $image,
            'platform' => 'instagram'
        ];
    }
}
```

### Example 3: Intelligent Routing Agent

```php
<?php

namespace App\AI\Agents\Smart;

use App\AI\Agents\BaseAIAgent;

class SmartRoutingAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text'];
    
    public function execute(array $data)
    {
        $task = $this->analyzeTask($data);
        
        // Route to best provider based on task
        return match($task['type']) {
            'creative' => $this->executeWith($data, 'claude', 'claude-4-opus-20250514'),
            'analytical' => $this->executeWith($data, 'openai', 'o3-2025-04-16'),
            'quick' => $this->executeWith($data, 'claude', 'claude-3-5-haiku-20241022'),
            default => $this->executeWithFallback($data, ['claude', 'openai'])
        };
    }
    
    private function analyzeTask(array $data): array
    {
        // Analyze prompt to determine best approach
        $prompt = $data['prompt'] ?? '';
        
        if (str_contains($prompt, 'create') || str_contains($prompt, 'write')) {
            return ['type' => 'creative'];
        }
        
        if (str_contains($prompt, 'analyze') || str_contains($prompt, 'data')) {
            return ['type' => 'analytical'];
        }
        
        if (strlen($prompt) < 100) {
            return ['type' => 'quick'];
        }
        
        return ['type' => 'general'];
    }
}
```

---

## 📊 Feature Comparison

| Feature | `execute()` | `executeWith()` | `executeWithFallback()` |
|---------|------------|-----------------|-------------------------|
| Provider switching | ❌ | ✅ | ✅ |
| Model selection | ❌ | ✅ | ✅ |
| Automatic restore | N/A | ✅ | ✅ |
| Fallback support | ❌ | ❌ | ✅ |
| Error resilience | ❌ | ❌ | ✅ |

---

## 🎨 Best Practices

### 1. **Choose the Right Execution Method**
- Use `execute()` for simple, single-provider tasks
- Use `executeWith()` when you need specific provider/model
- Use `executeWithFallback()` for critical operations

### 2. **Define Service Requirements**
```php
// Only request what you need
protected array $requiredServices = ['text']; // Not ['text', 'image', 'video']
```

### 3. **Handle Errors Gracefully**
```php
public function safeExecute(array $data)
{
    return rescue(
        fn() => $this->executeWith($data, 'claude'),
        fn() => $this->executeWithFallback($data, ['openai', 'claude']),
        report: true
    );
}
```

### 4. **Use Meaningful Agent Names**
```bash
# Good
php artisan make:ai-agent Blog/SEOContentAgent
php artisan make:ai-agent Ecommerce/ProductDescriptionAgent

# Less Clear
php artisan make:ai-agent Agent1
php artisan make:ai-agent TextAgent
```

---

## 🚦 Quick Reference

```php
// Create agent
php artisan make:ai-agent AgentName [options]

// Basic usage
$agent->execute($data);

// With specific provider/model
$agent->executeWith($data, 'provider', 'model');

// With automatic fallback
$agent->executeWithFallback($data, ['provider1', 'provider2']);

// Temporary operations
$agent->withProvider('provider', fn($agent) => $agent->execute($data));
$agent->withModel('model', fn($agent) => $agent->execute($data));

// Check capabilities
$agent->getAvailableProviders();
$agent->getAvailableModels();
$agent->getModelCapabilities();
```

---

## 📚 Next Steps

1. Create your first agent: `php artisan make:ai-agent MyFirstAgent`
2. Experiment with different providers and models
3. Implement fallback strategies for production
4. Explore advanced features like streaming and functions

---

For more information, see the [full documentation](https://github.com/kaviyarasu-dev/agent).