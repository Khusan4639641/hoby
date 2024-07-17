<?php

namespace App\Exports;

use App\Models\EdTransaction;
use App\Services\EdTransactionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


class EdTransactionExport implements FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithStrictNullComparison,
    WithCustomStartCell,
    WithEvents
{
    use Exportable;

    private Carbon $date_from;
    private Carbon $date_to;
    private string $type;

    public function __construct(
        Carbon $date_from,
        Carbon $date_to,
        string $type = 'all'
    )
    {
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->type = $type;
    }

    public function query()
    {
        return EdTransaction::query()
            ->select([
                'doc_time',
                'corr_account',
                'corr_name',
                'doc_id',
                'type',
                'amount',
                'purpose_of_payment',
                'cash_symbol',
                'corr_inn'
            ])
            ->whereBetween('doc_time', [
                $this->date_from->startOfDay()->getTimestampMs(),
                $this->date_to->endOfDay()->getTimestampMs()
            ])
            ->when($this->type != 'all', function (Builder $builder) {
                $builder->when($this->type == 'credit', function (Builder $builder) {
                    $builder->where('type', 'CREDIT');
                })->when($this->type == 'debit', function (Builder $builder) {
                    $builder->where('type', 'DEBIT');
                });
            })->orderBy('doc_time');
    }


    public function headings(): array
    {
        return [
            "Дата документа",
            "Счёт",
            "Наименование",
            "Номер документа",
            "Оборот Дебет",
            "Оборот кредит",
            "Назначение платежа",
            "Кассовый символ",
            "ИНН"
        ];
    }

    public function map($row): array
    {
        $doc_date = Carbon::createFromTimestampMs($row->doc_time)->format('d.m.Y H:i');
        $debit_amount = ' - ';
        $credit_amount = ' - ';
        if ($row->type == 'CREDIT') {
            $credit_amount = number_format($row->amount / 100, 2, ',', ' ');
        } elseif ($row->type == 'DEBIT') {
            $debit_amount = number_format($row->amount / 100, 2, ',', ' ');
        }
        return [
            $doc_date,
            $row->corr_account,
            $row->corr_name,
            $row->doc_id,
            $debit_amount,
            $credit_amount,
            $row->purpose_of_payment,
            $row->cash_symbol,
            $row->corr_inn
        ];
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        $from = $this->date_from->format('d.m.Y');
        $to = $this->date_to->format('d.m.Y');

        $balance_before = EdTransactionService::balanceBetweenPeriod(
            Carbon::createFromTimestampMs(0),
            $this->date_from->startOfDay()
        );
        $balance_after = EdTransactionService::balanceBetweenPeriod(
            Carbon::createFromTimestampMs(0),
            $this->date_to->endOfDay()
        );


        $balance_before = number_format($balance_before / 100, 2, ',', ' ');
        $balance_after = number_format($balance_after / 100, 2, ',', ' ');
        return [
            AfterSheet::class => function (AfterSheet $event) use ($balance_before, $balance_after, $from, $to) {
                $event->sheet->mergeCells('A1:C1');
                $event->sheet->mergeCells('A2:C2');
                $event->sheet->mergeCells('A3:C3');
                $event->sheet->mergeCells('A4:C4');

                $event->sheet->setCellValue('A1', "Данный по Электронным деньгам (выписка за период)");
                $event->sheet->setCellValue('A2', "Справка о работе счета за {$from} - {$to}");
                $event->sheet->setCellValue('A3', "Счет: 20208000505290124001  АО  \"Solutions Lab\"");
                $event->sheet->setCellValue('A4', "Остаток на начало периода    {$balance_before}      Остаток на конец  периода      {$balance_after}");


                $event->sheet->getStyle('E')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $event->sheet->getStyle('F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $event->sheet->getStyle('A6:I6')->getFont()->setBold(true);
                $event->sheet->getStyle('A6:I6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        ];
    }
}
