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

Route::post('webhooks/' . env('TELEGRAM_WEBHOOK_URL'), [TelegramController::class, 'process']);
Route::get('webhooks/' . env('TELEGRAM_WEBHOOK_URL'), [TelegramController::class, 'process']);

Route::get('setWebhook/' . env('TELEGRAM_WEBHOOK_URL'), [WebhooksController::class, 'setWebhook']);
Route::get('removeWebhook/' . env('TELEGRAM_WEBHOOK_URL'), [WebhooksController::class, 'removeWebhook']);

Route::get('debug/', function() {

    // \App\Models\Chat::create([
    //     'id' => 333,
    //     'type' => 'group',
    //     'title' => 'chat 2',
    //     'username' => 'chat_2',
    // ]);

    // \App\Models\User::create([
    //     'id' => 222,
    //     'first_name' => '2',
    //     'last_name' => 'user',
    //     'username' => 'user_2',
    // ]);

    // $chat = \App\Models\Chat::first();
    // $user = \App\Models\User::find(222);
    // $chat->users()->attach($user->username, ['role' => 'guest']);

    // $user = new \App\Models\User([
    //     'id' => 444,
	// 	'username' => 'user_4',
	// 	'first_name' => 'user',
	// 	'last_name' => '4',
    // ]);
    // $user->save();
    // $chat = \App\Models\Chat::find(333);
    // $chat->users()->attach('hhihi', ['role' => 'operator']);

    // $user = \App\Models\User::create([
    //     'id' => 666,
    //     'username' => 'vaicalon',
    //     'first_name' => 'sau',
    //     'last_name' => 'sau',
    // ]);
    // $user = \App\Models\User::find(666);
    // $role = \App\Models\UserChat::whereChatId(333)->whereUsername('vaicalon')->first();
    // dd($role->role);
    

    // $user = \App\Models\User::find('user_4');
    // dd($user->id);

    // $user = \App\Models\User::find(222);
    // $chats = \App\Models\Chat::with('users')->get();
    // dd($chats);

    // $user = \App\Models\User::whereUsername('user_4')->first()->deposits;
    // $user->deposits()->create([
    //     'user_id' => $user->id,
    //     'shift_id' => 1,
    //     'amount' => 500,
    //     'created_at' => date('Y-m-d H:i:s', time()),
    // ]);
    // dd($user);

    $deposit = \App\Models\Deposit::find(2)->user;
    dd($deposit->username);

});
