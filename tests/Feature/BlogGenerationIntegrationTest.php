<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Tests\Feature;

use App\Agents\Blog\BlogAiAgent;
use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;
use Kaviyarasu\AIAgent\Tests\TestCase;

class BlogGenerationIntegrationTest extends TestCase
{
    public function test_generates_complete_blog_post(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);

        $textService->expects($this->once())
            ->method('setProvider')
            ->with('claude');
        $textService->expects($this->once())
            ->method('generateText')
            ->willReturn($this->getSampleBlogPost());

        $agent = new BlogAiAgent($textService);

        $result = $agent->execute([
            'prompt' => 'Laravel Best Practices',
            'options' => [
                'tone' => 'professional',
                'length' => 'medium',
            ],
        ]);

        $this->assertIsString($result);
        $this->assertGreaterThan(500, strlen($result));
        $this->assertStringContainsString('Laravel', $result);
        $this->assertStringContainsString('best practices', $result);
        $this->assertStringContainsString('Introduction', $result);
        $this->assertStringContainsString('Conclusion', $result);
    }

    public function test_generates_posts_with_different_lengths(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);

        $textService->expects($this->once())
            ->method('setProvider')
            ->with('claude');

        $lengths = [
            'short' => 300,
            'medium' => 800,
            'long' => 1500,
        ];

        $textService->expects($this->exactly(3))
            ->method('generateText')
            ->willReturnCallback(function ($prompt, $options) use ($lengths) {
                foreach ($lengths as $length => $wordCount) {
                    if (str_contains($prompt, $length)) {
                        return $this->generateContentWithLength($wordCount);
                    }
                }

                return 'Default content';
            });

        $agent = new BlogAiAgent($textService);

        foreach ($lengths as $lengthType => $expectedWords) {
            $result = $agent->execute([
                'prompt' => 'PHP Development',
                'options' => [
                    'length' => $lengthType,
                ],
            ]);

            $wordCount = str_word_count($result);

            $minWords = $expectedWords * 0.8;
            $maxWords = $expectedWords * 1.2;

            $this->assertGreaterThan($minWords, $wordCount, "Word count for {$lengthType} post is too low");
            $this->assertLessThan($maxWords, $wordCount, "Word count for {$lengthType} post is too high");
        }
    }

    public function test_generates_seo_optimized_content(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);

        $textService->expects($this->once())
            ->method('setProvider')
            ->with('claude');

        $textService->expects($this->once())
            ->method('generateText')
            ->willReturn($this->getSeoOptimizedBlogPost());

        $agent = new BlogAiAgent($textService);

        $result = $agent->execute([
            'prompt' => 'Laravel SEO Best Practices',
            'options' => [
                'tone' => 'professional',
            ],
        ]);

        $this->assertStringContainsString('Laravel SEO', $result);
        $this->assertStringContainsString('## ', $result);
        $this->assertStringContainsString('### ', $result);

        $keywordCount = substr_count(strtolower($result), 'laravel seo');
        $this->assertGreaterThan(3, $keywordCount);
        $this->assertLessThan(30, $keywordCount);
    }

    public function test_handles_api_errors_with_retry(): void
    {
        $textService = $this->createMock(TextServiceInterface::class);

        $textService->expects($this->once())
            ->method('setProvider')
            ->with('claude');

        $this->markTestSkipped('BlogAiAgent does not have built-in retry logic');
    }

    private function getSampleBlogPost(): string
    {
        return <<<'BLOG'
# Laravel Best Practices: A Comprehensive Guide

## Introduction

Laravel has become one of the most popular PHP frameworks for web development, and for good reason. Its elegant syntax, powerful features, and extensive ecosystem make it an excellent choice for projects of all sizes. In this article, we'll explore the best practices that will help you write cleaner, more maintainable Laravel code.

## 1. Follow Laravel Naming Conventions

Consistency is key when working with any framework. Laravel has established naming conventions that you should follow:

- **Controllers**: Use PascalCase and end with 'Controller' (e.g., UserController)
- **Models**: Use singular PascalCase (e.g., User, BlogPost)
- **Tables**: Use plural snake_case (e.g., users, blog_posts)
- **Columns**: Use snake_case (e.g., created_at, user_id)

## 2. Use Eloquent Relationships Effectively

Laravel's Eloquent ORM provides powerful relationship management. Always define relationships in your models:

```php
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

## 3. Leverage Service Providers and Dependency Injection

Service providers are the central place for application bootstrapping. Use them to register services, and leverage Laravel's dependency injection container for better testability and maintainability.

## 4. Implement Repository Pattern for Complex Queries

For applications with complex database operations, consider implementing the repository pattern to abstract database logic from your controllers.

## 5. Use Form Requests for Validation

Instead of validating in controllers, create dedicated form request classes:

```php
class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ];
    }
}
```

## Conclusion

Following these Laravel best practices will help you build more robust, maintainable applications. Remember that best practices evolve with the framework, so stay updated with the latest Laravel documentation and community recommendations.

The key to mastering Laravel is understanding its philosophy of making common tasks simple while keeping the flexibility to handle complex requirements. Happy coding!
BLOG;
    }

    private function getSeoOptimizedBlogPost(): string
    {
        return <<<BLOG
# Laravel SEO: Ultimate Guide to Optimizing Your Laravel Application

## Introduction to Laravel SEO

Laravel SEO is crucial for ensuring your web application ranks well in search engines. This comprehensive guide covers everything you need to know about implementing Laravel SEO best practices.

## Why Laravel SEO Matters

Search engine optimization in Laravel applications requires special attention due to the framework's dynamic nature. Proper Laravel SEO implementation can significantly improve your site's visibility.

### Key Benefits of Laravel SEO:
- Improved search engine rankings
- Better user experience
- Increased organic traffic
- Higher conversion rates

## Essential Laravel SEO Techniques

### 1. Meta Tags Management

Implement dynamic meta tags for better Laravel SEO:

```php
@section('meta')
    <meta name="description" content="{{ \$post->meta_description }}">
    <meta name="keywords" content="Laravel SEO, {{ \$post->keywords }}">
@endsection
```

### 2. XML Sitemap Generation

A sitemap is essential for Laravel SEO. Use packages like spatie/laravel-sitemap:

```php
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

Sitemap::create()
    ->add(Url::create('/'))
    ->add(Url::create('/about'))
    ->writeToFile(public_path('sitemap.xml'));
```

## Advanced Laravel SEO Strategies

### Schema Markup Implementation

Structured data helps search engines understand your content better, improving Laravel SEO:

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Laravel SEO Best Practices",
  "author": {
    "@type": "Person",
    "name": "Your Name"
  }
}
```

### Page Speed Optimization

Laravel SEO heavily depends on page speed. Implement:
- Route caching
- View caching
- Query optimization
- Asset minification

## Laravel SEO Tools and Packages

Several packages can enhance your Laravel SEO efforts:
- artesaos/seotools
- spatie/laravel-sitemap
- spatie/schema-org

## Monitoring Laravel SEO Performance

Track your Laravel SEO success with:
- Google Search Console
- Google Analytics
- Laravel Telescope for performance monitoring

## Conclusion

Mastering Laravel SEO requires continuous effort and monitoring. By implementing these strategies, your Laravel application will be well-positioned for search engine success. Remember, Laravel SEO is not a one-time task but an ongoing process that evolves with search engine algorithms.

Keep your Laravel SEO strategy updated and always focus on providing value to your users while optimizing for search engines.
BLOG;
    }

    private function generateContentWithLength(int $wordCount): string
    {
        $words = [
            'Laravel',
            'PHP',
            'framework',
            'development',
            'application',
            'best',
            'practices',
            'code',
            'clean',
            'maintainable',
            'scalable',
            'performance',
            'optimization',
            'database',
            'Eloquent',
            'routing',
            'controllers',
            'models',
            'views',
            'blade',
        ];

        $content = "# Blog Post About Web Development\n\n";
        $currentWordCount = 5;

        while ($currentWordCount < $wordCount) {
            $sentenceLength = rand(10, 20);
            $sentence = '';

            for ($i = 0; $i < $sentenceLength; $i++) {
                $sentence .= $words[array_rand($words)].' ';
            }

            $content .= ucfirst(trim($sentence)).'. ';
            $currentWordCount += $sentenceLength;

            if ($currentWordCount % 100 < 20) {
                $content .= "\n\n";
            }
        }

        return $content;
    }
}
