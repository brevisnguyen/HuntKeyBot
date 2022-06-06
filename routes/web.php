<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\WebhooksController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('webhooks/' . env('TELEGRAM_WEBHOOK_URL'), [TelegramController::class, 'process']);

Route::get('setWebhook/' . env('TELEGRAM_WEBHOOK_URL'), [WebhooksController::class, 'setWebhook']);
Route::get('removeWebhook/' . env('TELEGRAM_WEBHOOK_URL'), [WebhooksController::class, 'removeWebhook']);
