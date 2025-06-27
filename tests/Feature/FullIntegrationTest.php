<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Tests\Feature;

use WebsiteLearners\AIAgent\Tests\TestCase;
use WebsiteLearners\AIAgent\Examples\ContentCreatorAgent;
use WebsiteLearners\AIAgent\Examples\EmailAIAgent;
use WebsiteLearners\AIAgent\Examples\AdaptiveCodeAgent;
use WebsiteLearners\AIAgent\Services\AI\TextService;
use WebsiteLearners\AIAgent\Services\AI\ImageService;
use WebsiteLearners\AIAgent\Factory\ProviderFactory;
use WebsiteLearners\AIAgent\Providers\OpenAI\OpenAIProvider;
use WebsiteLearners\AIAgent\Providers\Claude\ClaudeProvider;
use WebsiteLearners\AIAgent\Providers\Ideogram\IdeogramProvider;

/**
 * Full integration test to verify all components work together
 * This test uses mocked HTTP responses but real service implementations
 */
class FullIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai-agent.providers.openai.api_key' => 'test-openai-key',
            'ai-agent.providers.claude.api_key' => 'test-claude-key',
            'ai-agent.providers.ideogram.api_key' => 'test-ideogram-key',
            'ai-agent.features.provider_switching' => true,
            'ai-agent.features.model_switching' => true,
            'ai-agent.model_selection.strategy' => 'balanced',
        ]);

        $this->mockHttpResponses();
    }

    public function test_content_creator_agent_full_workflow(): void
    {
        $agent = new ContentCreatorAgent();

        $this->assertEquals('claude', $agent->getCurrentProvider());

        $result = $agent->execute([
            'topic' => 'The Future of AI',
            'style' => 'informative',
            'include_images' => true,
        ]);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('images', $result);
        $this->assertArrayHasKey('metadata', $result);

        $this->assertStringContainsString('AI', $result['content']);
        $this->assertNotEmpty($result['images']);

        $this->assertEquals('claude', $result['metadata']['text_provider']);
        $this->assertEquals('ideogram', $result['metadata']['image_provider']);
        $this->assertArrayHasKey('processing_time', $result['metadata']);
    }

    public function test_email_agent_with_provider_switching(): void
    {
        $agent = new EmailAIAgent();

        $result1 = $agent->execute([
            'recipient' => 'Alice',
            'subject' => 'Project Update',
            'tone' => 'professional',
            'key_points' => ['Progress report', 'Next steps'],
        ]);

        $this->assertStringContainsString('Alice', $result1);
        $this->assertStringContainsString('Project Update', $result1);

        $agent->useProvider('claude');

        $result2 = $agent->execute([
            'recipient' => 'Bob',
            'subject' => 'Meeting Invitation',
            'tone' => 'friendly',
            'key_points' => ['Team meeting', 'Friday 3pm'],
        ]);

        $this->assertStringContainsString('Bob', $result2);
        $this->assertStringContainsString('Meeting', $result2);
        $this->assertEquals('claude', $agent->getCurrentProvider());
    }

    public function test_adaptive_code_agent_automatic_selection(): void
    {
        $agent = new AdaptiveCodeAgent();

        $result1 = $agent->execute([
            'task' => 'Write a function to reverse a string',
            'language' => 'python',
            'complexity' => 'simple',
        ]);

        $this->assertArrayHasKey('code', $result1);
        $this->assertArrayHasKey('provider_used', $result1);
        $this->assertEquals('openai', $result1['provider_used']);
        $this->assertEquals('gpt-3.5-turbo', $result1['model_used']);
        $this->assertStringContainsString('def', $result1['code']);

        $result2 = $agent->execute([
            'task' => 'Implement a distributed cache with consistency guarantees',
            'language' => 'java',
            'complexity' => 'complex',
        ]);

        $this->assertEquals('claude', $result2['provider_used']);
        $this->assertEquals('claude-3-opus-20240229', $result2['model_used']);
        $this->assertStringContainsString('class', $result2['code']);
    }

    public function test_temporary_switching(): void
    {
        $agent = new EmailAIAgent();

        $this->assertEquals('openai', $agent->getCurrentProvider());

        $result = $agent->withTemporaryProvider('claude', function ($agent) {
            $this->assertEquals('claude', $agent->getCurrentProvider());

            return $agent->execute([
                'recipient' => 'Charlie',
                'subject' => 'Test',
                'tone' => 'casual',
                'key_points' => ['Testing temporary provider'],
            ]);
        });

        $this->assertStringContainsString('Charlie', $result);

        $this->assertEquals('openai', $agent->getCurrentProvider());
    }

    public function test_model_capabilities(): void
    {
        $agent = new ContentCreatorAgent();

        $capabilities = $agent->getModelCapabilities('gpt-4');

        $this->assertIsArray($capabilities);
        $this->assertArrayHasKey('max_tokens', $capabilities);
        $this->assertArrayHasKey('supports_functions', $capabilities);
        $this->assertArrayHasKey('supports_vision', $capabilities);
        $this->assertArrayHasKey('context_window', $capabilities);

        $this->assertEquals(8192, $capabilities['max_tokens']);
        $this->assertTrue($capabilities['supports_functions']);
    }

    public function test_fallback_providers(): void
    {
        $this->mockFailingProvider('claude');

        $agent = new AdaptiveCodeAgent();

        $result = $agent->execute([
            'task' => 'Complex task that should use Claude',
            'language' => 'python',
            'complexity' => 'complex',
        ]);

        $this->assertEquals('openai', $result['provider_used']);
        $this->assertEquals('gpt-4', $result['model_used']);
        $this->assertArrayHasKey('fallback_used', $result);
        $this->assertTrue($result['fallback_used']);
    }

    public function test_multi_service_different_providers(): void
    {
        $agent = new ContentCreatorAgent();

        $result = $agent->execute([
            'topic' => 'Space Exploration',
            'style' => 'educational',
            'include_images' => true,
        ]);

        $this->assertEquals('claude', $result['metadata']['text_provider']);
        $this->assertEquals('ideogram', $result['metadata']['image_provider']);

        $this->assertNotEmpty($result['content']);
        $this->assertNotEmpty($result['images']);
    }

    public function test_configuration_stacking(): void
    {
        $agent = new EmailAIAgent();

        $agent->useProvider('openai', ['temperature' => 0.7]);

        $agent->useProvider('openai', ['max_tokens' => 500]);

        $result = $agent->execute([
            'recipient' => 'David',
            'subject' => 'Config Test',
            'tone' => 'formal',
            'key_points' => ['Testing configuration'],
        ]);
    }

    protected function mockHttpResponses(): void
    {
        $this->app->bind(OpenAIProvider::class, function () {
            $provider = \Mockery::mock(OpenAIProvider::class)->makePartial();

            $provider->shouldReceive('generateText')
                ->andReturnUsing(function ($params) {
                    $prompt = $params['prompt'] ?? '';
                    $recipient = $params['recipient'] ?? 'User';
                    $subject = $params['subject'] ?? 'your inquiry';
                    $topic = $params['topic'] ?? 'the subject';

                    if (str_contains($prompt, 'email')) {
                        return "Dear {$recipient},\n\nRegarding {$subject}...";
                    }
                    if (str_contains($prompt, 'reverse a string')) {
                        return "def reverse_string(s):\n    return s[::-1]";
                    }
                    return "Generated text content about {$topic}";
                });

            $provider->shouldReceive('switchModel')->andReturn(true);
            $provider->shouldReceive('getCurrentModel')->andReturn('gpt-3.5-turbo');
            $provider->shouldReceive('getModelCapabilities')->andReturn([
                'max_tokens' => 8192,
                'supports_functions' => true,
                'supports_vision' => true,
                'context_window' => 128000,
            ]);

            return $provider;
        });

        $this->app->bind(ClaudeProvider::class, function () {
            $provider = \Mockery::mock(ClaudeProvider::class)->makePartial();

            $provider->shouldReceive('generateText')
                ->andReturnUsing(function ($params) {
                    $prompt = $params['prompt'] ?? '';
                    $recipient = $params['recipient'] ?? 'there';
                    $topic = $params['topic'] ?? 'AI';

                    if (str_contains($prompt, 'email')) {
                        return "Hello {$recipient},\n\nI hope this email finds you well...";
                    }
                    if (str_contains($prompt, 'distributed cache')) {
                        return "public class DistributedCache {\n    // Complex implementation\n}";
                    }
                    return "Comprehensive article about {$topic} written in an informative style.";
                });

            $provider->shouldReceive('switchModel')->andReturn(true);
            $provider->shouldReceive('getCurrentModel')->andReturn('claude-3-opus-20240229');

            return $provider;
        });

        $this->app->bind(IdeogramProvider::class, function () {
            $provider = \Mockery::mock(IdeogramProvider::class)->makePartial();

            $provider->shouldReceive('generateImage')
                ->andReturn('https://example.com/generated-image.jpg');

            $provider->shouldReceive('generateImages')
                ->andReturn(['https://example.com/image1.jpg', 'https://example.com/image2.jpg']);

            return $provider;
        });
    }

    protected function mockFailingProvider(string $providerName): void
    {
        if ($providerName === 'claude') {
            $this->app->bind(ClaudeProvider::class, function () {
                $provider = \Mockery::mock(ClaudeProvider::class)->makePartial();

                $provider->shouldReceive('generateText')
                    ->andThrow(new \Exception('Claude API error'));

                $provider->shouldReceive('switchModel')->andReturn(true);

                return $provider;
            });
        }
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
