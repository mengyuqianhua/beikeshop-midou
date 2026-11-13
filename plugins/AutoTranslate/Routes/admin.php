<?php

use Illuminate\Support\Facades\Route;
use Plugin\AutoTranslate\Controllers\TranslateController;

Route::post('auto_translate/translate', [TranslateController::class, 'translate'])
    ->middleware('throttle:60,1')
    ->name('auto_translate.translate');

Route::get('auto_translate/asset', [TranslateController::class, 'asset'])
    ->name('auto_translate.asset');
