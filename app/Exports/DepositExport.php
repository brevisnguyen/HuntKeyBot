<?php

namespace App\Exports;

use App\Models\Deposit;
use App\Models\Chat;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;

class DepositExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    protected $date;
    protected $chat_id;

    public function __construct(String $date, int $chat_id)
    {
        $this->date = $date;
        $this->chat_id = $chat_id;
    }

    public function query()
    {
        $deposits = Chat::find($this->chat_id)
            ->deposits()
            ->whereDate('created_at', $this->date)
            ->with('user');
        return $deposits;
    }

    public function map($deposit): array
    {
        return [
            $deposit->id,
            $deposit->user->username,
            $deposit->amount,
            $deposit->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            '操作人',
            '金额',
            '时间',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_CYAN],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK],
                    ],
                ],
            ],
            'A' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            'C' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            'D' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 25,
            'D' => 40,
        ];
    }

    public function title(): string
    {
        return '入款';
    }
}
