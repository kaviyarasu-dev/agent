<?php

namespace VendorName\Skeleton\Facades;

use Illuminate\Support\Facades\Facade;
use WebsiteLearners\AI\AI;

/**
 * @see \WebsiteLearners\AI\AI
 */
class Skeleton extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AI::class;
    }
}
