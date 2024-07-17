<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\CompanyUniqNum;
use App\Models\Contract;
use App\Models\DetailPayment;
use App\Models\EdTransaction;
use App\Services\resusBank\CanceledReceiptSync;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;


class ComparativeDocumentExport implements
    ShouldAutoSize,
    WithHeadings,
    WithStrictNullComparison,
    WithCustomStartCell,
    WithEvents,
    FromArray
{
    use Exportable;

    private Carbon $date_from;
    private Carbon $date_to;
    private Builder $contract;
    private $companies;
    private array $data;
    private $total_money_back = 0;
    private $total_paid = 0;
    private $total_sale = 0;
    private string $inn;

    public function __construct(
        Carbon $date_from,
        Carbon $date_to,
        string $inn
    )
    {
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->inn = $inn;
        $syncer = app()->make(CanceledReceiptSync::class);
        $syncer->sync('lastMonth');
    }

    public function array(): array
    {
        $this->data = [];

        $this->companies = Company::select(['id', 'inn', 'name', 'general_company_id', 'parent_id'])
            ->where('inn', $this->inn)->get();

        $generalCompanies = $this->companies->where('general_company_id', 3);

        $contracts = Contract::query()
            ->selectRaw("confirmed_at, ($this->inn) as inn, company_id, status,
            (SELECT partner_total from orders where id = contracts.order_id) as contract_total, id,
                                CASE
                                    WHEN status = 1 THEN 'В рассрочке'
                                    WHEN status = 3 THEN 'В рассрочке'
                                    WHEN status = 4 THEN 'В рассрочке'
                                    WHEN status = 5 THEN 'Отменен'
                                    WHEN status = 9 and cancel_reason is null  THEN 'Закрыт'
                                    WHEN status = 9 and cancel_reason is not null  THEN 'Закрыт MFO'
                                    ELSE '' END AS contract_status, canceled_at")
            ->whereIn('company_id', $generalCompanies->pluck('id'))
            ->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_60_DAYS, Contract::STATUS_OVERDUE_30_DAYS, Contract::STATUS_CANCELED, Contract::STATUS_COMPLETED])
            ->whereBetween('created_at', [$this->date_from, $this->date_to])
            ->get();

        $detailPayments = DetailPayment::query()
            ->select(['created_at', 'company_id', 'amount'])
            ->whereIn('company_id', $this->companies->pluck('id'))
            ->where('status', DetailPayment::SUCCESS_STATUS)
            ->whereBetween('created_at', [$this->date_from, $this->date_to])
            ->get();

        $edTransactions = EdTransaction::where('type', 'CREDIT')
            ->select(['doc_time', 'amount'])
            ->where('corr_inn', $this->inn)
            ->whereBetween('doc_time', [$this->date_from->getTimestampMs(), $this->date_to->getTimestampMs()])
            ->get();

        $uniq_num = CompanyUniqNum::where('company_id', $generalCompanies->where('parent_id', null)->first()->id)->first();

        foreach ($contracts as $contract) {
            if ($contract->status === 5 || $contract->status === 9 && $contract->cancel_reason === null) {
                $this->data[] = [
                    'date' => $contract->confirmed_at,
                    'inn' => $this->inn,
                    'company_id' => $contract->company_id,
                    'uniq_num' => $uniq_num->uniq_num,
                    'money_back' => '',
                    'paid' => '',
                    'sale' => number_format($contract->contract_total ?? 0, 0, ',', ''),
                    "should_pay" => '',
                    "over_pay" => '',
                    'contract_id' => $contract->id,
                    'contract_status' => $contract->contract_status,
                    'canceled_at' => $contract->canceled_at
                ];
                $contract->contract_total *= -1;
            } else {
                $this->total_sale += $contract->contract_total;
            }

            $this->data[] = [
                'date' => $contract->confirmed_at,
                'inn' => $this->inn,
                'company_id' => $contract->company_id,
                'uniq_num' => $uniq_num->uniq_num,
                'money_back' => '',
                'paid' => '',
                'sale' => number_format($contract->contract_total ?? 0, 0, ',', ''),
                "should_pay" => '',
                "over_pay" => '',
                'contract_id' => $contract->id,
                'contract_status' => $contract->contract_status,
                'canceled_at' => $contract->canceled_at
            ];
        }
        foreach ($detailPayments as $detailPayment) {
            $this->total_paid += $detailPayment->amount;
            $this->data[] = [
                'date' => $detailPayment->created_at->format('d.m.Y'),
                'inn' => $this->inn,
                'company_id' => $detailPayment->company_id,
                'uniq_num' => $uniq_num->uniq_num,
                'money_back' => '',
                'paid' => number_format($detailPayment->amount ?? 0, 0, ',', ''),
                'sale' => '',
                "should_pay" => '',
                "over_pay" => '',
                'contract_id' => '',
                'contract_status' => '',
                'canceled_at' => ''
            ];
        }
        foreach ($edTransactions as $edTransaction) {
            $this->total_money_back = $edTransaction->amount / 100;
            $this->data[] = [
                'date' => Carbon::createFromTimestampMs($edTransaction->doc_time)->format('d.m.Y'),
                'inn' => $this->inn,
                'company_id' => '',
                'uniq_num' => $uniq_num->uniq_num,
                'money_back' => number_format($edTransaction->amount / 100 ?? 0, 0, ',', ''),
                'paid' => '',
                'sale' => '',
                "should_pay" => '',
                "over_pay" => '',
                'contract_id' => '',
                'contract_status' => '',
                'canceled_at' => ''
            ];
        }
        return $this->data;
    }

    public function headings(): array
    {
        return [
            "Дата",
            "Инн",
            "ID компании",
            "Номер договора",
            "Возврат денег",
            "Оплаты",
            "Продажи",
            "К оплате",
            "Переплата",
            "ID кредита",
            "Статус кредита",
            "Дата отмены"
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
                $lastRow = $lastPlusOneRow - 1;
                $event->sheet->mergeCells('B2:G2');
                $event->sheet->mergeCells('B3:G3');
                $event->sheet->getStyle('A5:L5')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('B2')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('B3')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle("A6:L{$lastRow}")->getAlignment()->setHorizontal('right');
                $event->sheet->setCellValue('B2', "СОЛИШТИРМА ДАЛОЛАТНОМА  за {$from} - {$to}");
                $event->sheet->setCellValue('B3', $this->companies[0]->name);
                $event->sheet->getStyle('B2:G2')->getFont()->setBold(true);
                $event->sheet->getStyle('A5:L5')->getFont()->setBold(true);
                $event->sheet->setCellValue('A' . ($lastPlusOneRow), 'Итого');
                $event->sheet->setCellValue('E' . ($lastPlusOneRow), "=SUBTOTAL(109,E6:E$lastRow)");
                $event->sheet->setCellValue('F' . ($lastPlusOneRow), "=SUBTOTAL(109,F6:F$lastRow)");
                $event->sheet->setCellValue('G' . ($lastPlusOneRow), "=SUBTOTAL(109,G6:G$lastRow)");
                $event->sheet->setCellValue('I' . ($lastPlusOneRow), "=IF(F$lastPlusOneRow - G$lastPlusOneRow > 0, F$lastPlusOneRow - G$lastPlusOneRow, 0)");
                $event->sheet->setCellValue('H' . ($lastPlusOneRow), "=IF(F$lastPlusOneRow - G$lastPlusOneRow < 0, G$lastPlusOneRow - F$lastPlusOneRow, 0)");
                $event->sheet->getStyle("A$lastPlusOneRow:L$lastPlusOneRow")->getFont()->setBold(true);
                $event->sheet->getStyle("A5:L$lastPlusOneRow")->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                $event->sheet->setAutoFilter('A5:L5');

            }
        ];
    }
}
