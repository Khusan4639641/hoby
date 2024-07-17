<?php
namespace App\Exports;

use App\Models\OldUser;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class OldUsersExport implements
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
        return OldUser::query();
    }


    public function map($user): array
    {

        return [
            $user->id,
            $user->username,
            $user->lastname,
            $user->patronymic,
            $user->inn,
            $user->pnfl,
            $user->address
        ];

    }


    public function headings(): array
    {
        return [
            'ID Пользователя',
            'Имя',
            'Фамилия',
            'Отчество',
            'ИНН',
            'ПНФЛ',
            'Адрес',
        ];

    }


    public function registerEvents(): array
    {
        return [


            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                ]);


                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('AC1:G' . $rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);


                $event->sheet->getStyle('A1:G' . $rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('AC1:G' . $rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('A1:G1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ]
                ])->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('e6e6eb');;
                $event->sheet->setAutoFilter('A1:G1');

            }
        ];
    }


}

?>
