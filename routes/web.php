<?php

use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\MailController;;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;



require __DIR__.'/auth.php';


Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/send-mail', [MailController::class, 'sendEmail']);

Route::prefix('auth')->group(function () {
    Route::get('/google', [GoogleAuthController::class, 'redirectToGoogle']);
});
