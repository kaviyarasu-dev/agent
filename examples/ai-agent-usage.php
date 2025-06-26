<?php

/**
 * AI Agent Command Examples
 *
 * This file demonstrates various ways to use the ai-agent command
 * to scaffold AI agents in your Laravel application.
 */

// Example 1: Basic text agent
// php artisan ai-agent Blog/BlogAiAgent

// Example 2: Image processing agent with specific provider
// php artisan ai-agent ImageProcessor --capability=image --provider=ideogram

// Example 3: Video agent with model specification
// php artisan ai-agent VideoCreator --capability=video --provider=openai --model=gpt-4-vision

// Example 4: Agent with logging trait
// php artisan ai-agent content/ArticleGenerator --with-logging

// Example 5: Agent with fallback support
// php artisan ai-agent critical/PaymentDescriber --with-fallback

// Example 6: Full-featured production agent
// php artisan ai-agent production/ContentModerator --provider=claude --model=claude-3-sonnet-20241022 --with-logging --with-fallback

// Example 7: Using forward slash notation for nested directories
// php artisan ai-agent blog/post/generator/PostGenerator

// Example 8: Force overwrite existing file
// php artisan ai-agent ExistingAgent --force

// Example 9: Interactive mode
// php artisan ai-agent

/**
 * Generated Agent Usage Example
 */

use App\Agents\Blog\BlogAiAgent;
use WebsiteLearners\AIAgent\Services\Core\TextService;

class BlogController extends Controller
{
    public function generatePost(Request $request)
    {
        // The agent is automatically resolved with dependency injection
        $blogAgent = app(BlogAiAgent::class);

        // Or manually instantiate
        $textService = app(TextService::class);
        $blogAgent = new BlogAiAgent($textService);

        // Execute the agent
        $result = $blogAgent->execute([
            'prompt' => 'Write a blog post about ' . $request->input('topic'),
            'tone' => $request->input('tone', 'professional'),
            'length' => $request->input('length', 'medium'),
        ]);

        return response()->json([
            'success' => true,
            'content' => $result,
        ]);
    }
}

/**
 * Agent with Traits Example
 */

namespace App\Agents;

use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use App\AI\Traits\LogsAIUsage;
use App\AI\Traits\UsesFallbackProvider;

class ProductionAgent
{
    use LogsAIUsage, UsesFallbackProvider;

    protected TextServiceInterface $textService;

    public function __construct(TextServiceInterface $textService)
    {
        $this->textService = $textService;

        // Set fallback providers
        $this->setFallbackProviders(['openai', 'anthropic']);
    }

    public function execute(array $data)
    {
        // Execute with both logging and fallback support
        return $this->executeWithLogging(
            fn() => $this->executeWithFallback(
                fn() => $this->textService->generateText($data['prompt']),
                'textService'
            ),
            'textService',
            'generateText',
            $data
        );
    }
}

/**
 * Custom Agent Implementation Example
 */

namespace App\Agents\Blog;

use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;

class BlogSummaryAgent
{
    protected TextServiceInterface $textService;

    public function __construct(TextServiceInterface $textService)
    {
        $this->textService = $textService;
        $this->textService->setProvider('claude');
        $this->textService->switchModel('claude-3-haiku-20240307');
    }

    public function summarize(string $content, int $maxWords = 150): string
    {
        $prompt = "Summarize the following content in no more than {$maxWords} words:\n\n{$content}";

        $response = $this->textService->generateText($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 500,
        ]);

        return $response['content'] ?? '';
    }

    public function extractKeyPoints(string $content): array
    {
        $prompt = "Extract 5 key points from the following content as a JSON array:\n\n{$content}";

        $response = $this->textService->generateText($prompt, [
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
        ]);

        return json_decode($response['content'] ?? '[]', true);
    }
}
