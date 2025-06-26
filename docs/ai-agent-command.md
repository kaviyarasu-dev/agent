# AI Agent Command Documentation

The `ai-agent` command is a powerful scaffolding tool that helps you quickly create AI agent classes with various capabilities and configurations.

## Basic Usage

Create a simple text-based AI agent:

```bash
php artisan ai-agent Blog/BlogAiAgent
```

This creates an AI agent at `app/AI/Agents/Blog/BlogAiAgent.php` with text generation capabilities.

## Command Options

### Capability Flag

Specify the AI capability your agent will use:

```bash
# Text generation (default)
php artisan ai-agent Blog/BlogAiAgent --capability=text

# Image generation
php artisan ai-agent ImageGenerator --capability=image

# Video generation
php artisan ai-agent VideoCreator --capability=video
```

### Provider and Model Configuration

Set default provider and model for your agent:

```bash
php artisan ai-agent Blog/BlogAiAgent --provider=claude --model=claude-3-sonnet-20241022

php artisan ai-agent ImageGen --capability=image --provider=ideogram --model=ideogram-v1
```

### Path Notation

You can use forward slashes for nested directories:

```bash
# Creates app/AI/Agents/Blog/Post/BlogPostAgent.php
php artisan ai-agent blog/post/BlogPostAgent

# Creates app/AI/Agents/Content/Generator/TextGenerator.php
php artisan ai-agent content/generator/TextGenerator
```

### Traits for Common Behaviors

Include helpful traits for logging and fallback functionality:

```bash
# Add logging capability
php artisan ai-agent TrackedAgent --with-logging

# Add fallback provider support
php artisan ai-agent ResilientAgent --with-fallback

# Include both traits
php artisan ai-agent ProductionAgent --with-logging --with-fallback
```

### Force Overwrite

Overwrite existing files with the `--force` flag:

```bash
php artisan ai-agent ExistingAgent --force
```

## Interactive Mode

Run the command without arguments for interactive mode:

```bash
php artisan ai-agent
```

You'll be prompted for:
- Agent class name
- Capability type
- Default provider (optional)
- Default model (optional)
- Whether to include logging
- Whether to include fallback support

## Generated Agent Structure

A generated agent with all options looks like this:

```php
<?php

declare(strict_types=1);

namespace App\Agents\Blog;

use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use App\AI\Traits\LogsAIUsage;
use App\AI\Traits\UsesFallbackProvider;

/**
 * Class BlogAiAgent
 *
 * This agent uses the AI text service to generate content.
 * Default Provider: claude
 * Capability: text
 *
 * You can change the provider using $this->textService->setProvider('provider_name');
 * You can switch models using $this->textService->switchModel('model_name');
 */
class BlogAiAgent
{
    use LogsAIUsage, UsesFallbackProvider;

    /**
     * The AI text service instance.
     *
     * @var TextServiceInterface
     */
    protected TextServiceInterface $textService;

    /**
     * Create a new AI agent instance.
     *
     * @param  TextServiceInterface  $textService
     */
    public function __construct(TextServiceInterface $textService)
    {
        $this->textService = $textService;
        
        // Initialize provider and model
        $this->textService->setProvider('claude');
        $this->textService->switchModel('claude-3-sonnet-20241022');
    }

    /**
     * Execute the AI agent logic.
     *
     * @param  array  $data
     * @return mixed
     */
    public function execute(array $data)
    {
        // TODO: Implement your AI agent logic here
        
        // Example usage:
        // return $this->textService->generate($prompt, $options);
    }
}
```

## Examples

### Blog Content Generator

```bash
php artisan ai-agent blog/ContentGenerator --provider=claude --model=claude-3-sonnet-20241022 --with-logging
```

### Image Processor with Fallback

```bash
php artisan ai-agent media/ImageProcessor --capability=image --provider=ideogram --with-fallback
```

### Video Editor with Full Features

```bash
php artisan ai-agent video/editor/VideoEditor --capability=video --provider=openai --with-logging --with-fallback
```

## Validation

The command includes smart validation:

- **Invalid capability**: Shows available options
- **Invalid provider**: Suggests similar provider names
- **Existing files**: Prevents accidental overwrites (use `--force` to override)

Example validation messages:

```bash
# Invalid capability
Invalid capability 'audio'. Valid options are: text, image, video

# Invalid provider with suggestion
Provider 'claudee' does not support 'text' capability.
Did you mean: claude?

# File exists
app/AI/Agents/ExistingAgent.php already exists. Use --force to overwrite.
```

## Best Practices

1. **Organize by Feature**: Group related agents in subdirectories
   ```bash
   php artisan ai-agent blog/post/PostGenerator
   php artisan ai-agent blog/comment/CommentModerator
   php artisan ai-agent blog/seo/MetaGenerator
   ```

2. **Use Descriptive Names**: Choose names that clearly indicate the agent's purpose
   ```bash
   php artisan ai-agent content/BlogContentGenerator  # Good
   php artisan ai-agent Agent1  # Bad
   ```

3. **Configure Defaults**: Set provider and model for consistency
   ```bash
   php artisan ai-agent ProductionAgent --provider=claude --model=claude-3-sonnet-20241022
   ```

4. **Add Logging in Production**: Always include logging for production agents
   ```bash
   php artisan ai-agent production/CriticalAgent --with-logging --with-fallback
   ```

5. **Use Fallback for Critical Services**: Ensure high availability
   ```bash
   php artisan ai-agent critical/PaymentDescriptionGenerator --with-fallback
   ```

## Trait Documentation

### LogsAIUsage Trait

Provides methods for logging AI operations:

- `logAIRequest()`: Log request details
- `logAIResponse()`: Log response and performance metrics
- `logAIError()`: Log errors with full context
- `executeWithLogging()`: Wrap operations with automatic logging

### UsesFallbackProvider Trait

Enables automatic provider fallback:

- `setFallbackProviders()`: Configure fallback order
- `addFallbackProvider()`: Add a single fallback
- `executeWithFallback()`: Execute with automatic fallback
- `hasFallbackProviders()`: Check if fallbacks are configured

## Integration Example

Using the generated agent in your application:

```php
use App\Agents\Blog\BlogAiAgent;

class BlogController extends Controller
{
    public function __construct(
        private BlogAiAgent $blogAgent
    ) {}

    public function generatePost(Request $request)
    {
        // Agent automatically uses configured provider/model
        $result = $this->blogAgent->execute([
            'topic' => $request->input('topic'),
            'tone' => 'professional',
            'length' => 'medium'
        ]);

        return response()->json($result);
    }
}
```

With traits enabled:

```php
// In your agent's execute method
public function execute(array $data)
{
    // With logging trait
    return $this->executeWithLogging(
        fn() => $this->textService->generate($data['prompt']),
        'textService',
        'generate',
        $data
    );

    // With fallback trait
    return $this->executeWithFallback(
        fn() => $this->textService->generate($data['prompt']),
        'textService'
    );
}
```
