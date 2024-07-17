<?php

namespace App\Exports;

use App\Helpers\EncryptHelper;
use App\Models\Buyer;
use App\Models\Payment;
use App\Models\SellerBonus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;


class BonusClientsExport implements
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
        $row = Buyer::first();
        $datesFromTo = report_filter($row);

        $subQuery = Buyer::query()
            ->with('personals')
            ->has('personals')
/*pass_num*/   ->selectRaw('(SELECT buyer_personals.passport_number FROM buyer_personals WHERE buyer_personals.user_id = users.id LIMIT 1) AS passport_number')
/*pinfl*/   ->selectRaw('(SELECT buyer_personals.pinfl FROM buyer_personals WHERE buyer_personals.user_id = users.id LIMIT 1) AS pinfl')
            ->selectSub(function ($query) use ($datesFromTo) {
                $query->from('seller_bonuses')
/*receipt*/         ->selectRaw('IFNULL(SUM(seller_bonuses.amount), 0) AS receipt')
                    ->whereRaw('seller_bonuses.seller_id = users.id')
                    ->whereRaw('seller_bonuses.status = 1')
                    ->whereRaw('seller_bonuses.updated_at BETWEEN \'' . $datesFromTo['date_from'] . '\' AND \'' . $datesFromTo['date_to'] . '\'');
/*receipt*/ }, 'receipt')
            ->selectSub(function ($query) use ($datesFromTo) {
                $query->from('payments')
/*debit*/           ->selectRaw('IFNULL(SUM(payments.amount), 0) AS debit')
                    ->whereRaw('payments.user_id = users.id')
                    ->whereRaw('payments.status = 1')
                    ->whereRaw('(payments.type = \'upay\' OR payments.type = \'A2C\' OR payments.type = \'refund\')')
                    ->whereRaw('payments.payment_system = \'Paycoin\'')
                    ->whereRaw('payments.created_at BETWEEN \'' . $datesFromTo['date_from'] . '\' AND \'' . $datesFromTo['date_to'] . '\'');
/*debit*/   }, 'debit')
            //->selectRaw('users.id') //ненужное поле, закомментил Нурлан 24.05.2022 ветка dev_nurlan_new_feature_add_new_fields_to_bonuses_report
/*user_id*/ ->selectRaw('users.id AS user_id')
/*fio*/     ->selectRaw('CONCAT(users.name, \' \', users.surname, \' \', users.patronymic) AS fio')
            ->whereRaw('users.id in (select distinct seller_id from seller_bonuses)')

/*company_name*/ ->selectRaw('companies.name AS company_name')
/*gen_compan*/   ->selectRaw('general_companies.name_ru AS general_company')
/*company_name*/ ->leftJoin('companies', 'users.seller_company_id', '=', 'companies.id')
/*gen_compan*/   ->leftJoin('general_companies', 'general_companies.id', '=', 'companies.general_company_id');

        $query = DB::table(
            DB::raw("({$subQuery->toSql()}) AS t")
        )
            ->selectRaw('t.*')
            ->selectRaw('(t.receipt + t.debit) AS different');            /*different*/

        return $query->get();
    }

    public function map($userBonus): array
    {
        try {
            return [
                $userBonus->user_id,
                $userBonus->fio,
                EncryptHelper::decryptData($userBonus->passport_number),
                EncryptHelper::decryptData($userBonus->pinfl),
                number_format($userBonus->receipt, 2, ',', ' '),
                number_format($userBonus->debit, 2, ',', ' '),
                number_format($userBonus->different, 2, ',', ' '),
                $userBonus->company_name,
                $userBonus->general_company
            ];
        } catch (\Exception $e) {
            Log::channel('errors')->info('ERROR bonusClients');
            Log::channel('errors')->info($e);
        }
        return [];
    }

    public function headings(): array
    {
        return [
            'ID клиента',
            'Фио',
            'Паспорт',
            'ПИНФЛ',
            'Сумма',
            'Расход',
            'Разница (Сумма - Расход)',
            'Компания',
            'Торговая компания (на рус.)'
        ];

    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $rows = $event->sheet->getDelegate()->getHighestRow();
                $event->sheet->getStyle('A2:I' . $rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
                    ]
                ]);

                $event->sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
                    ]
                ])->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('e6e6eb');
                $event->sheet->setAutoFilter('A1:I1');
            }
        ];
    }
}
