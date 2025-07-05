<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Providers\AI\Runware;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Kaviyarasu\AIAgent\Contracts\Capabilities\ImageGenerationInterface;
use Kaviyarasu\AIAgent\Exceptions\AIAgentException;
use Kaviyarasu\AIAgent\Providers\AI\AbstractProvider;

class RunwareProvider extends AbstractProvider implements ImageGenerationInterface
{
    protected array $supportedModels = [];

    protected array $modelCapabilities = [];

    private const API_URL = 'https://api.runware.ai/v1';

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function loadModelsFromConfig(): void
    {
        $models = config('ai-agent.providers.runware.models', []);
        $this->supportedModels = array_keys($models);
        foreach ($models as $modelKey => $modelConfig) {
            $this->modelCapabilities[$modelKey] = $modelConfig;
        }
    }

    public function getName(): string
    {
        return 'Runware';
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
        return $this->generateImages($params)[0] ?? '';
    }

    public function generateImages(array $params): array
    {
        $requestParams = [
            'taskType' => 'imageInference',
            'taskUUID' => (string) Str::uuid(), // Generate a UUID for each task
            'positivePrompt' => $params['prompt'],
            'model' => $this->currentModel,
            'numberResults' => $params['n'] ?? 1,
            ...$params,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->post(self::API_URL, [
                [
                    'taskType' => 'authentication',
                    'apiKey' => $this->config['api_key'],
                ],
                $requestParams,
            ]);

        if (! $response->successful()) {
            logger()->error('Runware API error:', $requestParams);

            throw new AIAgentException('Runware API error: '.$response->body());
        }

        $data = $response->json();

        return collect($data['data'])->pluck('imageURL')->all() ?? [];
    }

    public function getSupportedFormats(): array
    {
        return ['PNG', 'JPG', 'WEBP'];
    }

    public function getMaxResolution(): array
    {
        return [
            'width' => 2048,
            'height' => 2048,
        ];
    }

    public function getModelCapabilities(?string $model = null): array
    {
        $model = $model ?? $this->currentModel;

        return $this->modelCapabilities[$model] ?? [
            'capabilities' => ['image'],
        ];
    }

    public function getAvailableModels(): array
    {
        return $this->supportedModels;
    }

    public function getDefaultModel(): string
    {
        return config('ai-agent.providers.runware.default_model', 'runware:97@2');
    }
}
