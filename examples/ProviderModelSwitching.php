<?php

declare(strict_types=1);

/**
 * Examples of switching providers and models in AI Agents
 */

use App\Agents\Blog\BlogAiAgentAdvanced;
use App\Agents\Blog\BlogAiAgentWithTrait;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;

// ===================================================================
// Method 1: Using BlogAiAgentAdvanced (Direct Provider Access)
// ===================================================================

// Initialize the agent
$agent = app(BlogAiAgentAdvanced::class);

// Switch to different providers
$agent->switchProvider('claude');
$agent->switchProvider('openai');

// Switch models for current provider
$agent->switchModel('gpt-4-turbo'); // For OpenAI
$agent->switchProvider('claude')->switchModel('claude-3-opus-20240229'); // Chain methods

// Get current configuration
echo 'Current Provider: '.$agent->getCurrentProvider()."\n";
echo 'Current Model: '.$agent->getCurrentModel()."\n";

// Get available models for current provider
$models = $agent->getAvailableModels();
print_r($models);

// Execute with current settings
$content = $agent->execute([
    'prompt' => 'Write about AI ethics',
    'options' => ['tone' => 'professional', 'length' => 'medium'],
]);

// Execute with temporary provider/model (without changing defaults)
$content = $agent->executeWith(
    [
        'prompt' => 'Write about quantum computing',
        'options' => ['tone' => 'casual', 'length' => 'short'],
    ],
    'openai',      // Use OpenAI
    'gpt-4'        // Use GPT-4 model
);

// ===================================================================
// Method 2: Using Trait-Based Agent
// ===================================================================

$agentWithTrait = app(BlogAiAgentWithTrait::class);

// Switch providers and models fluently
$agentWithTrait
    ->useProvider('claude')
    ->useModel('claude-3-sonnet-20241022');

// Get available providers for text generation
$textProviders = $agentWithTrait->getAvailableProviders('text');
print_r($textProviders);

// Get available models for a specific provider
$claudeModels = $agentWithTrait->getAvailableModels('claude');
$openaiModels = $agentWithTrait->getAvailableModels('openai');

// Execute with temporary configuration
$content = $agentWithTrait->executeWith(
    ['prompt' => 'Write about Laravel', 'options' => []],
    'openai',
    'gpt-3.5-turbo'
);

// ===================================================================
// Method 3: Direct Provider Factory Usage (Most Control)
// ===================================================================

$providerFactory = app(ProviderFactory::class);

// Create specific provider
$claudeProvider = $providerFactory->create('claude');
$claudeProvider->switchModel('claude-3-sonnet-20241128');

// Use the provider directly
$result = $claudeProvider->generateText([
    'prompt' => 'Write a haiku about programming',
    'temperature' => 0.5,
    'max_tokens' => 100,
]);

// ===================================================================
// Method 4: Creating a Custom Agent with Provider Switching
// ===================================================================

class CustomAiAgent
{
    private ProviderFactory $providerFactory;

    private ?string $provider = null;

    private ?string $model = null;

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function generate(string $prompt): string
    {
        $provider = $this->providerFactory->create($this->provider ?? 'claude');

        if ($this->model && method_exists($provider, 'switchModel')) {
            $provider->switchModel($this->model);
        }

        return $provider->generateText([
            'prompt' => $prompt,
            'max_tokens' => 1000,
        ]);
    }
}

// Usage
$customAgent = app(CustomAiAgent::class);
$result = $customAgent
    ->setProvider('openai')
    ->setModel('gpt-4')
    ->generate('Explain machine learning');

// ===================================================================
// Method 5: Configuration-Based Switching
// ===================================================================

// In a service provider or config:
config(['ai-agent.providers.claude.default_model' => 'claude-3-opus-20240229']);

// Or use environment variables:
// CLAUDE_MODEL=claude-3-opus-20240229
// OPENAI_MODEL=gpt-4-turbo

// ===================================================================
// Practical Examples
// ===================================================================

// Example 1: Compare outputs from different models
$agent = app(BlogAiAgentAdvanced::class);
$topic = 'The Future of Web Development';

$results = [];

// Try with Claude Sonnet
$results['claude-sonnet'] = $agent
    ->switchProvider('claude')
    ->switchModel('claude-3-sonnet-20241022')
    ->execute(['prompt' => $topic, 'options' => ['length' => 'short']]);

// Try with Claude Opus
$results['claude-opus'] = $agent
    ->switchModel('claude-3-opus-20240229')
    ->execute(['prompt' => $topic, 'options' => ['length' => 'short']]);

// Try with OpenAI GPT-4
$results['gpt-4'] = $agent
    ->switchProvider('openai')
    ->switchModel('gpt-4')
    ->execute(['prompt' => $topic, 'options' => ['length' => 'short']]);

// Example 2: Fallback mechanism
function generateWithFallback(string $prompt, array $providers): ?string
{
    $agent = app(BlogAiAgentAdvanced::class);

    foreach ($providers as $config) {
        try {
            return $agent
                ->switchProvider($config['provider'])
                ->switchModel($config['model'])
                ->execute(['prompt' => $prompt, 'options' => []]);
        } catch (\Exception $e) {
            logger()->warning("Provider {$config['provider']} failed: ".$e->getMessage());

            continue;
        }
    }

    return null;
}

$content = generateWithFallback('Write about PHP', [
    ['provider' => 'claude', 'model' => 'claude-3-sonnet-20241022'],
    ['provider' => 'openai', 'model' => 'gpt-4'],
    ['provider' => 'openai', 'model' => 'gpt-3.5-turbo'],
]);

// Example 3: Model selection based on task
class SmartBlogAgent extends BlogAiAgentAdvanced
{
    public function executeSmartly(array $data)
    {
        $length = $data['options']['length'] ?? 'medium';

        // Use different models based on requirements
        if ($length === 'long') {
            // Use more capable model for long content
            $this->switchProvider('claude')->switchModel('claude-3-opus-20240229');
        } elseif ($length === 'short') {
            // Use faster model for short content
            $this->switchProvider('claude')->switchModel('claude-3-haiku-20240307');
        } else {
            // Default for medium
            $this->switchProvider('claude')->switchModel('claude-3-sonnet-20241022');
        }

        return $this->execute($data);
    }
}

// Example 4: Testing different providers in PHPUnit
class BlogAgentTest extends TestCase
{
    public function test_with_different_providers()
    {
        $agent = app(BlogAiAgentAdvanced::class);
        $data = ['prompt' => 'Test topic', 'options' => []];

        // Test with Claude
        $agent->switchProvider('claude');
        $claudeResult = $agent->execute($data);
        $this->assertNotEmpty($claudeResult);

        // Test with OpenAI
        $agent->switchProvider('openai');
        $openaiResult = $agent->execute($data);
        $this->assertNotEmpty($openaiResult);
    }
}
