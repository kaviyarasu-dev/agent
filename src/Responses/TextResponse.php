<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Responses;

class TextResponse extends AIResponse
{
    public function __construct(
        bool $success = true,
        ?string $text = null,
        array $metadata = [],
        ?string $error = null
    ) {
        $data = $success ? ['text' => $text] : null;
        parent::__construct($success, $data, $metadata, $error);
    }

    public function getText(): ?string
    {
        return $this->data['text'] ?? null;
    }

    public function getTokensUsed(): ?int
    {
        return $this->metadata['tokens_used'] ?? null;
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
            'tokens_used' => 0,
            'max_tokens' => null,
            'temperature' => null,
            'stream' => false,
        ]);
    }

    public static function fromText(
        string $text,
        ?string $provider = null,
        ?string $model = null,
        array $additionalMetadata = []
    ): static {
        $metadata = array_merge([
            'provider' => $provider,
            'model' => $model,
        ], $additionalMetadata);

        return new static(true, $text, $metadata);
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
     * For backward compatibility - returns just the text
     */
    public function __toString(): string
    {
        return $this->getText() ?? '';
    }
}
