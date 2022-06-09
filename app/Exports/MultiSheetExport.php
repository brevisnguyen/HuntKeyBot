<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $date;

    public function __construct(String $date)
    {
        $this->date = $date;   
    }

    /**
    * @return array
    */
    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new DepositExport($this->date);
        $sheets[] = new IssuedExport($this->date);

        return $sheets;
    }
}
