<?php

/**
 * This script verifies the provider switching implementation
 * by checking the existence of all required files and classes
 */

$basePath = __DIR__ . '/../src';

$requiredFiles = [
    // Core Interfaces
    'Contracts/HasProviderSwitching.php' => 'HasProviderSwitching interface',
    'Contracts/HasModelSwitching.php' => 'HasModelSwitching interface',
    
    // Services
    'Services/AI/TextService.php' => 'TextService class',
    'Services/AI/ImageService.php' => 'ImageService class', 
    'Services/AI/VideoService.php' => 'VideoService class',
    
    // Base Agent
    'Agents/BaseAIAgent.php' => 'BaseAIAgent abstract class',
    
    // Trait
    'Traits/HasDynamicProvider.php' => 'HasDynamicProvider trait',
    
    // Examples
    'Examples/ContentCreatorAgent.php' => 'ContentCreatorAgent example',
    'Examples/EmailAIAgent.php' => 'EmailAIAgent example',
    'Examples/AdaptiveCodeAgent.php' => 'AdaptiveCodeAgent example',
];

$results = [];
$allExist = true;

echo "=== Verifying Provider Switching Implementation ===\n\n";

foreach ($requiredFiles as $file => $description) {
    $fullPath = $basePath . '/' . $file;
    $exists = file_exists($fullPath);
    $results[$file] = $exists;
    
    if (!$exists) {
        $allExist = false;
    }
    
    echo sprintf(
        "[%s] %s - %s\n",
        $exists ? '✓' : '✗',
        $description,
        $exists ? 'Found' : 'Missing'
    );
}

echo "\n=== Summary ===\n";
echo sprintf("Total files: %d\n", count($requiredFiles));
echo sprintf("Found: %d\n", count(array_filter($results)));
echo sprintf("Missing: %d\n", count($results) - count(array_filter($results)));

if ($allExist) {
    echo "\n✅ All required files are present!\n";
} else {
    echo "\n❌ Some files are missing. The implementation is incomplete.\n";
    echo "\nMissing files:\n";
    foreach ($results as $file => $exists) {
        if (!$exists) {
            echo "  - $file\n";
        }
    }
}

// Check for provider updates
echo "\n=== Checking Provider Updates ===\n";

$providers = [
    'Providers/AbstractProvider.php',
    'Providers/OpenAI/OpenAIProvider.php',
    'Providers/Claude/ClaudeProvider.php',
];

foreach ($providers as $provider) {
    $fullPath = $basePath . '/' . $provider;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        
        // Check if provider implements model switching
        $hasModelSwitching = strpos($content, 'switchModel') !== false;
        $hasGetModelCapabilities = strpos($content, 'getModelCapabilities') !== false;
        
        echo sprintf(
            "[%s] %s - Model switching: %s\n",
            file_exists($fullPath) ? '✓' : '✗',
            basename($provider),
            $hasModelSwitching && $hasGetModelCapabilities ? 'Yes' : 'No'
        );
    }
}

// Check configuration updates
echo "\n=== Checking Configuration ===\n";

$configFile = dirname(__DIR__) . '/config/ai-agent.php';
if (file_exists($configFile)) {
    $config = include $configFile;
    
    $hasFeatureFlags = isset($config['features']);
    $hasModelConfigs = isset($config['providers']['openai']['models']);
    $hasModelSelection = isset($config['model_selection']);
    
    echo sprintf("[%s] Feature flags - %s\n", $hasFeatureFlags ? '✓' : '✗', $hasFeatureFlags ? 'Present' : 'Missing');
    echo sprintf("[%s] Model configurations - %s\n", $hasModelConfigs ? '✓' : '✗', $hasModelConfigs ? 'Present' : 'Missing');
    echo sprintf("[%s] Model selection strategy - %s\n", $hasModelSelection ? '✓' : '✗', $hasModelSelection ? 'Present' : 'Missing');
} else {
    echo "[✗] Configuration file not found\n";
}

echo "\n";