<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram;

class WebhooksController extends Controller
{
    public function setWebhook(Request $request)
    {
        $params = [
            'url' => env('TELEGRAM_WEBHOOK_URL'),
            'drop_pending_updates' => true,
            'allowed_updates' => ["message", "callback_query"],
        ];
        $response = Telegram::bot()->setWebhook($params);
        dd($response);
    }

    public function removeWebhook()
    {
        $response = Telegram::bot()->removeWebhook();
        dd($response);
    }
}
