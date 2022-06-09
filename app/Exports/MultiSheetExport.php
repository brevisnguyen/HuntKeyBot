<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $date;
    protected $chat_id;

    public function __construct(String $date, int $chat_id)
    {
        $this->date = $date;
        $this->chat_id = $chat_id;
    }

    /**
    * @return array
    */
    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new DepositExport($this->date, $this->chat_id);
        $sheets[] = new IssuedExport($this->date, $this->chat_id);

        return $sheets;
    }
}
