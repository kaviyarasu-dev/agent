<?php

use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/blog')->group(function () {
    Route::post('/generate', [BlogController::class, 'generateBlogPost']);
    Route::post('/generate-alt', [BlogController::class, 'generateBlogPostAlt']);
});