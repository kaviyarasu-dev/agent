# AI Agent Provider & Model Switching

## Quick Start

The AI Agent Laravel package now supports comprehensive provider and model switching across all agent classes.

### Installation

The provider switching functionality is built into the core package. No additional installation required.

### Basic Usage

#### 1. Using the Trait (Simplest Method)

```php
use App\Agents\Traits\HasDynamicProvider;

class MyAgent {
    use HasDynamicProvider;
    
    public function generateContent($prompt) {
        return $this->useProvider('claude')
            ->useModel('claude-3-opus-20240229')
            ->textService->generateText($prompt);
    }
}
```

#### 2. Extending BaseAIAgent

```php
use WebsiteLearners\AIAgent\Agents\BaseAIAgent;

class BlogAgent extends BaseAIAgent {
    protected array $requiredServices = ['text'];
    
    public function execute(array $data) {
        return $this->textService->generateText($data['prompt']);
    }
}

// Usage
$agent->switchProvider('openai')
    ->switchModel('gpt-4')
    ->execute(['prompt' => 'Write about AI']);
```

#### 3. Direct Service Usage

```php
use WebsiteLearners\AIAgent\Facades\AIAgent;

AIAgent::text()
    ->switchProvider('claude')
    ->switchModel('claude-3-sonnet-20241022')
    ->generateText('Hello world');
```

### Available Providers & Models

#### Claude (Anthropic)
- `claude-3-opus-20240229` - Most capable, best for complex tasks
- `claude-3-sonnet-20241022` - Balanced performance (default)
- `claude-3-haiku-20240307` - Fastest, most cost-effective

#### OpenAI
- `gpt-4-turbo-preview` - Latest GPT-4 with 128K context
- `gpt-4` - Standard GPT-4
- `gpt-3.5-turbo` - Fast and cost-effective
- `dall-e-3` - Advanced image generation
- `dall-e-2` - Standard image generation

#### Ideogram
- `ideogram-v2` - Latest image generation
- `ideogram-v1` - Previous version

### Advanced Features

#### Temporary Provider Switching

```php
$result = $agent->withProvider('openai', function($agent) {
    return $agent->execute($data);
});
```

#### Fallback Providers

```php
$result = $agent->executeWithFallback($data, [
    ['provider' => 'claude', 'model' => 'claude-3-opus-20240229'],
    ['provider' => 'openai', 'model' => 'gpt-4'],
    ['provider' => 'openai', 'model' => 'gpt-3.5-turbo'],
]);
```

#### Multi-Service Agents

```php
class ContentCreator extends BaseAIAgent {
    protected array $requiredServices = ['text', 'image'];
    
    public function execute(array $data) {
        // Use Claude for text
        $text = $this->textService
            ->switchProvider('claude')
            ->generateText($data['prompt']);
            
        // Use DALL-E for images
        $image = $this->imageService
            ->switchProvider('openai')
            ->switchModel('dall-e-3')
            ->generateImage($data['image_prompt']);
            
        return compact('text', 'image');
    }
}
```

### Configuration

Add to your `.env`:

```env
# Default providers
AI_DEFAULT_PROVIDER=claude
AI_TEXT_PROVIDER=claude
AI_IMAGE_PROVIDER=openai

# API Keys
CLAUDE_API_KEY=your-key
OPENAI_API_KEY=your-key
IDEOGRAM_API_KEY=your-key

# Feature flags
AI_PROVIDER_SWITCHING_ENABLED=true
AI_MODEL_SWITCHING_ENABLED=true
```

### Examples

See the `app/Agents/Examples/` directory for complete examples:
- `ContentCreatorAgent.php` - Multi-service content generation
- `EmailAIAgent.php` - Email generation with provider selection
- `AdaptiveCodeAgent.php` - Code generation with automatic model selection

### Testing

Run the provider switching tests:

```bash
php artisan test --filter=ProviderSwitchingTest
```

### Migration from Existing Agents

1. Add the trait to existing classes:
   ```php
   use HasDynamicProvider;
   ```

2. Or extend BaseAIAgent:
   ```php
   extends BaseAIAgent
   ```

3. No other changes required - backward compatible!

### Support

For detailed documentation, see `docs/PROVIDER_SWITCHING.md`.

For issues or questions, please open a GitHub issue.