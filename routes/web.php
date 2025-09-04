<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\IndexController;

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::get('/video/{id}/{slug}', [VideoController::class, 'show'])
    ->whereNumber('id')
    ->name('video.show');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories');

Route::get('/test', [TestController::class, 'index'])->name('test.index');

Route::get('/{page}/', [IndexController::class, 'index'])
    ->whereNumber('page');
Route::get(
    '/{page}',
    fn(int $page) =>
    redirect()->route('index', ['page' => $page], 301)
)->whereNumber('page');
