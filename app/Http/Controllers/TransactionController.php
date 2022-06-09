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
            $fromDate = date('Y-m-d', strtotime('-1 days'));
            $toDate = date('Y-m-d', strtotime('-1 days'));
            $link = '<a class="h4 mx-4" href="'. route('telegram.history', ['chat_id' => $chat_id]) . '">Dữ liệu hôm nay</a>';
            $exportUrl = '<a class="h4 mx-4" href="'. route('telegram.export', ['chat_id' => $chat_id]) . '/?day=yesterday">Xuất Excel</a>';
        } else {
            $fromDate = date('Y-m-d', time());
            $toDate = date('Y-m-d', time());
            $link = '<a class="h4 mx-4" href="'. route('telegram.history', ['chat_id' => $chat_id]) . '/?day=yesterday">Dữ liệu hôm qua</a>';
            $exportUrl = '<a class="h4 mx-4" href="'. route('telegram.export', ['chat_id' => $chat_id]) . '">Xuất Excel</a>';
        }

        $chat = Chat::find($chat_id);

        $shifts = Chat::find($chat_id)->work_shifts()
            ->whereBetween('start_time', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->get();

        $deposits_per_shift = [];
        $deposit_count = 0;
        $deposit_amount = 0;
        $issueds_per_shift = [];
        $issued_count = 0;
        $issued_amount = 0;

        foreach ($shifts as $shift) {
            array_push($deposits_per_shift, $shift->deposits()->get());
            $deposit_count += $shift->loadCount('deposits')->deposits_count;
        }
        foreach ($shifts as $shift) {
            array_push($issueds_per_shift, $shift->issueds()->get());
            $issued_count += $shift->loadCount('issueds')->issueds_count;
        }

        foreach ($deposits_per_shift as $deposits) {
            foreach ($deposits as $deposit) {
                $deposit_amount += $deposit->amount;
            }
        }
        foreach ($issueds_per_shift as $issueds) {
            foreach ($issueds as $issued) {
                $issued_amount += $issued->amount;
            }
        }

        return view(
            'history',
            [
                'link'               => $link,
                'exportUrl'          => $exportUrl,
                'chat'               => $chat,
                'deposit_count'      => $deposit_count,
                'issued_count'       => $issued_count,
                'deposits_per_shift' => $deposits_per_shift,
                'issueds_per_shift'  => $issueds_per_shift,
                'deposit_amount'     => $deposit_amount,
                'issued_amount'      => $issued_amount,
            ]
        );
    }
}
