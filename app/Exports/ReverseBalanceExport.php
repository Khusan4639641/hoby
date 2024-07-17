<?php

namespace App\Exports;

use App\Services\resusBank\CanceledReceiptSync;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;


class ReverseBalanceExport implements FromArray,
    ShouldAutoSize,
    WithHeadings,
    WithStrictNullComparison,
    WithCustomStartCell,
    WithEvents
{
    use Exportable;

    private Carbon $date_from;
    private Carbon $date_to;
    private Builder $contract;
    private array $data = [];
    private $companies;

    public function __construct(
        Carbon $date_from,
        Carbon $date_to
    )
    {
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $syncer = app()->make(CanceledReceiptSync::class);
        $syncer->sync('lastMonth');
    }

    public function array(): array
    {
        config()->set('database.connections.mysql.strict', false);
        DB::reconnect();
        $query = DB::table('companies as main_company')
            ->select('main_company.id as company_id', 'main_company.inn as inn', 'name', DB::raw('SUM(dtp.sum) as debit_sum'), DB::raw('SUM(orders.sum) as credit_sum'), 'company_uniq_nums.uniq_num as uniq_num')
            ->leftJoin(DB::raw("(SELECT SUM(amount) as sum, company_id
                        FROM detail_payments
                        WHERE status = 01
                            AND payment_at BETWEEN '{$this->date_from}' AND '{$this->date_to}'
                        GROUP BY company_id) as dtp"), 'dtp.company_id', '=', 'main_company.id')
            ->leftJoin(DB::raw("(SELECT o.company_id, SUM(o.partner_total) as sum
                        FROM contracts c
                        LEFT JOIN orders o ON c.order_id = o.id
                        WHERE (c.status IN (1, 3, 4) OR (c.status = 9 AND c.cancel_reason IS NULL))
                            AND c.confirmed_at BETWEEN '{$this->date_from}' AND '{$this->date_to}'
                        GROUP BY o.company_id) as orders"), 'orders.company_id', '=', 'main_company.id')
            ->leftJoin('company_uniq_nums', 'main_company.id', '=', 'company_uniq_nums.company_id')
            ->where('main_company.general_company_id', 3)
            ->groupBy('main_company.inn');
        $results = $query->get();

        foreach ($results as $result) {
            $result->credit_sum = $result->credit_sum ?? 0;
            $result->debit_sum = $result->debit_sum ?? 0;
            $this->data[] = [
                $result->company_id,
                $result->uniq_num,
                $result->name,
                $result->inn,
                $this->numberFormat($result->debit_sum ?? 0),
                $this->numberFormat($result->credit_sum ?? 0),
                $this->numberFormat($result->credit_sum - $result->debit_sum > 0 ? 0 : abs($result->credit_sum - $result->debit_sum)),
                $this->numberFormat($result->credit_sum - $result->debit_sum < 0 ? 0 : abs($result->credit_sum - $result->debit_sum)),
            ];
        }

        return $this->data;
    }

    public function headings(): array
    {
        return [
            "ID компании",
            "Номер договора",
            "Название",
            "Инн",
            "Дебет",
            "Кредит",
            "Дебет",
            "Кредит",
        ];
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function registerEvents(): array
    {
        $from = $this->date_from->format('d.m.Y');
        $to = $this->date_to->format('d.m.Y');

        return [
            AfterSheet::class => function (AfterSheet $event) use ($from, $to) {
                $lastPlusOneRow = count($this->data) + 6;
                $event->sheet->mergeCells('B2:F2');
                $event->sheet->setCellValue('B2', "Название отчета: Оборотно-сальдовая ведомость за {$from} - {$to}");
                $event->sheet->getStyle('B2')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle("D6:H$lastPlusOneRow")->getAlignment()->setHorizontal('right');
                $event->sheet->getStyle('B2')->getFont()->setBold(true);
                $event->sheet->mergeCells('A3:C4');
                $event->sheet->mergeCells('D3:E4');
                $event->sheet->setCellValue('D3', 'Обороты за переод');
                $event->sheet->mergeCells('F3:G4');
                $event->sheet->setCellValue('F3', 'Сальдо на конец переода');
                $event->sheet->getStyle('A5:H5')->getFont()->setBold(true);
                $event->sheet->getStyle('A3:H4')->getFont()->setBold(true);
                $event->sheet->getStyle('A1:H1')->getFont()->setBold(true);
                $event->sheet->setAutoFilter('A5:H5');
            }
        ];
    }

    private function numberFormat($number)
    {
        return number_format($number, 2, ',', '');
    }
}
