<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Examples;

use Kaviyarasu\AIAgent\Agents\BaseAIAgent;

class AdaptiveCodeAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text'];

    protected array $taskProviderMap = [
        'simple' => 'openai',
        'complex' => 'claude',
        'creative' => 'claude',
        'documentation' => 'openai',
        'debugging' => 'claude',
    ];

    public function __construct()
    {
        parent::__construct(app(\Kaviyarasu\AIAgent\Factory\ServiceFactory::class));
    }

    public function execute(array $data): string
    {
        $task = $data['task'] ?? '';
        $language = $data['language'] ?? 'javascript';
        $complexity = $data['complexity'] ?? $this->determineComplexity($task);

        // Select appropriate provider based on task complexity
        $provider = $this->selectProviderForTask($complexity);
        $this->switchProvider($provider);

        $prompt = $this->buildCodePrompt($task, $language, $complexity);

        try {
            return $this->textService->generateText($prompt, [
                'max_tokens' => $complexity === 'complex' ? 2000 : 1000,
                'temperature' => 0.3,
            ]);
        } catch (\Exception $e) {
            // Fallback to alternate provider
            $fallbackProvider = $provider === 'claude' ? 'openai' : 'claude';
            $this->switchProvider($fallbackProvider);

            return $this->textService->generateText($prompt, [
                'max_tokens' => 1500,
                'temperature' => 0.3,
            ]);
        }
    }

    protected function determineComplexity(string $task): string
    {
        $complexKeywords = ['algorithm', 'neural network', 'complex', 'advanced', 'optimize', 'architecture'];
        $simpleKeywords = ['function', 'simple', 'basic', 'create', 'write'];

        $taskLower = strtolower($task);

        foreach ($complexKeywords as $keyword) {
            if (str_contains($taskLower, $keyword)) {
                return 'complex';
            }
        }

        foreach ($simpleKeywords as $keyword) {
            if (str_contains($taskLower, $keyword)) {
                return 'simple';
            }
        }

        return 'medium';
    }

    protected function selectProviderForTask(string $complexity): string
    {
        return match ($complexity) {
            'simple' => 'openai',
            'complex' => 'claude',
            default => 'openai',
        };
    }

    protected function buildCodePrompt(string $task, string $language, string $complexity): string
    {
        $requirements = match ($complexity) {
            'simple' => 'Keep the code simple and straightforward.',
            'complex' => 'Provide a comprehensive, well-architected solution with error handling and edge cases.',
            default => 'Provide a clean, efficient solution.',
        };

        return <<<PROMPT
Task: {$task}
Programming Language: {$language}

Requirements:
- {$requirements}
- Include appropriate comments
- Follow best practices for {$language}
- Ensure the code is production-ready

Please provide the complete implementation:
PROMPT;
    }
}
