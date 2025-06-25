<?php

declare(strict_types=1);

use WebsiteLearners\AIAgent\AIAgent;
use WebsiteLearners\AIAgent\Contracts\Services\ImageServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface;
use WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface;
use WebsiteLearners\AIAgent\Factory\ServiceFactory;

it('can create text service', function () {
    $serviceFactory = Mockery::mock(ServiceFactory::class);
    $textService = Mockery::mock(TextServiceInterface::class);

    $serviceFactory->shouldReceive('createTextService')
        ->once()
        ->andReturn($textService);

    $aiAgent = new AIAgent($serviceFactory);

    expect($aiAgent->text())->toBe($textService);
});

it('can create image service', function () {
    $serviceFactory = Mockery::mock(ServiceFactory::class);
    $imageService = Mockery::mock(ImageServiceInterface::class);

    $serviceFactory->shouldReceive('createImageService')
        ->once()
        ->andReturn($imageService);

    $aiAgent = new AIAgent($serviceFactory);

    expect($aiAgent->image())->toBe($imageService);
});

it('can create video service', function () {
    $serviceFactory = Mockery::mock(ServiceFactory::class);
    $videoService = Mockery::mock(VideoServiceInterface::class);

    $serviceFactory->shouldReceive('createVideoService')
        ->once()
        ->andReturn($videoService);

    $aiAgent = new AIAgent($serviceFactory);

    expect($aiAgent->video())->toBe($videoService);
});

it('can switch providers', function () {
    $serviceFactory = Mockery::mock(ServiceFactory::class);

    $serviceFactory->shouldReceive('setDefaultProvider')
        ->once()
        ->with('openai');

    $aiAgent = new AIAgent($serviceFactory);
    $result = $aiAgent->provider('openai');

    expect($result)->toBe($aiAgent);
});
