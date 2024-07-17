<?php
namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class VerifiedExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents
{
    use Exportable;
    use Exportable;

    public function query()
    {
        return User::query();
    }


    public function map($user): array
    {

        return [
            $user->id,
            $user->fio,
            $user->phone,
            $user->statusText,
        ];

    }


    public function headings(): array
    {
        return [
            'ID Пользователя',
            'ФИО',
            'Телефон',
            'Статус',
        ];

    }


    public function registerEvents(): array
    {
        return [


            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:D1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                ]);


                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('AC1:D' . $rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);


                $event->sheet->getStyle('A1:D' . $rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('AC1:D' . $rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('A1:D1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ]
                ])->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('e6e6eb');;
                $event->sheet->setAutoFilter('A1:D1');

            }
        ];
    }


}

?>
