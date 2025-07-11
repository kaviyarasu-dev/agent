<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Contracts\Capabilities;

interface TextGenerationInterface
{
    public function generateText(array $params): string;

    public function streamText(array $params): iterable;

    public function getMaxTokens(): int;
}
