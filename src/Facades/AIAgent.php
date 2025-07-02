<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface text()
 * @method static \Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface image()
 * @method static \Kaviyarasu\AIAgent\Contracts\Services\VideoServiceInterface video()
 * @method static \Kaviyarasu\AIAgent\AIAgent provider(string $name)
 *
 * @see \Kaviyarasu\AIAgent\AIAgent
 */
class AIAgent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kaviyarasu\AIAgent\AIAgent::class;
    }
}
