<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Providers\AI\OpenAI;

use Illuminate\Support\Facades\Http;
use WebsiteLearners\AIAgent\Contracts\Capabilities\ImageGenerationInterface;
use WebsiteLearners\AIAgent\Contracts\Capabilities\TextGenerationInterface;
use WebsiteLearners\AIAgent\Exceptions\AIAgentException;
use WebsiteLearners\AIAgent\Providers\AI\AbstractProvider;

class OpenAIProvider extends AbstractProvider implements TextGenerationInterface, ImageGenerationInterface
{
    protected array $supportedModels = [];

    protected array $modelCapabilities = [];

    private const TEXT_API_URL = 'https://api.openai.com/v1/chat/completions';

    private const IMAGE_API_URL = 'https://api.openai.com/v1/images/generations';

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function loadModelsFromConfig(): void
    {
        $models = config('ai-agent.providers.openai.models', []);
        $this->supportedModels = array_keys($models);
        foreach ($models as $modelKey => $modelConfig) {
            $this->modelCapabilities[$modelKey] = $modelConfig;
        }
    }

    public function getName(): string
    {
        return 'OpenAI';
    }

    public function getVersion(): string
    {
        return '1.0';
    }

    public function supports(string $capability): bool
    {
        $modelCapabilities = $this->modelCapabilities[$this->currentModel]['capabilities'] ?? [];
        return in_array($capability, $modelCapabilities);
    }

    public function getCapabilities(): array
    {
        $capabilities = [];
        foreach ($this->modelCapabilities as $model => $config) {
            $capabilities = array_merge($capabilities, $config['capabilities'] ?? []);
        }
        return array_unique($capabilities);
    }

    public function generateText(array $params): string
    {
        if (!$this->supports('text')) {
            throw new AIAgentException("Model {$this->currentModel} does not support text generation");
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::TEXT_API_URL, [
            'model' => $this->currentModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $params['prompt'],
                ],
            ],
            'temperature' => $params['temperature'] ?? 0.7,
            'max_tokens' => $params['max_tokens'] ?? $this->getMaxTokens(),
        ]);

        if (! $response->successful()) {
            throw new AIAgentException('OpenAI API error: ' . $response->body());
        }

        return $response->json()['choices'][0]['message']['content'] ?? '';
    }

    public function streamText(array $params): iterable
    {
        if (!$this->supports('text')) {
            throw new AIAgentException("Model {$this->currentModel} does not support text generation");
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->withOptions([
            'stream' => true,
        ])->post(self::TEXT_API_URL, [
            'model' => $this->currentModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $params['prompt'],
                ],
            ],
            'temperature' => $params['temperature'] ?? 0.7,
            'max_tokens' => $params['max_tokens'] ?? $this->getMaxTokens(),
            'stream' => true,
        ]);

        $body = $response->getBody();
        while (!$body->eof()) {
            $chunk = $body->read(1024);
            if ($chunk) {
                yield $chunk;
            }
        }
    }

    public function generateImage(array $params): string
    {
        if (!$this->supports('image')) {
            throw new AIAgentException("Model {$this->currentModel} does not support image generation");
        }

        $requestParams = [
            'model' => $this->currentModel,
            'prompt' => $params['prompt'],
            'n' => 1,
            'size' => $params['size'] ?? '1024x1024',
        ];

        if ($this->currentModel === 'dall-e-3' && isset($params['quality'])) {
            $requestParams['quality'] = $params['quality'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::IMAGE_API_URL, $requestParams);

        if (! $response->successful()) {
            throw new AIAgentException('OpenAI API error: ' . $response->body());
        }

        $data = $response->json()['data'] ?? [];

        if (empty($data)) {
            throw new AIAgentException('No image data returned from OpenAI API');
        }

        return $data[0]['url'] ?? '';
    }

    public function generateImages(array $params): array
    {
        if (!$this->supports('image')) {
            throw new AIAgentException("Model {$this->currentModel} does not support image generation");
        }

        $requestParams = [
            'model' => $this->currentModel,
            'prompt' => $params['prompt'],
            'n' => $params['n'] ?? 1,
            'size' => $params['size'] ?? '1024x1024',
        ];

        if ($this->currentModel === 'dall-e-3' && isset($params['quality'])) {
            $requestParams['quality'] = $params['quality'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::IMAGE_API_URL, $requestParams);

        if (! $response->successful()) {
            throw new AIAgentException('OpenAI API error: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    public function getSupportedFormats(): array
    {
        return ['png'];
    }

    public function getMaxResolution(): array
    {
        if ($this->currentModel === 'dall-e-3') {
            return [
                'width' => 1792,
                'height' => 1024,
            ];
        }

        return [
            'width' => 1024,
            'height' => 1024,
        ];
    }

    public function getMaxTokens(): int
    {
        return $this->modelCapabilities[$this->currentModel]['max_tokens'] ?? 4096;
    }

    public function getModelCapabilities(?string $model = null): array
    {
        $model = $model ?? $this->currentModel;

        return $this->modelCapabilities[$model] ?? [
            'max_tokens' => 4096,
            'supports_streaming' => true,
            'supports_functions' => false,
        ];
    }

    public function getAvailableModels(): array
    {
        return $this->supportedModels;
    }

    public function getDefaultModel(): string
    {
        return config('ai-agent.providers.openai.default_model', 'gpt-4');
    }
}
