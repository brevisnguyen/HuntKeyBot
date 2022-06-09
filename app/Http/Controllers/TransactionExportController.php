<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use App\Exports\MultiSheetExport;
use App\Models\Chat;

date_default_timezone_set('Asia/Manila');

class TransactionExportController extends Controller
{
    private $excel;
    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    public function export(Request $request, $chat_id)
    {
        if ( $request->filled('day') && $request->day == 'yesterday' ) {
            $date = date('Y-m-d', strtotime('-1 days'));
        } else {
            $date = date('Y-m-d', time());
        }

        $chat = Chat::find($chat_id);

        return $this->excel->download(new MultiSheetExport($date, $chat->id), $chat->title . '_' . date('Ymd_His', time()) . '.xlsx');
    }
}
