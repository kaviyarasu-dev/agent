<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Contracts;

/**
 * Interface for classes that support model switching.
 */
interface HasModelSwitching
{
    /**
     * Switch to a different model.
     */
    public function switchModel(string $model): self;

    /**
     * Get the current model.
     */
    public function getCurrentModel(): string;

    /**
     * Get available models.
     *
     * @return array<string>
     */
    public function getAvailableModels(): array;

    /**
     * Check if a model is available.
     */
    public function hasModel(string $model): bool;

    /**
     * Execute with a temporary model.
     *
     * @return mixed
     */
    public function withModel(string $model, callable $callback);

    /**
     * Get model capabilities and limits.
     *
     * @return array{max_tokens?: int, supports_streaming?: bool, supports_functions?: bool}
     */
    public function getModelCapabilities(?string $model = null): array;
}
