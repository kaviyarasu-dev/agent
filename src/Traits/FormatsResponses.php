<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Traits;

use Kaviyarasu\AIAgent\Contracts\Formatters\ResponseFormatterInterface;
use Kaviyarasu\AIAgent\Formatters\ResponseFormatterFactory;
use Kaviyarasu\AIAgent\Responses\AIResponse;

trait FormatsResponses
{
    protected ResponseFormatterFactory $formatterFactory;

    protected ?ResponseFormatterInterface $currentFormatter = null;

    /**
     * Initialize the formatter factory
     */
    protected function initializeFormatterFactory(): void
    {
        if (! isset($this->formatterFactory)) {
            $this->formatterFactory = new ResponseFormatterFactory;
        }
    }

    /**
     * Get the current formatter
     */
    protected function getFormatter(): ResponseFormatterInterface
    {
        $this->initializeFormatterFactory();

        if ($this->currentFormatter === null) {
            $this->currentFormatter = $this->formatterFactory->get($this->getFormatterType());
        }

        return $this->currentFormatter;
    }

    /**
     * Format successful response
     */
    protected function formatSuccess(mixed $data, array $metadata = []): AIResponse
    {
        $metadata = $this->enrichMetadata($metadata);

        return $this->getFormatter()->formatSuccess($data, $metadata);
    }

    /**
     * Format error response
     */
    protected function formatError(string $error, array $metadata = []): AIResponse
    {
        $metadata = $this->enrichMetadata($metadata);

        return $this->getFormatter()->formatError($error, $metadata);
    }

    /**
     * Format response with timing
     */
    protected function formatWithTiming(mixed $data, float $startTime, array $metadata = []): AIResponse
    {
        $metadata = $this->enrichMetadata($metadata);

        return $this->getFormatter()->formatWithTiming($data, $startTime, $metadata);
    }

    /**
     * Enrich metadata with provider and model information
     */
    protected function enrichMetadata(array $metadata): array
    {
        // Add provider information if available
        if (method_exists($this, 'getCurrentProvider')) {
            $metadata['provider'] = $metadata['provider'] ?? $this->getCurrentProvider();
        }

        // Add model information if available
        if (method_exists($this, 'getCurrentModel')) {
            $metadata['model'] = $metadata['model'] ?? $this->getCurrentModel();
        }

        return $metadata;
    }

    /**
     * Get the formatter type for this service
     */
    abstract protected function getFormatterType(): string;

    /**
     * Execute with response formatting and timing
     */
    protected function executeWithFormatting(callable $callback, array $metadata = []): AIResponse
    {
        $startTime = microtime(true);

        try {
            $result = $callback();

            return $this->formatWithTiming($result, $startTime, $metadata);
        } catch (\Exception $e) {
            $processingTime = microtime(true) - $startTime;
            $metadata['processing_time'] = round($processingTime, 3);

            return $this->formatError($e->getMessage(), $metadata);
        }
    }

    /**
     * Set custom formatter
     */
    public function setFormatter(ResponseFormatterInterface $formatter): void
    {
        $this->currentFormatter = $formatter;
    }

    /**
     * Reset formatter to default
     */
    public function resetFormatter(): void
    {
        $this->currentFormatter = null;
    }
}
