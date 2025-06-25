<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Tests\Commands;

use Illuminate\Support\Facades\File;
use WebsiteLearners\AIAgent\Tests\TestCase;

class MakeAiAgentCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing test files
        File::deleteDirectory(app_path('AI'));
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        File::deleteDirectory(app_path('AI'));

        parent::tearDown();
    }

    /** @test */
    public function it_can_create_a_basic_ai_agent()
    {
        $this->artisan('ai-agent Blog/BlogAiAgent')
            ->assertSuccessful();

        $this->assertFileExists(app_path('AI/Agents/Blog/BlogAiAgent.php'));

        $content = File::get(app_path('AI/Agents/Blog/BlogAiAgent.php'));
        $this->assertStringContainsString('class BlogAiAgent', $content);
        $this->assertStringContainsString('TextServiceInterface', $content);
        $this->assertStringContainsString('Capability: text', $content);
    }

    /** @test */
    public function it_can_create_an_ai_agent_with_image_capability()
    {
        $this->artisan('ai-agent ImageProcessor --capability=image')
            ->assertSuccessful();

        $content = File::get(app_path('AI/Agents/ImageProcessor.php'));
        $this->assertStringContainsString('ImageServiceInterface', $content);
        $this->assertStringContainsString('Capability: image', $content);
    }

    /** @test */
    public function it_can_create_an_ai_agent_with_video_capability()
    {
        $this->artisan('ai-agent VideoGenerator --capability=video')
            ->assertSuccessful();

        $content = File::get(app_path('AI/Agents/VideoGenerator.php'));
        $this->assertStringContainsString('VideoServiceInterface', $content);
        $this->assertStringContainsString('Capability: video', $content);
    }

    /** @test */
    public function it_validates_invalid_capability()
    {
        $this->artisan('ai-agent TestAgent --capability=invalid')
            ->assertFailed()
            ->expectsOutput("Invalid capability 'invalid'. Valid options are: text, image, video");
    }

    /** @test */
    public function it_validates_invalid_provider()
    {
        $this->artisan('ai-agent TestAgent --provider=invalid-provider')
            ->assertFailed();

        // File should not be created when validation fails
        $this->assertFileDoesNotExist(app_path('AI/Agents/TestAgent.php'));
    }

    /** @test */
    public function it_provides_suggestions_for_similar_provider_names()
    {
        $this->artisan('ai-agent TestAgent --provider=claudee')
            ->assertFailed()
            ->expectsOutput('Did you mean: claude?');
    }

    /** @test */
    public function it_can_set_provider_and_model()
    {
        $this->artisan('ai-agent ContentAgent --provider=claude --model=claude-3-sonnet-20241022')
            ->assertSuccessful();

        $content = File::get(app_path('AI/Agents/ContentAgent.php'));
        $this->assertStringContainsString('Default Provider: claude', $content);
        $this->assertStringContainsString("\$this->textService->setProvider('claude');", $content);
        $this->assertStringContainsString("\$this->textService->switchModel('claude-3-sonnet-20241022');", $content);
    }

    /** @test */
    public function it_handles_forward_slash_notation()
    {
        $this->artisan('ai-agent blog/post/BlogPostAgent')
            ->assertSuccessful();

        $this->assertFileExists(app_path('AI/Agents/Blog/Post/BlogPostAgent.php'));

        $content = File::get(app_path('AI/Agents/Blog/Post/BlogPostAgent.php'));
        $this->assertStringContainsString('namespace App\AI\Agents\Blog\Post;', $content);
        $this->assertStringContainsString('class BlogPostAgent', $content);
    }

    /** @test */
    public function it_includes_logging_trait_when_requested()
    {
        $this->artisan('ai-agent LoggedAgent --with-logging')
            ->assertSuccessful();

        $content = File::get(app_path('AI/Agents/LoggedAgent.php'));
        $this->assertStringContainsString('use App\AI\Traits\LogsAIUsage;', $content);
        $this->assertStringContainsString('use LogsAIUsage;', $content);

        $this->assertFileExists(app_path('AI/Traits/LogsAIUsage.php'));
    }

    /** @test */
    public function it_includes_fallback_trait_when_requested()
    {
        $this->artisan('ai-agent FallbackAgent --with-fallback')
            ->assertSuccessful();

        $content = File::get(app_path('AI/Agents/FallbackAgent.php'));
        $this->assertStringContainsString('use App\AI\Traits\UsesFallbackProvider;', $content);
        $this->assertStringContainsString('use UsesFallbackProvider;', $content);

        $this->assertFileExists(app_path('AI/Traits/UsesFallbackProvider.php'));
    }

    /** @test */
    public function it_prevents_overwriting_without_force_flag()
    {
        $this->artisan('ai-agent ExistingAgent')->assertSuccessful();

        $this->artisan('ai-agent ExistingAgent')
            ->assertFailed()
            ->expectsOutput('already exists');
    }

    /** @test */
    public function it_overwrites_with_force_flag()
    {
        $this->artisan('ai-agent ExistingAgent')->assertSuccessful();

        File::put(app_path('AI/Agents/ExistingAgent.php'), '<?php // Original content');

        $this->artisan('ai-agent ExistingAgent --force')->assertSuccessful();

        $content = File::get(app_path('AI/Agents/ExistingAgent.php'));
        $this->assertStringNotContainsString('// Original content', $content);
    }

    /** @test */
    public function it_creates_multiple_traits_together()
    {
        $this->artisan('ai-agent CompleteAgent --with-logging --with-fallback')
            ->assertSuccessful();

        $content = File::get(app_path('AI/Agents/CompleteAgent.php'));
        $this->assertStringContainsString('use LogsAIUsage, UsesFallbackProvider;', $content);
    }
}
