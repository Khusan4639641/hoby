<?php

namespace App\Console\Commands;

use App\Helpers\LetterHelper;
use App\Models\Letter;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Illuminate\Console\Command;

class CheckLettersStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'letters:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check letters status and update';

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
        $client = new Client([
            'base_uri' => 'https://hybrid.pochta.uz/',
            'headers'  => [
                'Authorization' => 'Bearer ' . LetterHelper::getBearerToken(),
                'Accept'        => 'application/pdf'
            ],
            'verify'   => false,
            'on_stats' => function (TransferStats $stats) {
                //echo $stats->getEffectiveUri() . "\n";
                echo $stats->getTransferTime() . "\n";
                //var_dump($stats->getHandlerStats());
            }
        ]);


        try {

            $letters = Letter::whereStatus(0)->orderByDesc('id')->get();

            $promises = (function () use ($letters, $client) {
                foreach ($letters as $item) {
                    $uri = 'https://hybrid.pochta.uz/api/Receipt?Id=' . $item->response['Id'];
                    yield $client->getAsync($uri)
                        ->then(function (Response $response) use ($item) {
                            return [$item, $response];
                        },
                            function ($reason) use ($item) {
                                if ($item->created_at < Carbon::now()->subDay()) {
                                    $item->status = 2;
                                    $item->save();
                                } else{
                                    \Log::channel('letters')->warning($reason->getMessage());
                                }
                            });
                }
            })();

            $eachPromise = new EachPromise($promises, [
                'concurrency' => 50,
                'fulfilled'   => function ($response) {
                    $letter = $response[0];
                    if ($response[1]->getStatusCode() === 200) {
                        $letter->status = 1;
                        $letter->save();
                    } else {
                        \Log::channel('letters')->warning('status ' . $response->getStatusCode(), [
                            'letter_id' => $letter->id,
                            'response'  => $response]);
                    }
                }
            ]);

            $eachPromise->promise()->wait();

        } catch (\Throwable $throwable) {
            \Log::channel('letters')->warning($throwable->getMessage());
        }

        return 'Ended';
    }
}
