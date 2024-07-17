<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Company;

class CompanyUniqNumRelationSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'companies:uniq-num-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize companies uniq num into relation';

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
        $this->info('Synchronizing companies uniq nums. This might take a while...');

        $companies = Company::all();

        $i = 0;
        $count = count($companies);

        foreach ($companies as $company)
        {
            $company->currentUniqNum()->updateOrCreate(['general_company_id' => $company->general_company_id], [
                'uniq_num' => $company->uniq_num
            ]);

            $i++;

            // PHPUnit-style feedback
            $this->output->write('.');
        }

        $this->info("\n$i out of $count companies have been synced");
    }
}
