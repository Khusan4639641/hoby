<?php

namespace App\Exports;

use App\Helpers\EncryptHelper;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class DebtorsByDistrictExport extends DefaultValueBinder implements FromQuery, WithMapping, WithHeadings, WithCustomValueBinder, ShouldAutoSize
{
    use Exportable;

    private int $district_id;
    private int $collector_id;

    public function __construct(int $district_id, int $collector_id)
    {
        $this->district_id = $district_id;
        $this->collector_id = $collector_id;
    }

    public function query()
    {
        return DB::table('contracts as c')->selectRaw("c.user_id,
        c.id,
        CONCAT(u.name, ' ', u.surname)                                 as name,
        bp.passport_number,
        u.phone,
        ba.address,
        c.confirmed_at,
        c.expired_days                                                 as expired_days,
        CASE
            WHEN c.status = 1
                THEN 'В рассрочке'
            WHEN c.status in(3,4)
                THEN 'Просрочен'
            WHEN c.status = 9
                THEN 'Закрыт'
            ELSE c.status
            END                                                        as status,
        c.total,
        c.total - c.balance                                            as total_balance,
        c.balance                                                      as debit,
        COALESCE(cc.balance, 0)                                        as notorius_debit,
        COALESCE(adh.balance, 0)                                       as autopay_debit,
        c.balance + COALESCE(cc.balance, 0) + COALESCE(adh.balance, 0) as total_debt")
            ->join('users as u', 'u.id', 'c.user_id')
            ->join('buyer_personals as bp', 'bp.user_id', 'u.id')
            ->join('buyer_addresses as ba', 'ba.user_id', 'u.id')
            ->join('debt_collector_contract as dcc', 'dcc.contract_id', 'c.id')
            ->join('debt_collector_district as dcdis', 'dcdis.collector_id', 'dcc.collector_id')
            ->join('districts as d', 'd.id', 'dcdis.district_id')
            ->leftJoin('collect_cost as cc', 'cc.contract_id', 'c.id')
            ->leftJoin('autopay_debit_history as adh', 'adh.contract_id', 'c.id')
            ->whereRaw("ba.`type` = 'registration'
        and bp.created_at = (select min(tempbp.created_at) from buyer_personals tempbp where tempbp.user_id = ba.user_id)
        and dcdis.collector_id = $this->collector_id
        and dcdis.district_id = $this->district_id
        and u.local_region = d.cbu_id
        and dcc.deleted_at is NULL
        and dcdis.deleted_at is NULL")
            ->whereIn('c.status', [1, 3, 4, 9])
            ->orderBy('user_id');
    }

    public function map($invoice): array
    {
        return [
            $invoice->user_id,
            $invoice->name,
            EncryptHelper::decryptData($invoice->passport_number),
            (string)$invoice->phone,
            $invoice->address,
            $invoice->id,
            $invoice->confirmed_at,
            (string)$invoice->expired_days,
            $invoice->status,
            $invoice->total,
            $invoice->total_balance,
            $invoice->debit,
            $invoice->notorius_debit,
            $invoice->autopay_debit,
            $invoice->total_debt
        ];
    }

    public function headings(): array
    {
        return [
            [
                'ID Пользователя',
                'Имя Фамилия',
                'Паспорт(серия номер)',
                'Телефон',
                'Адрес',
                'Контракт',
                'дата',
                'Просрочено дней',
                'Статус',
                'Сумма договора',
                'Выплачено',
                'Задолженность по договору',
                'Расходы за взыскание',
                'Расходы за нотариуса',
                'Суммарная задолженность'
            ]
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING2);

            return true;
        }

        // else return default behavior
        return parent::bindValue($cell, $value);
    }
}
