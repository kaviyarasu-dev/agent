# Security Policy

## Supported Versions

We actively maintain and provide security updates for the following versions of the AI Agent Laravel package:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security vulnerability in the AI Agent package, please report it responsibly.

### How to Report

1. **Email**: Send details to `kaviphpnschool@gmail.com`
2. **Subject**: Use the prefix `[SECURITY]` in your email subject
3. **Include**: 
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### Response Timeline

- **Initial Response**: Within 48 hours
- **Assessment**: Within 5 business days
- **Fix**: Critical vulnerabilities patched within 7 days
- **Disclosure**: Public disclosure after fix is available

## Security Best Practices

### API Key Management

**❌ Never do this:**
```php
// Don't hardcode API keys in your code
$apiKey = 'sk-1234567890abcdef';

// Don't commit API keys to version control
CLAUDE_API_KEY=sk-1234567890abcdef
```

**✅ Do this instead:**
```php
// Use environment variables
$apiKey = env('CLAUDE_API_KEY');

// Use Laravel's config system
$apiKey = config('ai-agent.providers.claude.api_key');
```

### Environment Configuration

Ensure your `.env` file is properly secured:

```env
# AI Provider API Keys - Keep these secure!
CLAUDE_API_KEY=your-secure-api-key-here
OPENAI_API_KEY=your-secure-api-key-here
IDEOGRAM_API_KEY=your-secure-api-key-here

# Enable security features
AI_RATE_LIMITING_ENABLED=true
AI_LOGGING_ENABLED=true
AI_CACHE_ENABLED=true
```

### Input Validation

Always validate and sanitize user inputs:

```php
use Illuminate\Support\Facades\Validator;

class BlogAiAgent
{
    public function execute(array $data)
    {
        // Validate input data
        $validator = Validator::make($data, [
            'prompt' => 'required|string|max:10000',
            'options.temperature' => 'numeric|between:0,1',
            'options.max_tokens' => 'integer|min:1|max:4096',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid input data');
        }

        // Sanitize user input
        $prompt = strip_tags($data['prompt']);
        $prompt = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');

        return $this->textService->generateText($prompt);
    }
}
```

### Rate Limiting

Implement rate limiting to prevent abuse:

```php
// In your config/ai-agent.php
'rate_limiting' => [
    'enabled' => env('AI_RATE_LIMITING_ENABLED', true),
    'requests_per_minute' => 60,
    'requests_per_hour' => 1000,
    'requests_per_day' => 10000,
],
```
