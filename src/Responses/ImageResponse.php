<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Responses;

class ImageResponse extends AIResponse
{
    public function __construct(
        bool $success = true,
        mixed $imageData = null,
        array $metadata = [],
        ?string $error = null
    ) {
        $data = $success ? $this->formatImageData($imageData) : null;
        parent::__construct($success, $data, $metadata, $error);
    }

    public function getUrl(): ?string
    {
        if (is_string($this->data)) {
            return $this->data;
        }

        return $this->data['url'] ?? $this->data[0] ?? null;
    }

    public function getUrls(): array
    {
        if (is_string($this->data)) {
            return [$this->data];
        }

        if (isset($this->data['urls'])) {
            return $this->data['urls'];
        }

        if (is_array($this->data) && isset($this->data[0])) {
            return $this->data;
        }

        return [];
    }

    public function getSize(): ?string
    {
        return $this->metadata['size'] ?? null;
    }

    public function getQuality(): ?string
    {
        return $this->metadata['quality'] ?? null;
    }

    public function getStyle(): ?string
    {
        return $this->metadata['style'] ?? null;
    }

    public function getProvider(): ?string
    {
        return $this->metadata['provider'] ?? null;
    }

    public function getModel(): ?string
    {
        return $this->metadata['model'] ?? null;
    }

    public function getProcessingTime(): ?float
    {
        return $this->metadata['processing_time'] ?? null;
    }

    protected function getDefaultMetadata(): array
    {
        return array_merge(parent::getDefaultMetadata(), [
            'size' => null,
            'quality' => null,
            'style' => null,
            'format' => 'png',
            'count' => 1,
        ]);
    }

    private function formatImageData(mixed $imageData): mixed
    {
        if (is_string($imageData)) {
            return ['url' => $imageData];
        }

        if (is_array($imageData)) {
            // If it's an array of URLs
            if (isset($imageData[0]) && is_string($imageData[0])) {
                return ['urls' => $imageData];
            }

            // If it's already properly formatted
            return $imageData;
        }

        return $imageData;
    }

    public static function fromUrl(
        string $url,
        ?string $provider = null,
        ?string $model = null,
        array $additionalMetadata = []
    ): static {
        $metadata = array_merge([
            'provider' => $provider,
            'model' => $model,
            'count' => 1,
        ], $additionalMetadata);

        return new static(true, $url, $metadata);
    }

    public static function fromUrls(
        array $urls,
        ?string $provider = null,
        ?string $model = null,
        array $additionalMetadata = []
    ): static {
        $metadata = array_merge([
            'provider' => $provider,
            'model' => $model,
            'count' => count($urls),
        ], $additionalMetadata);

        return new static(true, $urls, $metadata);
    }

    public static function fromError(
        string $error,
        ?string $provider = null,
        ?string $model = null,
        array $additionalMetadata = []
    ): static {
        $metadata = array_merge([
            'provider' => $provider,
            'model' => $model,
        ], $additionalMetadata);

        return new static(false, null, $metadata, $error);
    }

    /**
     * For backward compatibility - returns just the first URL
     */
    public function __toString(): string
    {
        return $this->getUrl() ?? '';
    }
}
