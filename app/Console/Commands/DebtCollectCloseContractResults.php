<?php

namespace App\Console\Commands;

use App\Models\DebtCollect\DebtCollectContractResult;
use App\Scopes\DebtCollectScope;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebtCollectCloseContractResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtCollect:closeContractResults {month?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixing debt-collect contract results on start of month';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $month_argument = $this->argument('month');
        if($month_argument) {
            try {
                $month = Carbon::createFromFormat('Y-m', $this->argument('month'));
            } catch (\Exception $e) {
                $this->info('Неверный формат даты');
                return 0;
            }
        } else {
            $month = Carbon::now()->subMonth();
        }

        $start_of_month = $month->clone()->startOfMonth();
        $end_of_month = $month->clone()->endOfMonth();
        $this->info("Фиксация за: {$end_of_month->format('m.Y')}");

        $contract_results = DebtCollectContractResult::with(['contract', 'collector' => function($query) {
            $query->withoutGlobalScope(DebtCollectScope::class);
        }])->whereBetween('period_start_at', [$start_of_month, $end_of_month])->get();

        DB::transaction(function () use($contract_results, $start_of_month, $end_of_month) {
            foreach ($contract_results as $cri => $contract_result) {
                $collector = $contract_result->collector;
                $contract = $contract_result->contract;

                $this->line("{$cri}. {$collector->id}: {$collector->full_name}");
                $this->line("  Контракт №{$contract->id}");
                $this->line("  Результат №{$contract_result->id}:");
                $this->line("    Начало: {$contract_result->period_start_at}");
                $this->line("    Ставка: {$contract_result->rate}%");

                if(!$contract_result->period_end_at) {
                    $contract_result->period_end_at = $end_of_month;
                }
                $this->question("    Конец: {$contract_result->period_end_at}");

                $total_amount = 0;
                $payments = $contract_result->payments()->get();

                $this->line("    Платежи:");
                foreach ($payments as $payment) {
                    $total_amount += $payment->amount;
                    $this->info("      - {$payment->amount} сум от {$payment->created_at}");
                }

                $contract_result->total_amount = round($total_amount, 2);
                $this->question("    Общая сумма: {$contract_result->total_amount} сум");

                $collector_remuneration = round($contract_result->total_amount * ($contract_result->rate / 100), 2);

                $contract_result->amount = round($collector_remuneration, 2);
                $this->question("    Сумма вознагражения: {$contract_result->amount} сум");

                $contract_result->save();
                $this->line(' ');
            }
        });
    }
}
