<?php

namespace App\Console\Commands;

use App\Models\Collector;
use App\Models\CollectorTransaction;
use App\Models\Contract;
use App\Models\DebtCollect\DebtCollectContract;
use App\Models\DebtCollect\DebtCollectContractResult;
use App\Models\DebtCollect\DebtCollector;
use App\Models\Payment;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebtCollectCreateContractResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtCollect:createContractResults {month?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create debt-collect contract results on start of month';

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
            $month = Carbon::now();
        }

        $start_of_month = $month->startOfMonth();
        $this->info("Фиксация за: {$start_of_month->format('m.Y')}");

        $collectors = DebtCollector::all();

        DB::transaction(function () use($collectors, $start_of_month) {
            foreach($collectors as $ci => $collector) {
                $this->line("{$ci}. {$collector->id}: {$collector->full_name}");
                $contracts = $collector->contracts()->whereIn('status', [1, 3, 4, 9])
                    ->where('contracts.created_at', '<', $start_of_month)
                    ->get();
                foreach ($contracts as $cci => $contract) {
                    $this->info("  {$cci}. Контракт №{$contract->id}:");

                    // TODO: Временное решение. Удалить после запуска команды на проде
                    $table = 'contracts';
                    if ($start_of_month->format('Y-m') === '2023-04') {
                        $table .= '_20230401';
                    }

                    $expired_days = DB::table($table)->find($contract->id)->expired_days;
                    // $expired_days = DebtCollectContract::find($contract->id)->expired_days;
                    $rate = 0;
                    if($expired_days >= 300) $rate = 7;
                    else if($expired_days >= 201) $rate = 5;
                    else if($expired_days >= 140) $rate = 4;
                    else $rate = 3;

                    $contract->debt_collect_results()->updateOrCreate(
                        [
                            'collector_id' => $collector->id,
                            'period_start_at' => $start_of_month,
                        ],
                        [
                            'rate' => $rate
                        ]
                    );
                    $this->info("    Статус: {$contract->status}");
                    $this->info("    Просрочка: {$expired_days} дней");
                    $this->info("    Ставка: {$rate}%");
                }
            }
        });
    }
}
