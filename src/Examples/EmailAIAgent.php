<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Examples;

use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;
use Kaviyarasu\AIAgent\Traits\HasDynamicProvider;

class EmailAIAgent
{
    use HasDynamicProvider;

    protected TextServiceInterface $textService;
    public function __construct(TextServiceInterface $textService = null)
    {
        $this->textService = $textService ?: app(TextServiceInterface::class);
        $this->currentProvider = 'openai';
    }

    public function execute(array $data): string
    {
        $recipient = $data['recipient'] ?? 'User';
        $subject = $data['subject'] ?? 'No Subject';
        $purpose = $data['purpose'] ?? 'general communication';

        $prompt = $this->buildEmailPrompt($recipient, $subject, $purpose);

        $response = $this->textService->generateText($prompt, [
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        // Parse JSON response if needed
        if ($this->isJsonResponse($response)) {
            $parsed = json_decode($response, true);
            return $parsed['body'] ?? $response;
        }

        return $response;
    }

    public function withTemporaryProvider(string $provider, callable $callback)
    {
        $original = $this->currentProvider;
        try {
            $this->useProvider($provider);
            return $callback($this);
        } finally {
            $this->useProvider($original);
        }
    }

    protected function buildEmailPrompt(string $recipient, string $subject, string $purpose): string
    {
        return <<<PROMPT
Write a professional email with the following details:
- Recipient: {$recipient}
- Subject: {$subject}
- Purpose: {$purpose}

Please write a complete, well-structured email that is professional, clear, and appropriate for the context.
Return the email content in JSON format: {"subject_line": "...", "body": "..."}
PROMPT;
    }

    protected function isJsonResponse(string $response): bool
    {
        json_decode($response);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
