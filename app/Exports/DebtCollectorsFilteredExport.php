<?php
namespace App\Exports;

use App\Enums\ExcelReportsNumberFormatsEnum;
use App\Helpers\EncryptHelper;
use App\Helpers\QueryHelper;
use App\Models\BuyerAddress;
use App\Models\Contract;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DebtCollectorsFilteredExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithColumnFormatting,
    WithColumnWidths
{
    use Exportable;

    public function __construct(
        $recovery = null,
        $contractDateFrom = null,
        $contractDateTo = null,
        int $delayDaysFrom = null,
        int $delayDaysTo = null,
        int $contractBalanceFrom = null,
        int $contractBalanceTo = null,
        int $katmRegion = null
    )
    {
        $this->queryHelper = new QueryHelper(new Contract);
        $recovery = json_decode($recovery);
        $params = [
            'contracts.recovery' => $recovery,
            'contracts.created_at__moe' => $contractDateFrom ? $contractDateFrom . ' 00:00:00' : null,
            'contracts.created_at__loe' => $contractDateTo ? $contractDateTo . ' 23:59:59' : null,
            'contracts.expired_days__moe' => $delayDaysFrom,
            'contracts.expired_days__loe' => $delayDaysTo,
            'contracts.total__moe' => $contractBalanceFrom,
            'contracts.total__loe' => $contractBalanceTo,
            'user|region' => $katmRegion,
        ];
        switch ($recovery) {
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
                $params['contracts.status'] = [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_60_DAYS, Contract::STATUS_OVERDUE_30_DAYS];
                break;
            case 7:
                $params['contracts.status'] = Contract::STATUS_COMPLETED;
                $params['contracts.recovery'] = [1,2,3,4,5,6,7];
                break;
            case [2,3,4,5,6,7]:
                $params['contracts.status'] = [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_60_DAYS, Contract::STATUS_OVERDUE_30_DAYS, Contract::STATUS_COMPLETED];
                break;
        }
        $this->query = $this->queryHelper->constructQuery($params);
    }

    public function query()
    {
        return $this->query
            ->leftjoin('users', 'contracts.user_id', 'users.id')
            ->leftjoin('collect_cost as cc', function ($q) {
                $q->on('contracts.id', 'cc.contract_id')
                    ->where('cc.id', function ($q) {
                        $q->select(DB::raw('(select max(id) from collect_cost where cc.contract_id = contracts.id)'));
                    });
            })
            ->leftjoin('autopay_debit_history as adh', function ($q) {
                $q->on('contracts.id', 'adh.contract_id')
                    ->where('adh.status', 0);
            })
            ->leftjoin('buyer_personals', 'contracts.user_id', 'buyer_personals.user_id')
            ->leftjoin('buyer_addresses', function ($q) {
                $q->on('contracts.user_id', 'buyer_addresses.user_id')
                    ->where('type', BuyerAddress::TYPE_REGISTRATION);
            })
            ->leftjoin('katm_regions', function($q) {
                $q->on('katm_regions.region', 'users.region')
                    ->on('katm_regions.local_region', 'users.local_region');
            })
            ->with('buyer.guarants')
            ->select(
                'users.id as user_id',
                'users.phone',
                'buyer_personals.passport_number',
                'buyer_personals.pinfl',
                DB::raw('CONCAT(users.name, " ", users.surname, " ", users.patronymic) AS fio'),
                DB::raw('(CASE WHEN users.gender = 1 THEN "М" WHEN users.gender = 2 THEN "Ж" ELSE "Не указано" END) AS gender'),
                DB::raw('DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(), birth_date)), "%Y") + 0 as age'),
                'katm_regions.region_name',
                'katm_regions.local_region_name',
                'buyer_addresses.address',
                DB::raw('(SELECT min(name) from buyer_guarants where buyer_guarants.user_id = contracts.user_id) as guarant_name'),
                'contracts.id as contract_id',
                DB::raw("CAST(contracts.created_at as DATE) as contract_created_at"),
                'contracts.total as contract_total',
                DB::raw('(select sum(balance) from contract_payments_schedule cps where contracts.id = cps.contract_id and cps.status = 0 and cps.payment_date < NOW()) as debts'),
                'contracts.expired_days as contract_expired_days',
                'cc.balance as collect_cost_amount',
                'adh.balance as autopay_debit_history_balance',
            );
    }

    public function map($data): array
    {
        return [
            $data->user_id,
            $data->phone,
            EncryptHelper::decryptData($data->passport_number),
            EncryptHelper::decryptData($data->pinfl),
            $data->fio,
            $data->gender,
            $data->age,
            $data->region_name,
            $data->local_region_name,
            $data->address,
            $data->guarant_name,
            $data->contract_id,
            $data->contract_created_at,
            $data->contract_total,
            $data->debts,
            $data->contract_expired_days,
            $data->collect_cost_amount,
            $data->autopay_debit_history_balance,
        ];
    }

    public function headings(): array
    {
        return [
            'ID покупателя',
            'Номер телефона',
            'Паспорт',
            'ПИНФЛ',
            'Покупатель',
            'Пол',
            'Возраст',
            'Область',
            'Район',
            'Адрес',
            'Доверитель',
            'Номер договора',
            'Дата создания договора',
            'Общая сумма контракта',
            'Сумма задолженности',
            'Количество дней задолженности',
            'Долг за взыскание',
            'Долг за автопей',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER,
            'B' => ExcelReportsNumberFormatsEnum::EXCEL_FORMAT_NUMBER_UZBEKISTAN_PHONE_NUMBER
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 17,
            'C' => 10,
            'D' => 15,
            'E' => 11,
            'F' => 10,
            'G' => 8,
            'H' => 8,
            'I' => 7,
            'J' => 7,
            'K' => 11,
            'L' => 15,
            'M' => 22,
            'N' => 22,
            'O' => 20,
            'P' => 29,
            'Q' => 17,
            'R' => 15,
        ];
    }
}

?>
