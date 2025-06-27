<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Providers\AI\Claude;

use Illuminate\Support\Facades\Http;
use WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface;
use WebsiteLearners\AIAgent\Exceptions\AIAgentException;
use WebsiteLearners\AIAgent\Providers\AI\AbstractProvider;

class ClaudeProvider extends AbstractProvider implements TextGenerationInterface
{
    protected array $supportedModels = [];

    protected array $modelCapabilities = [];

    private const API_URL = 'https://api.anthropic.com/v1/messages';

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * Load supported models and their capabilities from config
     */
    protected function loadModelsFromConfig(): void
    {
        $models = config('ai-agent.providers.claude.models', []);

        $this->supportedModels = array_keys($models);

        // Load model capabilities
        foreach ($models as $modelKey => $modelConfig) {
            $this->modelCapabilities[$modelKey] = [
                'max_tokens' => $modelConfig['max_tokens'] ?? 4096,
                'supports_streaming' => $modelConfig['supports_streaming'] ?? true,
                'supports_functions' => $modelConfig['supports_functions'] ?? false,
                'name' => $modelConfig['name'] ?? $modelKey,
                'version' => $modelConfig['version'] ?? 'unknown',
            ];
        }
    }

    public function getName(): string
    {
        return 'Claude';
    }

    public function getVersion(): string
    {
        return '3.0';
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, ['text']);
    }

    public function getCapabilities(): array
    {
        return ['text'];
    }

    public function generateText(array $params): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post(self::API_URL, [
            'model' => $this->currentModel,
            'max_tokens' => $params['max_tokens'] ?? $this->getMaxTokens(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $params['prompt'],
                ],
            ],
            'temperature' => $params['temperature'] ?? 0.7,
        ]);

        if (! $response->successful()) {
            throw new AIAgentException('Claude API error: ' . $response->body());
        }

        return $response->json()['content'][0]['text'] ?? '';
    }

    public function streamText(array $params): iterable
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->withOptions([
            'stream' => true,
        ])->post(self::API_URL, [
            'model' => $this->currentModel,
            'max_tokens' => $params['max_tokens'] ?? $this->getMaxTokens(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $params['prompt'],
                ],
            ],
            'temperature' => $params['temperature'] ?? 0.7,
            'stream' => true,
        ]);

        // Handle streaming response
        $body = $response->getBody();
        while (!$body->eof()) {
            $chunk = $body->read(1024);
            if ($chunk) {
                yield $chunk;
            }
        }
    }

    /**
     * Get max tokens for current model from config
     */
    public function getMaxTokens(): int
    {
        return $this->modelCapabilities[$this->currentModel]['max_tokens'] ?? 4096;
    }

    /**
     * {@inheritdoc}
     */
    public function getModelCapabilities(?string $model = null): array
    {
        $model = $model ?? $this->currentModel;

        return $this->modelCapabilities[$model] ?? [
            'max_tokens' => 4096,
            'supports_streaming' => true,
            'supports_functions' => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableModels(): array
    {
        return $this->supportedModels;
    }

    public function getDefaultModel(): string
    {
        return config('ai-agent.providers.claude.default_model', 'claude-3-5-sonnet-20241022');
    }
}
