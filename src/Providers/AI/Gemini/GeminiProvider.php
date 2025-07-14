<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Providers\AI\Gemini;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kaviyarasu\AIAgent\Contracts\Capabilities\ImageGenerationInterface;
use Kaviyarasu\AIAgent\Contracts\Capabilities\TextGenerationInterface;
use Kaviyarasu\AIAgent\Exceptions\AIAgentException;
use Kaviyarasu\AIAgent\Providers\AI\AbstractProvider;

class GeminiProvider extends AbstractProvider implements ImageGenerationInterface, TextGenerationInterface
{
    protected array $supportedModels = [];

    protected array $modelCapabilities = [];

    private const TEXT_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    private const IMAGE_API_URL = 'https://generativelanguage.googleapis.com/v1beta/openai/images/generations';
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function loadModelsFromConfig(): void
    {
        $models = config('ai-agent.providers.gemini.models', []);
        $this->supportedModels = array_keys($models);
        foreach ($models as $modelKey => $modelConfig) {
            $this->modelCapabilities[$modelKey] = $modelConfig;
        }
    }

    public function getName(): string
    {
        return 'Gemini';
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

        $requestParams = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $params['prompt'],
                        ]
                    ]
                ]
            ],
        ];

        $response = Http::timeout(350)
            ->connectTimeout(350)
            ->withHeaders([
                'x-goog-api-key' => $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])
            ->post(self::TEXT_API_URL . $this->currentModel . ':generateContent', $requestParams);

        Log::info('Gemini Response', [$response->json(), 'curent_model' => $this->currentModel, $requestParams]);

        if (!$response->successful()) {
            logger()->error('Gemini API error:', $requestParams);

            throw new AIAgentException('Gemini API error: ' . $response->body());
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    public function streamText(array $params): iterable
    {
        if (!$this->supports('text')) {
            throw new AIAgentException("Model {$this->currentModel} does not support text generation");
        }

        $requestParams = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $params['prompt'],
                        ]
                    ]
                ]
            ],
        ];

        $response = Http::timeout(350)
            ->connectTimeout(350)
            ->withHeaders([
                'x-goog-api-key' => $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])
            ->post(self::TEXT_API_URL . $this->currentModel . ':streamGenerateContent?alt=sse', $requestParams);

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

        return $this->generateImages($params)[0] ?? '';
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
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::IMAGE_API_URL, $requestParams);

        Log::info('Gemini Response', [$response->json()]);

        if (!$response->successful()) {
            throw new AIAgentException('Gemini Image API error: ' . $response->body());
        }

        $data = $response->json();

        return collect($data['data'])->pluck('url')->all() ?? [];
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
        return config('ai-agent.providers.gemini.default_model', 'gpt-4');
    }
}
