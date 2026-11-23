<?php

declare(strict_types=1);

use App\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DemoController::class, 'show'])->name('demo.show');
Route::post('/send', [DemoController::class, 'send'])->name('demo.send');
