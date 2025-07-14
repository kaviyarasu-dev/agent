<?php

/**
 * SOLID Principles Implementation Demo
 * 
 * This file demonstrates how the new response format system
 * follows SOLID principles in its design.
 */

use Kaviyarasu\AIAgent\Contracts\Formatters\ResponseFormatterInterface;
use Kaviyarasu\AIAgent\Formatters\TextResponseFormatter;
use Kaviyarasu\AIAgent\Formatters\ImageResponseFormatter;
use Kaviyarasu\AIAgent\Formatters\ResponseFormatterFactory;
use Kaviyarasu\AIAgent\Responses\TextResponse;
use Kaviyarasu\AIAgent\Responses\ImageResponse;
use Kaviyarasu\AIAgent\Services\Core\TextService;
use Kaviyarasu\AIAgent\Services\Core\ImageService;

// =======================
// SOLID Principles Demo
// =======================

// 1. Single Responsibility Principle (SRP)
// ----------------------------------------
// Each class has a single, well-defined responsibility:
// - TextResponse: Handles text response data and metadata
// - ImageResponse: Handles image response data and metadata
// - TextResponseFormatter: Formats text responses
// - ImageResponseFormatter: Formats image responses
// - ResponseFormatterFactory: Creates appropriate formatters

echo "=== SOLID Principles Demonstration ===\
\
";

// 2. Open/Closed Principle (OCP)
// ------------------------------
// System is open for extension but closed for modification
// You can add new formatters without modifying existing code

class CustomTextResponseFormatter implements ResponseFormatterInterface
{
    public function formatSuccess(mixed $data, array $metadata = []): \Kaviyarasu\AIAgent\Responses\AIResponse
    {
        // Custom formatting logic
        $metadata['custom_processor'] = 'CustomFormatter';
        $metadata['formatted_at'] = now()->toISOString();
        
        return TextResponse::fromText($data, $metadata['provider'] ?? null, $metadata['model'] ?? null, $metadata);
    }

    public function formatError(string $error, array $metadata = []): \Kaviyarasu\AIAgent\Responses\AIResponse
    {
        $metadata['custom_processor'] = 'CustomFormatter';
        $metadata['error_formatted_at'] = now()->toISOString();
        
        return TextResponse::fromError($error, $metadata['provider'] ?? null, $metadata['model'] ?? null, $metadata);
    }

    public function formatWithTiming(mixed $data, float $startTime, array $metadata = []): \Kaviyarasu\AIAgent\Responses\AIResponse
    {
        $processingTime = microtime(true) - $startTime;
        $metadata['processing_time'] = round($processingTime, 3);
        $metadata['custom_processor'] = 'CustomFormatter';
        
        return $this->formatSuccess($data, $metadata);
    }

    public function getResponseType(): string
    {
        return 'custom_text';
    }
}

// Extend the system without modifying existing classes
$factory = new ResponseFormatterFactory();
$factory->register('custom_text', new CustomTextResponseFormatter());

echo "2. Open/Closed Principle: Added custom formatter without modifying existing code\
";

// 3. Liskov Substitution Principle (LSP)
// --------------------------------------
// All response formatters are interchangeable through the interface

function processWithAnyFormatter(ResponseFormatterInterface $formatter, string $data): \Kaviyarasu\AIAgent\Responses\AIResponse
{
    return $formatter->formatSuccess($data, [
        'provider' => 'test-provider',
        'model' => 'test-model'
    ]);
}

$textFormatter = new TextResponseFormatter();
$imageFormatter = new ImageResponseFormatter();
$customFormatter = new CustomTextResponseFormatter();

// All formatters can be used interchangeably
$textResult = processWithAnyFormatter($textFormatter, 'This is generated text');
$imageResult = processWithAnyFormatter($imageFormatter, 'https://example.com/image.jpg');
$customResult = processWithAnyFormatter($customFormatter, 'This is custom formatted text');

echo "3. Liskov Substitution Principle: All formatters work interchangeably\
";
echo "   - Text Result Success: " . ($textResult->isSuccess() ? 'Yes' : 'No') . "\
";
echo "   - Image Result Success: " . ($imageResult->isSuccess() ? 'Yes' : 'No') . "\
";
echo "   - Custom Result Success: " . ($customResult->isSuccess() ? 'Yes' : 'No') . "\
\
";

// 4. Interface Segregation Principle (ISP)
// ----------------------------------------
// Interfaces are focused and not forced to implement unused methods

// TextServiceInterface only has text-related methods
// ImageServiceInterface only has image-related methods
// ResponseFormatterInterface only has formatting methods

echo "4. Interface Segregation Principle: Focused interfaces\
";
echo "   - TextServiceInterface: Only text methods\
";
echo "   - ImageServiceInterface: Only image methods\
";
echo "   - ResponseFormatterInterface: Only formatting methods\
\
";

// 5. Dependency Inversion Principle (DIP)
// ---------------------------------------
// High-level modules depend on abstractions, not concretions

class AIResponseProcessor
{
    private ResponseFormatterInterface $formatter;

    public function __construct(ResponseFormatterInterface $formatter)
    {
        $this->formatter = $formatter; // Depends on abstraction, not concrete class
    }

    public function processData(string $data): \Kaviyarasu\AIAgent\Responses\AIResponse
    {
        return $this->formatter->formatSuccess($data, [
            'processor' => 'AIResponseProcessor',
            'processed_at' => now()->toISOString()
        ]);
    }

    public function processError(string $error): \Kaviyarasu\AIAgent\Responses\AIResponse
    {
        return $this->formatter->formatError($error, [
            'processor' => 'AIResponseProcessor',
            'error_processed_at' => now()->toISOString()
        ]);
    }
}

// Can work with any formatter implementation
$textProcessor = new AIResponseProcessor($textFormatter);
$imageProcessor = new AIResponseProcessor($imageFormatter);
$customProcessor = new AIResponseProcessor($customFormatter);

$textProcessorResult = $textProcessor->processData('AI generated content');
$imageProcessorResult = $imageProcessor->processData('https://example.com/generated-image.jpg');
$customProcessorResult = $customProcessor->processData('Custom processed content');

echo "5. Dependency Inversion Principle: High-level class depends on abstraction\
";
echo "   - Text Processor Result: " . ($textProcessorResult->isSuccess() ? 'Success' : 'Failed') . "\
";
echo "   - Image Processor Result: " . ($imageProcessorResult->isSuccess() ? 'Success' : 'Failed') . "\
";
echo "   - Custom Processor Result: " . ($customProcessorResult->isSuccess() ? 'Success' : 'Failed') . "\
\
";

// =======================
// Advanced Usage Examples
// =======================

// Factory Pattern Usage
echo "=== Factory Pattern Usage ===\
";

$factory = new ResponseFormatterFactory();

// Get formatter by type
$textFormatter = $factory->get('text');
$imageFormatter = $factory->get('image');

echo "Available formatters: " . implode(', ', array_keys($factory->getAll())) . "\
";

// Strategy Pattern Example
echo "\
=== Strategy Pattern Example ===\
";

class ResponseStrategy
{
    private ResponseFormatterInterface $formatter;

    public function setFormatter(ResponseFormatterInterface $formatter): void
    {
        $this->formatter = $formatter;
    }

    public function execute(string $data): \Kaviyarasu\AIAgent\Responses\AIResponse
    {
        return $this->formatter->formatSuccess($data, [
            'strategy' => 'ResponseStrategy',
            'execution_time' => microtime(true)
        ]);
    }
}

$strategy = new ResponseStrategy();

// Switch strategies at runtime
$strategy->setFormatter(new TextResponseFormatter());
$textStrategyResult = $strategy->execute('Text strategy result');

$strategy->setFormatter(new ImageResponseFormatter());
$imageStrategyResult = $strategy->execute('https://example.com/strategy-image.jpg');

echo "Strategy Pattern: Changed formatter at runtime\
";
echo "   - Text Strategy: " . ($textStrategyResult->isSuccess() ? 'Success' : 'Failed') . "\
";
echo "   - Image Strategy: " . ($imageStrategyResult->isSuccess() ? 'Success' : 'Failed') . "\
\
";

// =======================
// Error Handling Examples
// =======================

echo "=== Error Handling Examples ===\
";

// Consistent error structure across all formatters
$textError = $textFormatter->formatError('Text generation failed');
$imageError = $imageFormatter->formatError('Image generation failed');

echo "Consistent error format:\
";
echo "   - Text Error: " . $textError->getError() . "\
";
echo "   - Image Error: " . $imageError->getError() . "\
";

// Both errors have the same structure
echo "   - Both have same structure: " . (
    method_exists($textError, 'getError') && method_exists($imageError, 'getError') ? 'Yes' : 'No'
) . "\
\
";

// =======================
// Performance Tracking
// =======================

echo "=== Performance Tracking ===\
";

$startTime = microtime(true);
$timedResponse = $textFormatter->formatWithTiming('Performance test data', $startTime, [
    'provider' => 'test-provider',
    'model' => 'test-model'
]);

echo "Performance tracking built into formatters:\
";
echo "   - Processing time: " . $timedResponse->getMetadata()['processing_time'] . "s\
";
echo "   - Timestamp: " . $timedResponse->getMetadata()['timestamp'] . "\
\
";

echo "=== SOLID Principles Successfully Implemented ===\
";
