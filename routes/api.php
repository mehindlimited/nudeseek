<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EncodingQueueController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\VideoPublisherController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\CategoryApiController;

Route::prefix('encoding-queue')->group(function () {
    Route::get('next-pending', [EncodingQueueController::class, 'getNextPending']);
    Route::post('{videoCode}/processing', [EncodingQueueController::class, 'markAsProcessing']);
    Route::post('{videoCode}/completed', [EncodingQueueController::class, 'markAsCompleted']);
    Route::post('{videoCode}/failed', [EncodingQueueController::class, 'markAsFailed']);
    Route::post('{videoCode}/retry', [EncodingQueueController::class, 'retryJob']);
    Route::post('reset-stuck', [EncodingQueueController::class, 'resetStuckJobs']);
    Route::get('{videoCode}/status', [EncodingQueueController::class, 'getStatus']);
    Route::get('stats', [EncodingQueueController::class, 'getStats']);
});

Route::prefix('videos')->group(function () {
    Route::post('{videoCode}/activate', [VideoController::class, 'activate']);
    Route::post('{videoCode}/metadata', [VideoController::class, 'updateMetadata']);
    Route::post('/', [VideoPublisherController::class, 'store']);  // Create video
    Route::post('{videoCode}/after-create', [VideoPublisherController::class, 'triggerAfterCreate']);
});

Route::get('users/random-for-target/{target_id}', [UserApiController::class, 'getRandomForTarget']);
Route::get('categories/by-legacy/{legacy}', [CategoryApiController::class, 'getByLegacy']);

Route::prefix('categories')->group(function () {
    Route::get('by-legacy/{legacy}', [CategoryApiController::class, 'getByLegacy']);
    Route::get('for-target/{target_id}', [CategoryApiController::class, 'getCategoriesForTarget']);
    Route::get('random-for-target/{target_id}', [CategoryApiController::class, 'getRandomForTarget']);
    Route::get('search', [CategoryApiController::class, 'searchCategories']);
    Route::get('without-legacy', [CategoryApiController::class, 'getCategoriesWithoutLegacy']);
    Route::post('bulk-lookup', [CategoryApiController::class, 'bulkLookupByLegacy']);
    Route::post('create-or-update', [CategoryApiController::class, 'createOrUpdateWithLegacy']);
    Route::get('stats/mapping', [CategoryApiController::class, 'getCategoryMappingStats']);
});
