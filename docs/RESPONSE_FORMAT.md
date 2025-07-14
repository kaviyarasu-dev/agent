# AI Agent Response Format Documentation

## Overview

The AI Agent package now provides structured response objects that follow SOLID principles, ensuring consistency, extensibility, and proper error handling across all AI operations.

## Response Structure

### Base Response Format

All responses extend the `AIResponse` base class and follow this structure:

```php
{
    "success": true|false,
    "data": mixed,
    "metadata": {
        "timestamp": "2025-01-XX",
        "processing_time": 1.234,
        "provider": "claude",
        "model": "claude-3-5-sonnet-20241022",
        // ... additional metadata
    },
    "error": "Error message if failed" // Only present on failures
}
```

### Text Response

```php
use Kaviyarasu\AIAgent\Facades\AIAgent;

$response = AIAgent::generateText('Write a story about AI');

// Access response data
$response->isSuccess();           // bool
$response->getText();             // string
$response->getProvider();         // string
$response->getModel();            // string
$response->getTokensUsed();       // int
$response->getProcessingTime();   // float
$response->getMetadata();         // array
$response->toArray();             // array
$response->toJson();              // string
```

### Image Response

```php
$response = AIAgent::generateImage('A futuristic city');

// Access response data
$response->isSuccess();           // bool
$response->getUrl();              // string (first image URL)
$response->getUrls();             // array (all image URLs)
$response->getSize();             // string
$response->getQuality();          // string
$response->getStyle();            // string
$response->getProvider();         // string
$response->getModel();            // string
$response->getProcessingTime();   // float
```

## Usage Examples

### Basic Usage

```php
use Kaviyarasu\AIAgent\Facades\AIAgent;

// Text generation
$textResponse = AIAgent::generateText('Explain quantum computing', [
    'temperature' => 0.7,
    'max_tokens' => 1000
]);

if ($textResponse->isSuccess()) {
    echo "Generated text: " . $textResponse->getText();
    echo "Used " . $textResponse->getTokensUsed() . " tokens";
    echo "Processing time: " . $textResponse->getProcessingTime() . "s";
} else {
    echo "Error: " . $textResponse->getError();
}

// Image generation
$imageResponse = AIAgent::generateImage('A serene landscape', [
    'size' => '1024x1024',
    'quality' => 'hd'
]);

if ($imageResponse->isSuccess()) {
    echo "Image URL: " . $imageResponse->getUrl();
    echo "Size: " . $imageResponse->getSize();
} else {
    echo "Error: " . $imageResponse->getError();
}
```

### Provider and Model Switching

```php
// Switch provider and model
$response = AIAgent::provider('claude')
    ->withModel('claude-3-opus-20240229')
    ->generateText('Write a poem about technology');

echo "Provider: " . $response->getProvider();
echo "Model: " . $response->getModel();
echo "Text: " . $response->getText();
```

### Multiple Images

```php
$response = AIAgent::generateMultipleImages('Abstract art concepts', 3, [
    'size' => '512x512'
]);

if ($response->isSuccess()) {
    foreach ($response->getUrls() as $index => $url) {
        echo "Image " . ($index + 1) . ": " . $url . "\
";
    }
    echo "Total images: " . count($response->getUrls());
}
```

### Error Handling

```php
$response = AIAgent::generateText('Some prompt');

if (!$response->isSuccess()) {
    // Handle error
    logger()->error('AI generation failed', [
        'error' => $response->getError(),
        'provider' => $response->getProvider(),
        'processing_time' => $response->getProcessingTime(),
        'metadata' => $response->getMetadata()
    ]);
}
```

## Backward Compatibility

The package maintains backward compatibility with raw response methods:

```php
// New structured response (recommended)
$response = AIAgent::generateText('Hello world');
$text = $response->getText();

// Old raw response (still supported)
$text = AIAgent::generateTextRaw('Hello world');
```

## Advanced Features

### Response Serialization

```php
$response = AIAgent::generateText('Generate content');

// Convert to array
$array = $response->toArray();

// Convert to JSON
$json = $response->toJson();

// Store in database
DB::table('ai_responses')->insert([
    'response_data' => json_encode($response),
    'created_at' => now()
]);
```

### Custom Metadata

```php
$response = AIAgent::generateText('Content for campaign', [
    'temperature' => 0.8,
    'custom_metadata' => [
        'campaign_id' => 'CAMP-2025-001',
        'user_id' => 123
    ]
]);

// Access custom metadata
$metadata = $response->getMetadata();
$campaignId = $metadata['custom_metadata']['campaign_id'];
```

### Response Processing

```php
function processTextResponse(TextResponse $response): array
{
    return [
        'success' => $response->isSuccess(),
        'word_count' => str_word_count($response->getText() ?? ''),
        'efficiency' => $response->getTokensUsed() / max($response->getProcessingTime(), 0.001),
        'provider' => $response->getProvider(),
        'model' => $response->getModel()
    ];
}

$response = AIAgent::generateText('Analyze this content');
$analysis = processTextResponse($response);
```

## SOLID Principles Implementation

### Single Responsibility
- `TextResponse`: Handles text response data
- `ImageResponse`: Handles image response data  
- `TextResponseFormatter`: Formats text responses
- `ImageResponseFormatter`: Formats image responses

### Open/Closed Principle
- Easy to add new response types and formatters
- Existing code doesn't need modification

### Liskov Substitution
- All formatters implement `ResponseFormatterInterface`
- All responses extend `AIResponse`

### Interface Segregation
- Focused interfaces for specific capabilities
- No unused methods forced on implementations

### Dependency Inversion
- Services depend on abstractions, not concrete classes
- Formatter factory provides appropriate implementations

## Performance Considerations

- Response objects are lightweight
- Metadata is lazily populated
- JSON serialization is optimized
- Processing time is automatically tracked

## Migration Guide

### From Raw Responses

```php
// Old way
$text = AIAgent::text()->generateText('prompt');

// New way
$response = AIAgent::generateText('prompt');
$text = $response->getText();
```

### From Array Responses

```php
// Old way
$images = AIAgent::image()->generateMultipleImages('prompt', 3);

// New way
$response = AIAgent::generateMultipleImages('prompt', 3);
$images = $response->getUrls();
```

## Configuration

Add custom formatters in your `config/ai-agent.php`:

```php
'custom_formatters' => [
    'custom_text' => App\Formatters\CustomTextFormatter::class,
    'enhanced_image' => App\Formatters\EnhancedImageFormatter::class,
],
```

## Testing

```php
use Kaviyarasu\AIAgent\Responses\TextResponse;

public function test_text_generation_returns_structured_response()
{
    $response = AIAgent::generateText('Test prompt');
    
    $this->assertInstanceOf(TextResponse::class, $response);
    $this->assertTrue($response->isSuccess());
    $this->assertNotEmpty($response->getText());
    $this->assertNotNull($response->getProvider());
    $this->assertGreaterThan(0, $response->getProcessingTime());
}
```

## Best Practices

1. **Always check success status** before using response data
2. **Use structured responses** for new implementations
3. **Log response metadata** for monitoring and debugging
4. **Handle errors gracefully** with proper error messages
5. **Use type hints** for better IDE support and error detection

## Troubleshooting

### Common Issues

1. **Response returns null data**: Check if generation was successful
2. **Missing metadata**: Ensure provider supports metadata extraction
3. **Performance issues**: Monitor processing times in metadata
4. **Type errors**: Use proper type hints and check response types

### Debug Information

```php
$response = AIAgent::generateText('Debug prompt');

// Full debug information
var_dump([
    'success' => $response->isSuccess(),
    'error' => $response->getError(),
    'metadata' => $response->getMetadata(),
    'provider' => $response->getProvider(),
    'model' => $response->getModel(),
    'processing_time' => $response->getProcessingTime()
]);
```
