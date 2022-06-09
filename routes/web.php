<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\WebhooksController;
use App\Http\Controllers\TransactionController;

use App\Models\User;
use App\Models\Chat;
use App\Models\Issued;
use App\Models\Deposit;
use App\Models\WorkShift;
use App\Models\UserChat;

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

Route::get('history/{chat_id}/{d?}/', [TransactionController::class, 'history'])->name('telegram.history');

Route::get('debug/', function() {
    $chat_id = -1001656586591;
    $shifts = Chat::find($chat_id)->work_shifts()
        ->whereBetween('start_time', ['2022-06-08 00:00:00', '2022-06-08 23:59:59'])
        ->get();
    $deposits = [];
    $deposit_count = 0;
    foreach ($shifts as $shift) {
        # code...
        array_push($deposits, $shift->deposits()->get());
        $deposit_count += $shift->loadCount('deposits')->deposits_count;
    }
    $a = array_map(function($items) {
        $sum = 0;
        $b = array_map(function($item) {
            $sum = 0;
            return $sum += $item['amount'];
        }, $items->toArray());
        return $b;
    }, $deposits);
    dd($a);
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
    // $a = $chat->users();
    // dd($a);

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

    // $deposit = \App\Models\Deposit::find(2)->user;
    // dd($deposit->username);

    // $chat = \App\Models\Chat::find(21);
    // $chat->work_shifts();

    // $chat = \App\Models\Chat::find(1);
    // $a = $chat->users()->sync(['user_1' => ['role' => 'admin']] );
    // dd($a);
    // if ($a['updated']) {
    //     dd('gan ok');
    // } else {
    //     dd('trung lap gan');
    // }

//     $deposits = \App\Models\WorkShift::find(1)->deposits()->latest()->get();
//     $issueds = \App\Models\WorkShift::find(1)->issueds()->latest()->get();

//     $total_deposit = count($deposits);
//     $total_issued = count($issueds);

//     $amount_deposit = 0;
//     $text_deposit = '<b>Deposit ('. $total_deposit .') :</b>' . '
// ';
//     foreach ($deposits as $key => $deposit) {
//         $amount_deposit += $deposit->amount;
//         if ( $key <= 4 ) {
//             $text_deposit .= '<code>' . $deposit->created_at . '</code> : <b>' . $deposit->amount . '</b>' . '
// ';
//         }
//     }

//     $amount_issued = 0;
//     $text_issued = '<b>Deposit ('. $total_issued .') :</b>' . '
// ';
//     foreach ($issueds as $key => $issued) {
//         $amount_issued += $issued->amount;
//         if ( $key <= 4 ) {
//             $text_issued .= '<code>' . $issued->created_at . '</code> : <b>' . $issued->amount . '</b>' . '
// ';
//         }
//     }

//     $not_issued = $amount_deposit - $amount_issued;
//     $text_statistic = '<b>Issued Available: ' . $amount_deposit . '</b>' . '
// ' . '<b>Total Issued: ' . $amount_issued .'</b>' . '
// ' . '<b>Not Issued: ' . $not_issued . '</b>';

//     $params = [
//         'chat_id'   => 5192927761,
//         // 'text'      => $text_deposit . $text_issued,
//         'text'      => $text_deposit . '
// ' . $text_issued . '
// ' . $text_statistic,
//         // 'text'      => '<b>bold</b>, <strong>bold</strong>
//         // <i>italic</i>, <em>italic</em>
//         // <u>underline</u>, <ins>underline</ins>
//         // <s>strikethrough</s>, <strike>strikethrough</strike>, <del>strikethrough</del>
//         // <span class="tg-spoiler">spoiler</span>, <tg-spoiler>spoiler</tg-spoiler>
//         // <b>bold <i>italic bold <s>italic bold strikethrough <span class="tg-spoiler">italic bold strikethrough spoiler</span></s> <u>underline italic bold</u></i> bold</b>
//         // <a href="http://www.example.com/">inline URL</a>
//         // <a href="tg://user?id=123456789">inline mention of a user</a>
//         // <code>inline fixed-width code</code>',
//         'parse_mode'    => 'HTML',
//     ];

    // $chat_id = 222;
    // $shift = \App\Models\Chat::find(222)->work_shifts()
    //     ->whereIsStart(true)
    //     ->whereIsEnd(false)
    //     ->first();
    // // dd($chat);
    // if ( $shift ) {
    //     $shift->is_end = true;
    //     $shift->stop_time = date("Y-m-d H:i:s", time());
    //     $shift->save();
    //     dd($shift);
    // } else {
    //     $shift = \App\Models\Chat::find(222)->work_shifts()->create([
    //         'is_start' => true,
    //         'is_stop' => false,
    //         'start_time' => date("Y-m-d H:i:s", time())
    //     ]);
    //     dd($shift->id);
    // }
    // $key = 'huntkey_bot_total_deposit_in_' . 1 . '_in_shift_id_' . 2;

    // Cache::forever($key, 12);

    // Cache::forget($key);

    // dd(Cache::get($key));

    // if ( Cache::has($key) ) {
        // $value = Cache::get($key) + 1;
        // Cache::put($key, $value);
        // dd(Cache::get($key));
    // } else {
    //     Cache::put($key, 1);
    //     dd(Cache::get($key));
    // }
});
