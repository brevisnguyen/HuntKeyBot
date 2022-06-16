<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Chat;
use App\Models\Issued;
use App\Models\Deposit;
use App\Models\WorkShift;
use App\Models\UserChat;

date_default_timezone_set('Asia/Manila');

class TransactionController extends Controller
{
    public function history(Request $request, $chat_id)
    {
        if ( $request->filled('day') && $request->day == 'yesterday') {
            $date = date('Y-m-d', strtotime('-1 days'));
            $link = '<a class="h4 mx-4" href="'. route('telegram.history', ['chat_id' => $chat_id]) . '">显示今天数据</a>';
            $exportUrl = '<a class="h4 mx-4" href="'. route('telegram.export', ['chat_id' => $chat_id]) . '/?day=yesterday">下载Excel数据</a>';
        } else {
            $date = date('Y-m-d', time());
            $link = '<a class="h4 mx-4" href="'. route('telegram.history', ['chat_id' => $chat_id]) . '/?day=yesterday">显示昨天数据</a>';
            $exportUrl = '<a class="h4 mx-4" href="'. route('telegram.export', ['chat_id' => $chat_id]) . '">下载Excel数据</a>';
        }

        $chat = Chat::find($chat_id);

        $shifts = Chat::find($chat_id)->work_shifts()
            ->whereDate('start_time', $date)
            ->get();

        $deposits = [];
        $deposits_amount = 0;
        $issueds = [];
        $issueds_amount = 0;
        $rate = 0;
        foreach ( $shifts as $shift ) {
            $list_deposit = $shift->deposits()->with('user')->with('work_shift')->get();
            $list_issued = $shift->issueds()->with('user')->with('work_shift')->get();
            foreach ( $list_deposit as $deposit ) {
                array_push($deposits, $deposit);
                $deposits_amount += $deposit->amount;
            }
            foreach ( $list_issued as $issued ) {
                array_push($issueds, $issued);
                $issueds_amount += $issued->amount;
            }
            $rate = $shift->rate;
        }

        return view(
            'history',
            [
                'link'              => $link,
                'exportUrl'         => $exportUrl,
                'chat'              => $chat,
                'deposits'          => $deposits,
                'deposits_amount'   => $deposits_amount,
                'rate'              => $rate,
                'issueds'           => $issueds,
                'issueds_amount'    => $issueds_amount,
            ]
        );
    }
}
