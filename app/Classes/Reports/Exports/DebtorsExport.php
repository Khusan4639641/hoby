<?php

namespace App\Classes\Reports\Exports;

use App\Models\Contract;
use App\Helpers\EncryptHelper;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Jobs\AppendRowsToFile;

class DebtorsExport
{
    public $limit = 1000;
    public $offset;

    public function query($count = false, $offset = false, $limit = false) {

        $subQuery = DB::table('contracts')
            ->selectRaw('contracts.user_id')
            ->selectRaw('users.phone AS buyer_phone')
            ->selectRaw('CONCAT(TRIM(users.name), " ", TRIM(users.surname), " ", TRIM(users.patronymic)) AS buyer_FIO')
            ->selectRaw('users.gender as buyer_gender')
            ->selectRaw('CASE users.gender WHEN 2 THEN \'Ж\' ELSE \'М\' END AS gender_caption')
            ->selectRaw('TIMESTAMPDIFF(YEAR, users.birth_date, CURDATE()) AS buyer_age')
            ->selectRaw('(
                        SELECT region_name FROM katm_regions
                        WHERE katm_regions.region = users.region AND katm_regions.local_region = users.local_region
                        ) as buyer_region_nameru')
            ->selectRaw('(
                        SELECT local_region_name FROM katm_regions
                        WHERE katm_regions.region = users.region AND katm_regions.local_region = users.local_region
                        ) as buyer_local_region_name')
            ->selectRaw("(
                        SELECT GROUP_CONCAT(CONCAT(
                            type, ': ', address
                            ) SEPARATOR '\n') FROM buyer_addresses
                        WHERE buyer_addresses.user_id = contracts.user_id
                        ) as buyer_addresses")
            ->selectRaw("(
                        SELECT GROUP_CONCAT(DISTINCT(CONCAT(name, ' ', phone)) SEPARATOR '\n') FROM buyer_guarants
                        WHERE buyer_guarants.user_id = contracts.user_id
                        GROUP BY user_id
                        ) as buyer_guarants")
            ->selectRaw('contracts.id')
            ->selectRaw('DATE_FORMAT(contracts.created_at, \'%d.%m.%Y\') AS created_at')
            ->selectRaw('contracts.total')
            ->selectRaw('contracts.expired_days')
            ->selectRaw('(
                        SELECT pinfl FROM buyer_personals
                        WHERE buyer_personals.user_id = users.id
                        LIMIT 1
                        ) as buyer_pinfl')
            ->selectRaw('(
                        SELECT passport_number FROM buyer_personals
                        WHERE buyer_personals.user_id = users.id
                        LIMIT 1
                        ) as buyer_passport_number')
            ->selectRaw('(
                        SELECT SUM(balance) FROM contract_payments_schedule
                        WHERE status = 0 AND contract_id = contracts.id
                        ) as debt_amount')
            ->leftJoin('users', 'users.id', '=', 'contracts.user_id')
            ->whereRaw('contracts.expired_days > 60');

        if ($offset) $subQuery->offset($offset);
        if ($limit) $subQuery->limit($limit);
        if ($count) return $subQuery->count();

        $query = DB::table(DB::raw("({$subQuery->toSql()}) AS t"))
            ->selectRaw('t.user_id')
            ->selectRaw('t.buyer_phone')
            ->selectRaw('t.buyer_FIO')
            ->selectRaw('t.gender_caption')
            ->selectRaw('t.buyer_age')
            ->selectRaw('t.buyer_region_nameru')
            ->selectRaw('t.buyer_local_region_name')
            ->selectRaw('t.buyer_addresses')
            ->selectRaw('t.buyer_guarants')
            ->selectRaw('t.id')
            ->selectRaw('t.created_at')
            ->selectRaw('t.total')
            ->selectRaw('t.debt_amount')
            ->selectRaw('t.expired_days')
            ->selectRaw('t.buyer_pinfl')
            ->selectRaw('t.buyer_passport_number');

        return $query;
    }

    public function getHeadings()
    {
        return [
            'ID покупателя',
            'Телефон',
            'Покупатель',
            'Пол',
            'Возраст',
            'Область',
            'Район',
            "Адрес",
            'Доверители',
            'Номер договора',
            'Дата создания договора',
            'Общая сумма контракта',
            'Сумма задолженности',
            'Количество дней задолженности',
            'ПИНФЛ',
            'Серия и номер паспорта',
        ];
    }
}
