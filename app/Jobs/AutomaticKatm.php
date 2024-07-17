<?php

namespace App\Jobs;

use App\Http\Controllers\Core\KatmController;
use App\Models\KatmScoring;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutomaticKatm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //

        Log::channel('jobs')->info('start automatic.katm test');

        return true;

        Log::channel('jobs')->info('start automatic.katm.start');
        $katmScoring = new KatmScoring();
        $katm = $katmScoring->where('status', 0)->orderBy('id', 'desc')->get();
        foreach ($katm as $item) {
            $katmController = new KatmController();
            $request = request();
            $request->merge(['user' => $item->buyer]);
            $result = $katmController->checkAndUpdateScoring($request);
            Log::channel('jobs')->info(var_export($result,1));
        }

        Log::channel('jobs')->info('start automatic.katm.end');
    }
}
