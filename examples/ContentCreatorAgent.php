<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Examples;

use Kaviyarasu\AIAgent\Agents\BaseAIAgent;

class ContentCreatorAgent extends BaseAIAgent
{
    protected array $requiredServices = ['text', 'image'];

    public function __construct()
    {
        parent::__construct(app(\Kaviyarasu\AIAgent\Factory\ServiceFactory::class));

        // Default to Claude for text
        if ($this->textService) {
            $this->switchProvider('claude');
        }
    }

    public function execute(array $data): array
    {
        $topic = $data['topic'] ?? 'General Content';
        $style = $data['style'] ?? 'professional';
        $includeImage = $data['include_image'] ?? true;

        // Generate text content
        $content = $this->generateContent($topic, $style);

        $result = [
            'content' => $content,
            'topic' => $topic,
            'style' => $style,
        ];

        // Generate accompanying image if requested
        if ($includeImage && $this->imageService) {
            $imagePrompt = $this->buildImagePrompt($topic, $style);
            $result['image'] = $this->imageService->generateImage($imagePrompt);
        }

        return $result;
    }

    protected function generateContent(string $topic, string $style): string
    {
        $prompt = $this->buildContentPrompt($topic, $style);

        return $this->textService->generateText($prompt, [
            'max_tokens' => 1000,
            'temperature' => $style === 'creative' ? 0.8 : 0.6,
        ]);
    }

    protected function buildContentPrompt(string $topic, string $style): string
    {
        $styleGuide = match ($style) {
            'casual' => 'casual and conversational',
            'professional' => 'professional and informative',
            'creative' => 'creative and engaging',
            'technical' => 'technical and detailed',
            'educational' => 'educational and clear',
            default => 'clear and engaging',
        };

        return <<<PROMPT
Create compelling content about: {$topic}

Style: Write in a {$styleGuide} manner.
Format: Use appropriate headings and structure.
Length: Aim for comprehensive coverage while being concise.

Please generate the content now.
PROMPT;
    }

    protected function buildImagePrompt(string $topic, string $style): string
    {
        $imageStyle = match ($style) {
            'casual' => 'friendly, approachable illustration',
            'professional' => 'clean, professional design',
            'creative' => 'artistic, creative visualization',
            'technical' => 'detailed technical diagram',
            'educational' => 'clear educational infographic',
            default => 'modern, appealing design',
        };

        return "Create a {$imageStyle} representing: {$topic}. High quality, visually appealing.";
    }
}
