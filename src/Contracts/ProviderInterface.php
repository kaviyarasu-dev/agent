<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Contracts;

interface ProviderInterface extends HasModelSwitching
{
    public function getName(): string;

    public function getVersion(): string;

    public function supports(string $capability): bool;

    public function getCapabilities(): array;

    public function isAvailable(): bool;

    /**
     * Get default model for this provider.
     */
    public function getDefaultModel(): string;

    /**
     * Validate provider configuration.
     *
     * @throws \Exception if configuration is invalid
     */
    public function validateConfiguration(): bool;

    /**
     * Get provider-specific configuration.
     */
    public function getConfiguration(): array;

    /**
     * Set provider configuration.
     */
    public function setConfiguration(array $config): self;
}
