<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Contracts;

/**
 * Interface for classes that support provider switching.
 */
interface HasProviderSwitching
{
    /**
     * Switch to a different provider.
     */
    public function switchProvider(string $providerName): self;

    /**
     * Get the current provider name.
     */
    public function getCurrentProvider(): string;

    /**
     * Get available providers for the service.
     *
     * @return array<string, array{name: string, available: bool, capabilities: array}>
     */
    public function getAvailableProviders(): array;

    /**
     * Check if a provider is available.
     */
    public function hasProvider(string $providerName): bool;

    /**
     * Execute with a temporary provider.
     *
     * @return mixed
     */
    public function withProvider(string $providerName, callable $callback);
}
