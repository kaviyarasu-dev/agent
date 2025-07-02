<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Providers\AI\Ideogram;

use Illuminate\Support\Facades\Http;
use Kaviyarasu\AIAgent\Contracts\Capabilities\ImageGenerationInterface;
use Kaviyarasu\AIAgent\Exceptions\AIAgentException;
use Kaviyarasu\AIAgent\Providers\AI\AbstractProvider;

class IdeogramProvider extends AbstractProvider implements ImageGenerationInterface
{
    protected array $supportedModels = [];

    protected array $modelCapabilities = [];

    private const API_URL = 'https://api.ideogram.ai/generate';

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function loadModelsFromConfig(): void
    {
        $models = config('ai-agent.providers.ideogram.models', []);
        $this->supportedModels = array_keys($models);
        foreach ($models as $modelKey => $modelConfig) {
            $this->modelCapabilities[$modelKey] = $modelConfig;
        }
    }

    public function getName(): string
    {
        return 'Ideogram';
    }

    public function getVersion(): string
    {
        return '1.0';
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, ['image']);
    }

    public function getCapabilities(): array
    {
        return ['image'];
    }

    public function generateImage(array $params): string
    {
        $requestParams = [
            'prompt' => $params['prompt'],
            'model' => $this->currentModel,
            'seed' => $params['seed'] ?? null,
            'resolution' => $params['resolution'] ?? '1024x1024',
        ];

        if (isset($params['style']) && in_array($params['style'], $this->getAvailableStyles())) {
            $requestParams['style'] = $params['style'];
        }

        $response = Http::withHeaders([
            'Api-Key' => $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post(self::API_URL, $requestParams);

        if (! $response->successful()) {
            logger()->error('Ideogram API error:', $requestParams);
            throw new AIAgentException('Ideogram API error: ' . $response->body());
        }

        $data = $response->json();
        return $data['data'][0]['url'] ?? '';
    }

    public function generateImages(array $params): array
    {
        $count = $params['n'] ?? 1;
        $images = [];

        for ($i = 0; $i < $count; $i++) {
            $requestParams = [
                'prompt' => $params['prompt'],
                'model' => $this->currentModel,
                'seed' => $params['seed'] ?? null,
                'resolution' => $params['resolution'] ?? '1024x1024',
            ];

            if (isset($params['style']) && in_array($params['style'], $this->getAvailableStyles())) {
                $requestParams['style'] = $params['style'];
            }

            $response = Http::withHeaders([
                'Api-Key' => $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])->post(self::API_URL, $requestParams);

            if (! $response->successful()) {
                throw new AIAgentException('Ideogram API error: ' . $response->body());
            }

            $data = $response->json();
            $images[] = $data['data'][0]['url'] ?? '';
        }

        return $images;
    }

    public function getSupportedFormats(): array
    {
        return ['png', 'jpg'];
    }

    public function getMaxResolution(): array
    {
        return [
            'width' => 1920,
            'height' => 1080,
        ];
    }

    public function getAvailableStyles(): array
    {
        return $this->modelCapabilities[$this->currentModel]['styles'] ?? ['realistic', 'design'];
    }

    public function getModelCapabilities(?string $model = null): array
    {
        $model = $model ?? $this->currentModel;
        return $this->modelCapabilities[$model] ?? [
            'styles' => ['realistic', 'design'],
            'capabilities' => ['image'],
        ];
    }

    public function getAvailableModels(): array
    {
        return $this->supportedModels;
    }

    public function getDefaultModel(): string
    {
        return config('ai-agent.providers.ideogram.default_model', 'ideogram-v2');
    }
}
