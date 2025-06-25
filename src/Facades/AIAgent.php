<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface text()
 * @method static \WebsiteLearners\AIAgent\Contracts\Services\ImageServiceInterface image()
 * @method static \WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface video()
 * @method static \WebsiteLearners\AIAgent\AIAgent provider(string $name)
 *
 * @see \WebsiteLearners\AIAgent\AIAgent
 */
class AIAgent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \WebsiteLearners\AIAgent\AIAgent::class;
    }
}
