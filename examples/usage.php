<?php

/**
 * AI Architecture Package - Usage Examples
 */

use App\AI\Contracts\Services\ImageServiceInterface;
use App\AI\Contracts\Services\TextServiceInterface;
use App\AI\Factory\ProviderFactory;
use App\AI\Services\Modules\Storyboard\CharacterService;
use App\AI\Services\Modules\Storyboard\ShotService;

// Example 1: Basic Text Generation
// --------------------------------
$textService = app(TextServiceInterface::class);

// Generate text with default provider (Claude)
$story = $textService->generateText(
    'Write a short story about a time-traveling scientist',
    ['temperature' => 0.8, 'max_tokens' => 1000]
);

// Switch to OpenAI for a different style
$textService->setProvider('openai');
$poem = $textService->generateText(
    'Write a haiku about artificial intelligence'
);

// Example 2: Image Generation
// --------------------------
$imageService = app(ImageServiceInterface::class);

// Generate a single image
$imageUrl = $imageService->generateImage(
    'A cyberpunk cityscape with neon lights and flying cars',
    ['size' => '1024x1024', 'quality' => 'hd']
);

// Generate multiple variations
$variations = $imageService->generateMultipleImages(
    'Abstract art representing the concept of time',
    4,
    ['size' => '512x512']
);

// Example 3: Storyboard Character Generation
// -----------------------------------------
$characterService = app(CharacterService::class);

// Generate a character description
$character = $characterService->generateCharacterDescription([
    'name' => 'Elena Voss',
    'age' => '28',
    'occupation' => 'Quantum Physicist',
    'personality' => 'Brilliant but reckless',
    'background' => 'Former child prodigy who discovered time travel',
]);

// Generate a full character sheet with images
$characterSheet = $characterService->generateCharacterSheet([
    'attributes' => [
        'name' => 'Marcus Chen',
        'appearance' => 'Tall, lean build, cybernetic left arm',
        'personality' => 'Stoic, calculating, secretly compassionate',
        'skills' => 'Hacking, martial arts, quantum mechanics',
    ],
]);

// Example 4: Storyboard Shot Generation
// ------------------------------------
$shotService = app(ShotService::class);

// Generate shots for a scene
$scenes = [
    [
        'description' => 'Elena enters her laboratory late at night',
        'action' => 'She walks past rows of quantum computers, their lights blinking in the darkness',
        'dialogue' => 'Another sleepless night. But I\'m so close to the breakthrough.',
    ],
    [
        'description' => 'The time machine activates unexpectedly',
        'action' => 'Sparks fly, energy crackles, and a portal opens',
        'dialogue' => 'No! It\'s too early! The calibration isn\'t complete!',
    ],
];

$shotList = $shotService->generateShotList($scenes);
$storyboard = $shotService->generateStoryboard($shotList);

// Example 5: Provider Management
// -----------------------------
$providerFactory = app(ProviderFactory::class);

// Get available providers for text generation
$textProviders = $providerFactory->getAvailableProviders('text');
foreach ($textProviders as $name => $provider) {
    echo "Provider: {$name} - Version: {$provider->getVersion()}\n";
}

// Example 6: Error Handling with Fallbacks
// ---------------------------------------
try {
    // This will automatically fallback if primary provider fails
    $result = $textService->generateText('Generate content');
} catch (\RuntimeException $e) {
    // All providers failed
    logger()->error('All AI providers failed', [
        'error' => $e->getMessage(),
    ]);
}

// Example 7: Streaming Responses
// -----------------------------
$textService->setProvider('claude');
$stream = $textService->streamText(
    'Explain quantum computing in simple terms',
    ['max_tokens' => 2000]
);

foreach ($stream as $chunk) {
    echo $chunk; // Output text as it's generated
    flush();
}

// Example 8: Model Switching
// -------------------------
use App\AI\Providers\Claude\ClaudeProvider;

$claude = $providerFactory->create('claude');
if ($claude instanceof ClaudeProvider) {
    // Switch to a different Claude model
    $claude->switchModel('claude-3-sonnet-20241128');

    // Use the new model
    $response = $claude->generateText([
        'prompt' => 'Explain the latest advances in AI',
        'max_tokens' => 1500,
    ]);
}

// Example 9: Custom Module Configuration
// -------------------------------------
// Configure in your service provider or bootstrap
app()->when(CharacterService::class)
    ->needs(TextServiceInterface::class)
    ->give(function ($app) {
        $service = $app->make(TextService::class);
        $service->setProvider('claude'); // Always use Claude for characters

        return $service;
    });

// Example 10: Caching Results
// --------------------------
use Illuminate\Support\Facades\Cache;

$cacheKey = 'ai_response_'.md5($prompt);
$response = Cache::remember($cacheKey, 3600, function () use ($textService, $prompt) {
    return $textService->generateText($prompt);
});
