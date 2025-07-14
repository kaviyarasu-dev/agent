<?php

/**
 * Enhanced AI Agent Usage Examples with Structured Responses
 *
 * This file demonstrates the new structured response format
 * following SOLID principles and proper error handling.
 */

use Kaviyarasu\AIAgent\Facades\AIAgent;
use Kaviyarasu\AIAgent\Responses\TextResponse;

// Example 1: Basic Text Generation with Structured Response
// --------------------------------------------------------

// New structured response format
$textResponse = AIAgent::generateText('Write a story about AI', [
    'temperature' => 0.8,
    'max_tokens' => 1000,
]);

echo 'Success: '.($textResponse->isSuccess() ? 'Yes' : 'No')."\
";
echo 'Text: '.$textResponse->getText()."\
";
echo 'Provider: '.$textResponse->getProvider()."\
";
echo 'Model: '.$textResponse->getModel()."\
";
echo 'Tokens Used: '.$textResponse->getTokensUsed()."\
";
echo 'Processing Time: '.$textResponse->getProcessingTime()."s\
";

// Access full metadata
$metadata = $textResponse->getMetadata();
echo 'Full Metadata: '.json_encode($metadata, JSON_PRETTY_PRINT)."\
";

// Convert to JSON
echo 'JSON Response: '.$textResponse->toJson()."\
";

// Example 2: Backward Compatibility
// --------------------------------

// Still works with raw string response
$rawText = AIAgent::generateTextRaw('Write a haiku about Laravel');
echo 'Raw Text: '.$rawText."\
";

// Example 3: Image Generation with Structured Response
// ---------------------------------------------------

$imageResponse = AIAgent::generateImage('A futuristic city at sunset', [
    'size' => '1024x1024',
    'quality' => 'hd',
    'style' => 'vivid',
]);

echo 'Success: '.($imageResponse->isSuccess() ? 'Yes' : 'No')."\
";
echo 'Image URL: '.$imageResponse->getUrl()."\
";
echo 'Provider: '.$imageResponse->getProvider()."\
";
echo 'Model: '.$imageResponse->getModel()."\
";
echo 'Size: '.$imageResponse->getSize()."\
";
echo 'Quality: '.$imageResponse->getQuality()."\
";
echo 'Style: '.$imageResponse->getStyle()."\
";
echo 'Processing Time: '.$imageResponse->getProcessingTime()."s\
";

// Example 4: Multiple Images with Structured Response
// --------------------------------------------------

$multipleImagesResponse = AIAgent::generateMultipleImages('Abstract art', 3, [
    'size' => '512x512',
]);

echo 'Success: '.($multipleImagesResponse->isSuccess() ? 'Yes' : 'No')."\
";
echo 'Number of Images: '.count($multipleImagesResponse->getUrls())."\
";

foreach ($multipleImagesResponse->getUrls() as $index => $url) {
    echo 'Image '.($index + 1).': '.$url."\
";
}

// Example 5: Error Handling with Structured Responses
// --------------------------------------------------

try {
    $response = AIAgent::provider('invalid-provider')->generateText('Test prompt');

    if (! $response->isSuccess()) {
        echo 'Error: '.$response->getError()."\
";
        echo 'Provider: '.$response->getProvider()."\
";
        echo 'Processing Time: '.$response->getProcessingTime()."s\
";
    }
} catch (\Exception $e) {
    echo 'Exception: '.$e->getMessage()."\
";
}

// Example 6: Provider and Model Switching
// ---------------------------------------

$claudeResponse = AIAgent::provider('claude')
    ->withModel('claude-3-opus-20240229')
    ->generateText('Explain quantum computing');

echo "Claude Response:\
";
echo 'Provider: '.$claudeResponse->getProvider()."\
";
echo 'Model: '.$claudeResponse->getModel()."\
";
echo 'Text: '.$claudeResponse->getText()."\
";

$openaiResponse = AIAgent::provider('openai')
    ->withModel('gpt-4.1-2025-04-14')
    ->generateText('Explain quantum computing');

echo "\
OpenAI Response:\
";
echo 'Provider: '.$openaiResponse->getProvider()."\
";
echo 'Model: '.$openaiResponse->getModel()."\
";
echo 'Text: '.$openaiResponse->getText()."\
";

// Example 7: Working with Response Objects
// ---------------------------------------

function processTextResponse(TextResponse $response): void
{
    if ($response->isSuccess()) {
        echo 'Generated text: '.$response->getText()."\
";

        // Log usage statistics
        logger()->info('Text generation successful', [
            'provider' => $response->getProvider(),
            'model' => $response->getModel(),
            'tokens_used' => $response->getTokensUsed(),
            'processing_time' => $response->getProcessingTime(),
        ]);
    } else {
        echo 'Generation failed: '.$response->getError()."\
";

        // Log error
        logger()->error('Text generation failed', [
            'error' => $response->getError(),
            'provider' => $response->getProvider(),
            'metadata' => $response->getMetadata(),
        ]);
    }
}

$response = AIAgent::generateText('Write a product description for a smartphone');
processTextResponse($response);

// Example 8: Streaming with Metadata
// ----------------------------------

echo "\
Streaming text generation:\
";
$streamMetadata = [];
$chunkCount = 0;

foreach (AIAgent::streamText('Explain machine learning concepts') as $chunk) {
    echo $chunk;
    $chunkCount++;
    flush();
}

echo "\
Streamed {$chunkCount} chunks\
";

// Example 9: Comparing Providers
// -----------------------------

$prompt = 'Write a creative story about time travel';
$providers = ['claude', 'openai'];

$responses = [];
foreach ($providers as $provider) {
    $response = AIAgent::provider($provider)->generateText($prompt);
    $responses[$provider] = $response;

    echo "\
{$provider} Response:\
";
    echo 'Success: '.($response->isSuccess() ? 'Yes' : 'No')."\
";
    echo 'Model: '.$response->getModel()."\
";
    echo 'Tokens: '.$response->getTokensUsed()."\
";
    echo 'Time: '.$response->getProcessingTime()."s\
";
    echo 'Text length: '.strlen($response->getText())." characters\
";
}

// Example 10: Advanced Usage with Custom Metadata
// -----------------------------------------------

$serviceResponse = AIAgent::text()->generateText('Create a marketing email', [
    'temperature' => 0.7,
    'max_tokens' => 500,
    'custom_metadata' => ['campaign_id' => 'CAMP-2025-001'],
]);

// Access all metadata including custom fields
$allMetadata = $serviceResponse->getMetadata();
echo 'Campaign ID: '.($allMetadata['custom_metadata']['campaign_id'] ?? 'N/A')."\
";

// Example 11: Response Serialization
// ---------------------------------

// Convert to array for storage
$responseArray = $textResponse->toArray();

// Convert to JSON for API responses
$responseJson = $textResponse->toJson();

// Store in database
// DB::table('ai_responses')->insert([
//     'response_data' => json_encode($responseArray),
//     'created_at' => now(),
// ]);

// Example 12: Type Safety with Response Objects
// --------------------------------------------

function analyzeResponse(TextResponse $response): array
{
    return [
        'success' => $response->isSuccess(),
        'word_count' => str_word_count($response->getText() ?? ''),
        'char_count' => strlen($response->getText() ?? ''),
        'processing_time' => $response->getProcessingTime(),
        'efficiency' => $response->getTokensUsed() / max($response->getProcessingTime(), 0.001),
        'provider_used' => $response->getProvider(),
        'model_used' => $response->getModel(),
    ];
}

$analysisResponse = AIAgent::generateText('Analyze the benefits of renewable energy');
$analysis = analyzeResponse($analysisResponse);

echo "Response Analysis:\
";
echo json_encode($analysis, JSON_PRETTY_PRINT)."\
";
