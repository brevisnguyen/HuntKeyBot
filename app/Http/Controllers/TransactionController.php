<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Maatwebsite\Excel\Excel;
use App\Exports\MultiSheetExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

date_default_timezone_set('Asia/Manila');

class TransactionController extends Controller
{
    private $excel;
    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request  $request, $chat_id)
    {
        $datetime = date('Y-m-d');
        if ( $request->has('date') && $request->input('date') != '' ) {
            $datetime = $request->input('date');
        }

        $chat = Chat::find($chat_id);
        if ( is_null($chat) ) {
            abort(404, '这不是您要查找的页面');
        }

        $from = $datetime . ' 00:00:00';
        $to = $datetime . ' 23:59:59';

        $deposits = DB::table('shifts')->where('chat_id', $chat->id)
            ->whereBetween('shifts.created_at', [$from, $to])
            ->join('deposits', 'shifts.id', '=', 'deposits.shift_id')
            ->join('users', 'users.id', '=', 'deposits.user_id')
            ->select('deposits.*', 'shifts.rate', 'users.first_name');
            
        $issueds = DB::table('shifts')->where('chat_id', $chat->id)
            ->whereBetween('shifts.created_at', [$from, $to])
            ->join('issueds', 'shifts.id', '=', 'issueds.shift_id')
            ->join('users', 'users.id', '=', 'issueds.user_id')
            ->select('issueds.*', 'shifts.rate', 'users.first_name');
            
        $sum_issued = $issueds->sum('issueds.amount');
        $deposit_total_gross = $deposits->sum('deposits.gross');
        $deposit_total_net = $deposits->sum('deposits.net');

        $shift = DB::table('shifts')->where('chat_id', $chat->id)->orderByDesc('id')->first();

        $param = '/telegram/chats/' . $chat->id . '?date=' . $datetime;

        return view('telegram.index', [
            'count_deposit' => $deposits->count(),
            'count_issued' => $issueds->count(),
            'deposits' => $deposits->paginate(25, ['*'], 'depositPage')->withPath($param),
            'issueds' => $issueds->paginate(25, ['*'], 'issuedPage')->withPath($param),
            'chat_id' => $chat_id,
            'deposit_gross' => $deposit_total_gross,
            'deposit_net' => $deposit_total_net,
            'sum_issued' => $sum_issued,
            'rate' => $shift->rate,
            'date' => $datetime
        ]);
    }

    /**
     * Export a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request, $chat_id)
    {
        $date = date('Y-m-d');
        if ( $request->has('date') && $request->input('date') != '' ) {
            $date = $request->input('date');
        }

        $chat = Chat::findOrFail($chat_id);

        $file_name = $chat->title . date_create_from_format('Y-m-d', $date)->format('Ymd');

        return $this->excel->download(new MultiSheetExport($date, $chat->id), $file_name . '.xlsx');
    }
}
