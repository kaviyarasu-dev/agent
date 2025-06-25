#!/usr/bin/env php
<?php

echo "Updating namespaces to follow Spatie package structure...\n";

// Define namespace mappings
$namespaceMap = [
    'namespace App\\AI\\Config' => 'namespace WebsiteLearners\\AIAgent\\Config',
    'namespace App\\AI\\Contracts' => 'namespace WebsiteLearners\\AIAgent\\Contracts',
    'namespace App\\AI\\Factory' => 'namespace WebsiteLearners\\AIAgent\\Factory',
    'namespace App\\AI\\Providers' => 'namespace WebsiteLearners\\AIAgent\\Providers\\AI',
    'namespace App\\AI\\Services' => 'namespace WebsiteLearners\\AIAgent\\Services',

    // Use statements
    'use App\\AI\\Config\\' => 'use WebsiteLearners\\AIAgent\\Config\\',
    'use App\\AI\\Contracts\\' => 'use WebsiteLearners\\AIAgent\\Contracts\\',
    'use App\\AI\\Factory\\' => 'use WebsiteLearners\\AIAgent\\Factory\\',
    'use App\\AI\\Providers\\' => 'use WebsiteLearners\\AIAgent\\Providers\\AI\\',
    'use App\\AI\\Services\\' => 'use WebsiteLearners\\AIAgent\\Services\\',

    // Fully qualified class names
    '\\App\\AI\\Config\\' => '\\WebsiteLearners\\AIAgent\\Config\\',
    '\\App\\AI\\Contracts\\' => '\\WebsiteLearners\\AIAgent\\Contracts\\',
    '\\App\\AI\\Factory\\' => '\\WebsiteLearners\\AIAgent\\Factory\\',
    '\\App\\AI\\Providers\\' => '\\WebsiteLearners\\AIAgent\\Providers\\AI\\',
    '\\App\\AI\\Services\\' => '\\WebsiteLearners\\AIAgent\\Services\\',

    // Update existing src files
    'namespace VendorName\\Skeleton' => 'namespace WebsiteLearners\\AIAgent',
    'use VendorName\\Skeleton' => 'use WebsiteLearners\\AIAgent',
    'VendorName\\Skeleton\\' => 'WebsiteLearners\\AIAgent\\',
];

// Function to update namespaces in a file
function updateNamespaces($file, $namespaceMap)
{
    if (! file_exists($file)) {
        echo "File not found: $file\n";

        return;
    }

    $content = file_get_contents($file);
    $originalContent = $content;

    foreach ($namespaceMap as $old => $new) {
        $content = str_replace($old, $new, $content);
    }

    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Updated: $file\n";
    }
}

// Process all PHP files in src directory
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('src', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        updateNamespaces($file->getPathname(), $namespaceMap);
    }
}

echo "Namespace update completed!\n";
