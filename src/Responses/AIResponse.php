<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Responses;

use JsonSerializable;

abstract class AIResponse implements JsonSerializable
{
    protected bool $success;

    protected mixed $data;

    protected array $metadata;

    protected ?string $error;

    public function __construct(
        bool $success = true,
        mixed $data = null,
        array $metadata = [],
        ?string $error = null
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->metadata = array_merge($this->getDefaultMetadata(), $metadata);
        $this->error = $error;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function addMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }

    protected function getDefaultMetadata(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'processing_time' => 0,
            'provider' => null,
            'model' => null,
        ];
    }

    public function jsonSerialize(): array
    {
        $response = [
            'success' => $this->success,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];

        if ($this->error) {
            $response['error'] = $this->error;
        }

        return $response;
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    public function toJson(): string
    {
        return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public static function success(mixed $data = null, array $metadata = []): static
    {
        return new static(true, $data, $metadata);
    }

    public static function error(string $error, array $metadata = []): static
    {
        return new static(false, null, $metadata, $error);
    }
}
