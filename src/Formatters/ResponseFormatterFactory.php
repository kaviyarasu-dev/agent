<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Formatters;

use Kaviyarasu\AIAgent\Contracts\Formatters\ResponseFormatterInterface;
use Kaviyarasu\AIAgent\Exceptions\AIAgentException;

class ResponseFormatterFactory
{
    private array $formatters = [];

    public function __construct()
    {
        $this->registerDefaultFormatters();
    }

    /**
     * Register a formatter for a specific type
     */
    public function register(string $type, ResponseFormatterInterface $formatter): void
    {
        $this->formatters[$type] = $formatter;
    }

    /**
     * Get a formatter for a specific type
     */
    public function get(string $type): ResponseFormatterInterface
    {
        if (!isset($this->formatters[$type])) {
            throw new AIAgentException("No formatter registered for type: {$type}");
        }

        return $this->formatters[$type];
    }

    /**
     * Check if a formatter exists for a type
     */
    public function has(string $type): bool
    {
        return isset($this->formatters[$type]);
    }

    /**
     * Get all registered formatters
     */
    public function getAll(): array
    {
        return $this->formatters;
    }

    /**
     * Register default formatters
     */
    private function registerDefaultFormatters(): void
    {
        $this->register('text', new TextResponseFormatter());
        $this->register('image', new ImageResponseFormatter());
    }

    /**
     * Create formatter instance from type
     */
    public function create(string $type): ResponseFormatterInterface
    {
        return $this->get($type);
    }

    /**
     * Register multiple formatters at once
     */
    public function registerMultiple(array $formatters): void
    {
        foreach ($formatters as $type => $formatter) {
            $this->register($type, $formatter);
        }
    }
}
