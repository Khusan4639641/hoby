<?php

namespace App\Exports;

use App\Helpers\EncryptHelper;
use App\Models\SellerBonus;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;


class BonusExport implements
    FromCollection,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        $query = SellerBonus::with('buyer','buyer.personals')
            ->has('buyer')
            ->has('buyer.personals')
            ->where('status',1);

        report_filter($query);

        return $query->get();

    }

    public function map($bonus): array{

        Log::channel('errors')->info($bonus);

            return [
                $bonus->buyer->user->id,
                $bonus->buyer->user->fio,
                EncryptHelper::decryptData($bonus->buyer->personals->passport_number),
                EncryptHelper::decryptData($bonus->buyer->personals->pinfl),
                $bonus->contract_id,
                str_replace('.',',',$bonus->amount),
                $bonus->created_at
            ];
    }

    public function headings(): array
    {
        return [
            'ID клиента',
            'Фио',
            'Паспорт',
            'ПИНФЛ',
            'ID контракта',
            'Сумма',
            'Дата создания',
        ];

    }


    public function registerEvents(): array
    {
        return [


            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                ]);


                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('AC1:G'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);


                $event->sheet->getStyle('A1:G'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('AC1:G'.$rows)->applyFromArray([
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
