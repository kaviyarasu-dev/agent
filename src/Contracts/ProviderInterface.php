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
     *
     * @return string
     */
    public function getDefaultModel(): string;

    /**
     * Validate provider configuration.
     *
     * @throws \Exception if configuration is invalid
     * @return bool
     */
    public function validateConfiguration(): bool;

    /**
     * Get provider-specific configuration.
     *
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * Set provider configuration.
     *
     * @param array $config
     * @return self
     */
    public function setConfiguration(array $config): self;
}
