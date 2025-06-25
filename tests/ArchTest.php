<?php

use WebsiteLearners\AIAgent\Contracts;
use WebsiteLearners\AIAgent\Providers;
use WebsiteLearners\AIAgent\Services;

it('ensures contracts are interfaces')
    ->expect('WebsiteLearners\AIAgent\Contracts')
    ->toBeInterfaces();

it('ensures services implement their contracts')
    ->expect('WebsiteLearners\AIAgent\Services\Core\TextService')
    ->toImplement('WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface')
    ->and('WebsiteLearners\AIAgent\Services\Core\ImageService')
    ->toImplement('WebsiteLearners\AIAgent\Contracts\Services\ImageServiceInterface')
    ->and('WebsiteLearners\AIAgent\Services\Core\VideoService')
    ->toImplement('WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface');

it('follows naming conventions for services')
    ->expect('WebsiteLearners\AIAgent\Services')
    ->classes()
    ->toHaveSuffix('Service');

it('follows naming conventions for providers')
    ->expect('WebsiteLearners\AIAgent\Providers\AI')
    ->classes()
    ->toHaveSuffix('Provider');

it('ensures providers extend abstract provider')
    ->expect('WebsiteLearners\AIAgent\Providers\AI')
    ->classes()
    ->toExtend('WebsiteLearners\AIAgent\Providers\AI\AbstractProvider')
    ->ignoring('WebsiteLearners\AIAgent\Providers\AI\AbstractProvider');

it('uses strict types')
    ->expect('WebsiteLearners\AIAgent')
    ->toUseStrictTypes();

it('avoids die and dd')
    ->expect(['die', 'dd', 'dump', 'ray'])
    ->not->toBeUsed();
