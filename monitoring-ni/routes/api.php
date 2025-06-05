<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WordpressUpdateController;

Route::post('/site-updates', [WordpressUpdateController::class, 'store'])->name('api.site.updates');
