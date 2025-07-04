<?php

it('ensures contracts are interfaces')
    ->expect('Kaviyarasu\AIAgent\Contracts')
    ->toBeInterfaces();

it('ensures services implement their contracts')
    ->expect('Kaviyarasu\AIAgent\Services\Core\TextService')
    ->toImplement('Kaviyarasu\AIAgent\Contracts\Services\TextServiceInterface')
    ->and('Kaviyarasu\AIAgent\Services\Core\ImageService')
    ->toImplement('Kaviyarasu\AIAgent\Contracts\Services\ImageServiceInterface')
    ->and('Kaviyarasu\AIAgent\Services\Core\VideoService')
    ->toImplement('Kaviyarasu\AIAgent\Contracts\Services\VideoServiceInterface');

it('follows naming conventions for services')
    ->expect('Kaviyarasu\AIAgent\Services')
    ->classes()
    ->toHaveSuffix('Service');

it('follows naming conventions for providers')
    ->expect('Kaviyarasu\AIAgent\Providers\AI')
    ->classes()
    ->toHaveSuffix('Provider');

it('ensures providers extend abstract provider')
    ->expect('Kaviyarasu\AIAgent\Providers\AI')
    ->classes()
    ->toExtend('Kaviyarasu\AIAgent\Providers\AI\AbstractProvider')
    ->ignoring('Kaviyarasu\AIAgent\Providers\AI\AbstractProvider');

it('uses strict types')
    ->expect('Kaviyarasu\AIAgent')
    ->toUseStrictTypes();

it('avoids die and dd')
    ->expect(['die', 'dd', 'dump', 'ray'])
    ->not->toBeUsed();
