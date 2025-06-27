<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Contracts;

/**
 * Interface for classes that support provider switching.
 */
interface HasProviderSwitching
{
    /**
     * Switch to a different provider.
     *
     * @param string $providerName
     * @return self
     */
    public function switchProvider(string $providerName): self;

    /**
     * Get the current provider name.
     *
     * @return string
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
     *
     * @param string $providerName
     * @return bool
     */
    public function hasProvider(string $providerName): bool;

    /**
     * Execute with a temporary provider.
     *
     * @param string $providerName
     * @param callable $callback
     * @return mixed
     */
    public function withProvider(string $providerName, callable $callback);
}