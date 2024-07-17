<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ExpiredDaysToExpiredAtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:update-expired-at';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expired days to expired at';

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
        $this->info('started');
        DB::table('contracts')
            ->whereIn('status', [1,3,4,9])
            ->where([['expired_days','>',0],['expired_at', null]])
            ->orderBy('id')
            ->update(['expired_at' => DB::raw('DATE_SUB(NOW(), INTERVAL expired_days DAY)')]);
        $this->info('finished');
        return 1;
    }
}
