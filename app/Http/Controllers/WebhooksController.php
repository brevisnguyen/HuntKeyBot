<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram;

class WebhooksController extends Controller
{
    public function setWebhook(Request $request)
    {
        if ( $request->has('webhookurl')) {
            $params = [
                'url' => $request->webhookurl,
                'drop_pending_updates' => $request->drop_pending_updates ?? true,
                'allowed_updates' => $request->allowed_updates ?? ["message", "callback_query"],
            ];
            $response = Telegram::bot()->setWebhook($params);
            dd($response);
        }

        abort(403);
    }

    public function removeWebhook()
    {
        $response = Telegram::bot()->removeWebhook();
        dd($response);
    }
}
