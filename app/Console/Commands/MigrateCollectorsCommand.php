<?php

namespace App\Console\Commands;

use App\Models\Collector;
use App\Models\CollectorTransaction;
use App\Models\Contract;
use App\Models\DebtCollect\DebtCollectContractResult;
use App\Models\DebtCollect\DebtCollectDebtorAction;
use App\Models\DebtCollect\DebtCollector;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateCollectorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collectors:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate collectors to new db structure';

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
        $collectors = Collector::all();

        DB::transaction(function () use($collectors){
            foreach ($collectors as $collector) {
                $role = Role::whereName('debt-collector')->first();
                $collector->user()->update(['role_id' => $role->id]);
                $debt_collector = DebtCollector::find($collector->user_id);

                foreach ($collector->contracts()->get() as $contract) {
                    $debt_collector->contracts()->attach([$contract->id  => [
                        'created_at' => $contract->pivot->created_at
                    ]]);

                    $debt_collector->debtors()->attach($contract->user_id);

                    $rate = 0;
                    $expired_days = $contract->expired_days - Carbon::parse($contract->pivot->created_at)->diffInDays();
                    if($expired_days >= 200) {
                        $rate = 5;
                    } else if($expired_days >= 140) {
                        $rate = 4;
                    } else if($expired_days >= 90) {
                        $rate = 3;
                    } else {
                        $rate = 2;
                    }

                    $total_amount = Payment::where([
                        ['status', 1],
                        ['contract_id', $contract->id]
                    ])->whereBetween('created_at', [$contract->pivot->created_at, Carbon::parse($contract->pivot->created_at)->endOfMonth()])->sum('amount');

                    DebtCollectContractResult::create([
                        'collector_id' => $debt_collector->id,
                        'contract_id' => $contract->id,
                        'period_start_at' => $contract->pivot->created_at,
                        'rate' => $rate,
                        'period_end_at' => Carbon::parse($contract->pivot->created_at)->endOfMonth(),
                        'total_amount' => $total_amount,
                        'amount' => $total_amount + ($total_amount / 100) * $rate,
                    ]);

                    foreach (CollectorTransaction::whereCollectorContractId($contract->pivot->id)->get() as $trans) {
                        $debt_collector->debtor_actions()->create([
                            'debtor_id' => Contract::find($contract->id)->buyer()->first()->id,
                            'type' => $trans->type,
                            'content' => $trans->content,
                            'created_at' => $trans->created_at
                        ]);
                    }
                }
            }
        });

    }
}
