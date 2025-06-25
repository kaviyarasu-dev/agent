<?php

declare(strict_types=1);

namespace App\AI\Services\Modules\Storyboard;

use App\AI\Contracts\Services\TextServiceInterface;
use App\AI\Contracts\Services\ImageServiceInterface;

class CharacterService
{
    private TextServiceInterface $textService;
    private ImageServiceInterface $imageService;
    
    public function __construct(
        TextServiceInterface $textService,
        ImageServiceInterface $imageService
    ) {
        $this->textService = $textService;
        $this->imageService = $imageService;
    }
    
    public function generateCharacterDescription(array $attributes): string
    {
        $prompt = $this->buildCharacterPrompt($attributes);
        
        return $this->textService->generateText($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 500,
        ]);
    }
    
    public function generateCharacterImage(string $description, array $options = []): string
    {
        $imagePrompt = "Character portrait: {$description}. Professional concept art, detailed, high quality.";
        
        return $this->imageService->generateImage($imagePrompt, array_merge([
            'size' => '512x768',
            'quality' => 'hd',
        ], $options));
    }
    
    public function generateCharacterSheet(array $character): array
    {
        $description = $this->generateCharacterDescription($character['attributes'] ?? []);
        
        $poses = [
            'front_view' => 'front facing view',
            'side_view' => 'side profile view',
            'back_view' => 'back view',
            'action_pose' => 'dynamic action pose',
        ];
        
        $images = [];
        foreach ($poses as $key => $pose) {
            $prompt = "{$description}, {$pose}, character sheet style";
            $images[$key] = $this->imageService->generateImage($prompt);
        }
        
        return [
            'description' => $description,
            'images' => $images,
            'generated_at' => now()->toIso8601String(),
        ];
    }
    
    private function buildCharacterPrompt(array $attributes): string
    {
        $parts = [
            "Create a detailed character description for a story.",
            "Character attributes:",
        ];
        
        foreach ($attributes as $key => $value) {
            $parts[] = "- {$key}: {$value}";
        }
        
        $parts[] = "Provide a vivid, creative description including personality, appearance, and background.";
        
        return implode("\n", $parts);
    }
}