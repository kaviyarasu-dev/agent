<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Services\Modules\Storyboard;

use Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface;
use Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface;

class ShotService
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

    public function generateShotDescription(array $sceneData): string
    {
        $prompt = $this->buildShotPrompt($sceneData);

        return $this->textService->generateText($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 300,
        ]);
    }

    public function generateStoryboardPanel(string $shotDescription, array $options = []): string
    {
        $imagePrompt = "Storyboard panel: {$shotDescription}. Black and white sketch, cinematic composition.";

        return $this->imageService->generateImage($imagePrompt, array_merge([
            'size' => '1024x576', // 16:9 aspect ratio
            'quality' => 'standard',
        ], $options));
    }

    public function generateShotList(array $scenes): array
    {
        $shots = [];

        foreach ($scenes as $index => $scene) {
            $shotCount = $this->determineShotCount($scene);

            for ($i = 0; $i < $shotCount; $i++) {
                $shotData = [
                    'scene_number' => $index + 1,
                    'shot_number' => $i + 1,
                    'description' => $scene['description'] ?? '',
                    'action' => $scene['action'] ?? '',
                    'dialogue' => $scene['dialogue'] ?? '',
                ];

                $shots[] = [
                    'id' => sprintf('S%02dSH%02d', $index + 1, $i + 1),
                    'description' => $this->generateShotDescription($shotData),
                    'type' => $this->determineShotType($i, $shotCount),
                    'duration' => $this->estimateDuration($shotData),
                ];
            }
        }

        return $shots;
    }

    public function generateStoryboard(array $shotList): array
    {
        $storyboard = [];

        foreach ($shotList as $shot) {
            $storyboard[] = [
                'shot_id' => $shot['id'],
                'description' => $shot['description'],
                'image' => $this->generateStoryboardPanel($shot['description']),
                'type' => $shot['type'] ?? 'medium',
                'duration' => $shot['duration'] ?? 3,
                'notes' => $shot['notes'] ?? '',
            ];
        }

        return $storyboard;
    }

    private function buildShotPrompt(array $sceneData): string
    {
        $parts = [
            'Generate a cinematic shot description for a storyboard.',
            'Scene: '.($sceneData['description'] ?? 'No description'),
        ];

        if (! empty($sceneData['action'])) {
            $parts[] = 'Action: '.$sceneData['action'];
        }

        if (! empty($sceneData['dialogue'])) {
            $parts[] = 'Dialogue: '.$sceneData['dialogue'];
        }

        $parts[] = 'Describe the camera angle, composition, and key visual elements.';

        return implode("\n", $parts);
    }

    private function determineShotCount(array $scene): int
    {
        $complexity = 0;

        if (! empty($scene['dialogue'])) {
            $complexity += substr_count($scene['dialogue'], '.') + 1;
        }

        if (! empty($scene['action'])) {
            $complexity += 2;
        }

        return max(1, min(5, $complexity));
    }

    private function determineShotType(int $index, int $total): string
    {
        $types = ['wide', 'medium', 'close-up', 'over-shoulder', 'point-of-view'];

        if ($index === 0) {
            return 'wide'; // Establishing shot
        }

        if ($index === $total - 1) {
            return 'medium'; // Closing shot
        }

        return $types[array_rand($types)];
    }

    private function estimateDuration(array $shotData): float
    {
        $duration = 3.0; // Base duration

        if (! empty($shotData['dialogue'])) {
            // Estimate based on dialogue length
            $wordCount = str_word_count($shotData['dialogue']);
            $duration += $wordCount * 0.3; // ~0.3 seconds per word
        }

        if (! empty($shotData['action'])) {
            $duration += 2.0; // Additional time for action
        }

        return round($duration, 1);
    }
}
