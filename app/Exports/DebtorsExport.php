<?php

namespace App\Exports;

use App\Helpers\EncryptHelper;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\KatmRegion;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DebtorsExport implements
    FromQuery,
    //FromCollection,
    ShouldAutoSize,
    WithMapping,
    WithHeadings
{
    use Exportable;

    public function query($data = []) {

        $query = Contract::where('expired_days', '>', 60);

        return $query;
    }

    /*public function collection()
    {
        return Contract::query()->where('expired_days', '>', 60)->cursor();
    }*/

    public function map($contract): array {

        if(isset($contract->buyer) && isset($contract)) {
            $buyer = $contract->buyer;
            // Область/Район
            $region = KatmRegion::where(['region' => $buyer->region, 'local_region' => $buyer->local_region])->first();

            // Адрес
            $addresses = '';
            foreach($buyer->addresses as $address) {
                if(isset($address->address))
                    $addresses .= "$address->type: $address->address    ";
            }

            // Доверители
            $trusters = '';
            $i = 1;

            foreach ($buyer->guarants as $guarant) {
                $trusters .= "$i. $guarant->name $guarant->phone    ";
                $i++;
            }

            $buyerFullName = "$buyer->name $buyer->surname $buyer->patronymic";

            // Сумма задолженности
            $debtAmount = ContractPaymentsSchedule::where(['contract_id' => $contract->id, 'status' => 0])->pluck('balance')->sum();

            return [
                $contract->user_id ?? '', // ID покупателя
                $buyer->phone ?? '', // Телефон
                $buyerFullName ?? '', // Покупатель
                $buyer->gender == 2 ? 'Ж' : 'М', // Пол
                Carbon::parse($buyer->birth_date)->diff(Carbon::now())->y, // Возраст
                $region->region_nameru ?? '', // Область
                $region->local_region_name ?? '', // Район
                $addresses ?? '', // Адрес
                $trusters ?? '', // Доверители
                $contract->id ?? '', // Номер договора
                $contract->created_at ?? '', // Дата создания договора
                $contract->total ?? '', // Общая сумма контракта
                $debtAmount ?? '', // Сумма задолженности
                $contract->expired_days ?? '', // Количество дней задолженности
                $buyer->personals && $buyer->personals->pinfl && EncryptHelper::decryptData($buyer->personals->pinfl) ? EncryptHelper::decryptData($buyer->personals->pinfl) : '', // ПИНФЛ
                $buyer->personals && $buyer->personals->passport_number && EncryptHelper::decryptData($buyer->personals->passport_number) ? EncryptHelper::decryptData($buyer->personals->passport_number) : '', // Серия и номер паспорта
            ];
        }
        return [];
    }

    public function headings(): array
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
