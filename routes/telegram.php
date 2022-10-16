<?php

use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Laravel\Http\Middleware\ValidateWebhook;
use Illuminate\Support\Str;
use Telegram\Bot\BotManager;

Route::group(
    [
        // 'domain' => config('telegram.webhook.domain'),
        'middleware' => ValidateWebhook::class,
        'prefix' => config('telegram.webhook.path')
    ], function() {

    Route::get('/hihi', function() {return 'ok';});

    Route::post('/{token}/{bot}', config('telegram.webhook.controller'))->name('telegram.bot.webhook');

});

Route::group(
    [
        'prefix' => config('telegram.webhook.path'),
        'middleware' => 'web',
    ],
    function() {
        Route::get('/setWebhook/{bot}', function($bot_name) {

            $bot = Telegram::bot($bot_name);

            $url = Str::replaceFirst('http', 'https', route('telegram.bot.webhook', [
                'token' => $bot->config('token'),
                'bot' => $bot->config('bot')
            ]));

            $params = [
                'url' => $url,
                'drop_pending_updates' => true,
                'allowed_updates' => ['message', 'edited_message', 'channel_post', 'edited_channel_post', 'inline_query', 'chosen_inline_result', 'chosen_inline_result', 'callback_query', 'my_chat_member', 'chat_member', 'chat_join_request']
            ];

            if ( $bot->setWebhook($params) ) {
                return $bot->getWebhookInfo();
            };

        })->name('telegram.setwebhook');

        Route::get('/removeWebhook/{bot}', function($bot_name) {

            $bot = Telegram::bot($bot_name);

            if ( $bot->removeWebhook() ) {
                return response()->json(['status' => 'OK', 'message' => 'Webhook removed successfully!']);
            };

        })->name('telegram.removewebhook');
    }
);
