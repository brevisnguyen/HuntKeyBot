<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\WebhooksController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionExportController;

use App\Models\User;
use App\Models\Chat;
use App\Models\Issued;
use App\Models\Deposit;
use App\Models\WorkShift;
use App\Models\UserChat;

use function PHPSTORM_META\type;

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

Route::post('webhooks/' . env('TELEGRAM_BOT_KEY'), [TelegramController::class, 'process']);
Route::get('updates/' . env('TELEGRAM_BOT_KEY'), [TelegramController::class, 'process']);

Route::get('setWebhook/', [WebhooksController::class, 'setWebhook']);
Route::get('removeWebhook/', [WebhooksController::class, 'removeWebhook']);

Route::get('history/{chat_id}/', [TransactionController::class, 'history'])->name('telegram.history');

Route::get('history/{chat_id}/export/', [TransactionExportController::class, 'export'])->name('telegram.export');
