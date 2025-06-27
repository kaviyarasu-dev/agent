<?php

declare(strict_types=1);

/**
 * BlogAiAgent Usage Examples
 * 
 * This file demonstrates various ways to use the BlogAiAgent
 * for generating blog posts with AI.
 */

use App\Agents\Blog\BlogAiAgent;

// Example 1: Basic Usage with Dependency Injection
// -------------------------------------------------
// In a controller or service class:
class BlogService
{
    private BlogAiAgent $blogAgent;
    
    public function __construct(BlogAiAgent $blogAgent)
    {
        $this->blogAgent = $blogAgent;
    }
    
    public function generatePost(string $topic): string
    {
        return $this->blogAgent->execute([
            'prompt' => "Write a blog post about {$topic}",
            'options' => [
                'tone' => 'professional',
                'length' => 'medium',
            ]
        ]);
    }
}

// Example 2: Using app() Helper (as shown in original request)
// ------------------------------------------------------------
$content = app(BlogAiAgent::class)->execute([
    'prompt' => 'Write a blog post about Laravel best practices',
    'options' => [
        'tone' => 'professional',
        'length' => 'medium',
    ]
]);

// Example 3: Different Tones
// --------------------------
// Professional tone
$professionalPost = app(BlogAiAgent::class)->execute([
    'prompt' => 'Write a blog post about AI in healthcare',
    'options' => [
        'tone' => 'professional',
        'length' => 'long',
    ]
]);

// Casual tone
$casualPost = app(BlogAiAgent::class)->execute([
    'prompt' => 'Write a blog post about weekend coding projects',
    'options' => [
        'tone' => 'casual',
        'length' => 'short',
    ]
]);

// Friendly tone
$friendlyPost = app(BlogAiAgent::class)->execute([
    'prompt' => 'Write a blog post about getting started with PHP',
    'options' => [
        'tone' => 'friendly',
        'length' => 'medium',
    ]
]);

// Example 4: Different Lengths
// ----------------------------
// Short post (300-500 words)
$shortPost = app(BlogAiAgent::class)->execute([
    'prompt' => 'Write a blog post about PHP 8.3 features',
    'options' => [
        'tone' => 'professional',
        'length' => 'short',
    ]
]);

// Medium post (800-1200 words)
$mediumPost = app(BlogAiAgent::class)->execute([
    'prompt' => 'Write a blog post about microservices architecture',
    'options' => [
        'tone' => 'professional',
        'length' => 'medium',
    ]
]);

// Long post (1500-2000 words)
$longPost = app(BlogAiAgent::class)->execute([
    'prompt' => 'Write a blog post about building scalable web applications',
    'options' => [
        'tone' => 'professional',
        'length' => 'long',
    ]
]);

// Example 5: In a Laravel Controller
// ----------------------------------
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BlogGeneratorController
{
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string',
            'tone' => 'string|in:professional,casual,friendly,formal,humorous',
            'length' => 'string|in:short,medium,long',
        ]);
        
        try {
            $content = app(BlogAiAgent::class)->execute([
                'prompt' => 'Write a blog post about ' . $validated['topic'],
                'options' => [
                    'tone' => $validated['tone'] ?? 'professional',
                    'length' => $validated['length'] ?? 'medium',
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

// Example 6: In a Laravel Job
// ---------------------------
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBlogPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        private string $topic,
        private string $tone = 'professional',
        private string $length = 'medium'
    ) {}
    
    public function handle(BlogAiAgent $blogAgent): void
    {
        $content = $blogAgent->execute([
            'prompt' => "Write a blog post about {$this->topic}",
            'options' => [
                'tone' => $this->tone,
                'length' => $this->length,
            ]
        ]);
        
        // Save to database, send email, etc.
        \Log::info('Blog post generated', [
            'topic' => $this->topic,
            'length' => strlen($content),
        ]);
    }
}

// Example 7: Error Handling
// ------------------------
try {
    $content = app(BlogAiAgent::class)->execute([
        'prompt' => 'Write a blog post about quantum computing',
        'options' => [
            'tone' => 'professional',
            'length' => 'medium',
        ]
    ]);
    
    // Process the content
    echo $content;
    
} catch (\InvalidArgumentException $e) {
    // Handle validation errors
    echo "Invalid input: " . $e->getMessage();
} catch (\RuntimeException $e) {
    // Handle AI service errors
    echo "AI service error: " . $e->getMessage();
} catch (\Exception $e) {
    // Handle other errors
    echo "Unexpected error: " . $e->getMessage();
}

// Example 8: Using with Minimal Options
// -------------------------------------
// The agent will use defaults (professional tone, medium length)
$simplePost = app(BlogAiAgent::class)->execute([
    'prompt' => 'Write a blog post about web security',
]);

// Example 9: Batch Processing
// ---------------------------
$topics = [
    'Laravel Tips and Tricks',
    'Database Optimization',
    'API Design Best Practices',
    'Testing Strategies',
];

$blogPosts = [];
foreach ($topics as $topic) {
    $blogPosts[$topic] = app(BlogAiAgent::class)->execute([
        'prompt' => "Write a blog post about {$topic}",
        'options' => [
            'tone' => 'professional',
            'length' => 'medium',
        ]
    ]);
}

// Example 10: Custom Service Provider Registration
// -----------------------------------------------
// In a service provider:
use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register BlogAiAgent as a singleton
        $this->app->singleton(BlogAiAgent::class, function ($app) {
            return new BlogAiAgent(
                $app->make(\WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface::class)
            );
        });
    }
}