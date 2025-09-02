<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EncodingQueueController;
use App\Http\Controllers\Api\VideoController;

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
});
