<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Tests\Feature;

use App\Agents\Blog\BlogAiAgent;
use App\Agents\Blog\BlogAiAgentWithImages;
use Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface;
use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;
use Kaviyarasu\AIAgent\Tests\TestCase;

class BlogAiAgentTest extends TestCase
{
    /**
     * Test basic blog generation functionality
     */
    public function test_blog_agent_can_generate_content(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);

        $textService->expects($this->once())
            ->method('setProvider')
            ->with('claude');
        $textService->expects($this->once())
            ->method('generateText')
            ->willReturn('This is a generated blog post about Laravel.');

        $agent = new BlogAiAgent($textService);

        $result = $agent->execute([
            'prompt' => 'Laravel Best Practices',
            'options' => [
                'tone' => 'professional',
                'length' => 'medium',
            ],
        ]);

        $this->assertIsString($result);
        $this->assertStringContainsString('Laravel', $result);
    }

    /**
     * Test blog generation with different tones
     */
    public function test_blog_agent_handles_different_tones(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);

        $textService->expects($this->once())
            ->method('setProvider')
            ->with('claude');

        $textService->expects($this->exactly(3))
            ->method('generateText')
            ->willReturnCallback(function ($prompt, $options) {
                if (str_contains($prompt, 'casual')) {
                    return 'Hey there! Let\'s talk about PHP...';
                } elseif (str_contains($prompt, 'technical')) {
                    return 'In this comprehensive guide, we explore PHP...';
                } else {
                    return 'PHP is a popular programming language...';
                }
            });

        $agent = new BlogAiAgent($textService);

        $casualResult = $agent->execute([
            'prompt' => 'PHP Development',
            'options' => [
                'tone' => 'casual',
                'length' => 'medium',
            ],
        ]);
        $this->assertStringContainsString('Hey there', $casualResult);

        $technicalResult = $agent->execute([
            'prompt' => 'PHP Development',
            'options' => [
                'tone' => 'technical',
                'length' => 'medium',
            ],
        ]);
        $this->assertStringContainsString('comprehensive', $technicalResult);

        $professionalResult = $agent->execute([
            'prompt' => 'PHP Development',
            'options' => [
                'tone' => 'professional',
                'length' => 'medium',
            ],
        ]);
        $this->assertStringContainsString('popular programming language', $professionalResult);
    }

    /**
     * Test blog agent with image generation
     */
    public function test_blog_agent_with_images_generates_content(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);
        $imageService = $this->createMock(ImageServiceInterface::class);

        $textService->expects($this->once())
            ->method('setProvider')
            ->with('claude');

        $textService->expects($this->once())
            ->method('generateText')
            ->willReturn('# AI Revolution\n\nArtificial Intelligence is transforming...');

        $imageService->expects($this->once())
            ->method('generateImage')
            ->willReturn('https://example.com/ai-image.jpg');

        $agent = new BlogAiAgentWithImages($textService, $imageService);

        $result = $agent->execute([
            'prompt' => 'Artificial Intelligence',
            'options' => [
                'tone' => 'informative',
            ],
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('featured_image', $result);
        $this->assertStringContainsString('AI Revolution', $result['content']);
        $this->assertEquals('https://example.com/ai-image.jpg', $result['featured_image']);
    }

    /**
     * Test blog agent handles missing parameters gracefully
     */
    public function test_blog_agent_handles_missing_parameters(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);

        $textService->expects($this->once())
            ->method('setProvider')
            ->with('claude');

        $textService->expects($this->once())
            ->method('generateText')
            ->willReturn('Generated content about general topics.');

        $agent = new BlogAiAgent($textService);
        $result = $agent->execute([
            'prompt' => 'general topics',
            'options' => [],
        ]);

        $this->assertIsString($result);
        $this->assertStringContainsString('general topics', $result);
    }

    /**
     * Test integration with real services (if available).
     * This test is skipped if API keys are not configured.
     */
    public function test_blog_agent_real_integration(): void
    {
        if (! config('ai-agent.providers.openai.api_key') || config('ai-agent.providers.openai.api_key') === 'your-openai-api-key') {
            $this->markTestSkipped('OpenAI API key not configured');
        }

        $agent = app(BlogAiAgent::class);

        $result = $agent->execute([
            'prompt' => 'Laravel Testing',
            'options' => [
                'tone' => 'technical',
                'length' => 'short',
            ],
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertGreaterThan(100, strlen($result));
    }
}
