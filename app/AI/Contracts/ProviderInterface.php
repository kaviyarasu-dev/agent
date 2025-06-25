<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface ProviderInterface
{
    public function getName(): string;
    
    public function getVersion(): string;
    
    public function supports(string $capability): bool;
    
    public function getCapabilities(): array;
    
    public function isAvailable(): bool;
}