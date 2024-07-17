<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LettersReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID отправителя',
            'Отправитель',
            'Должник',
            'Контракт',
            'Адрес',
            'Создан',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->sender->id,
            "{$invoice->sender->name} {$invoice->sender->surname} {$invoice->sender->patronymic}",
            "{$invoice->debtor->name} {$invoice->debtor->surname} {$invoice->debtor->patronymic}",
            $invoice->contract_id,
            "{$invoice->region()->first()->name}, {$invoice->area()->first()->name}",
            $invoice->created_at,
        ];
    }
}
