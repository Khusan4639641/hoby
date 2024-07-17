<?php

namespace App\Console\Commands;

use App\Http\Controllers\Web\Frontend\MigrateController;
use Illuminate\Console\Command;

class ImportAction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:action {action} {--arg=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migration data from system';

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
     * @return mixed
     */
    public function handle()
    {
        //
        $this->info('Migration start!');
        $action = $this->argument('action');
        $arg = $this->option('arg');
        switch($action){
            case 'migrate':
                $this->migrate();
                break;
            case 'users':
                $this->users($arg);
                break;
            case 'contracts':
                $this->contracts($arg);
                break;
            case 'vendors':
                $this->vendors($arg);
                break;
            case 'correct':
                $this->correct();
                break;
            case 'clear-contracts':
                $this->clearContracts();
                break;
            case 'clear':
                $this->clear();
                break;
        }
        $this->info('---------------------------------');
        $this->info('Migration complete!');
        return 0;
    }

    private function migrate(){
        $migrate = new MigrateController();
        $migrate->migrate();
    }

    private function users($arg){
        $this->info('Buyers migration start!');
        $migrate = new MigrateController();
        $migrate->migrateBuyers();
        $this->info('Buyers migration complete!');
    }

    private function contracts($arg){
        $this->info('Credits migration start!');
        $migrate = new MigrateController();
        $migrate->migrateContracts();
        $this->info('Credits migration complete!');
    }

    private function vendors($arg){
        $this->info('Vendors migration start!');
        $migrate = new MigrateController();

        $migrate->migrateVendors($arg[0]?? null);
        $this->info('Vendors migration complete!');
    }

    private function clear(){
        $migrate = new MigrateController();
        $migrate->clear();
    }
    private function correct(){
        $migrate = new MigrateController();
        $migrate->correct();
    }
    private function clearContracts(){
        $migrate = new MigrateController();
        $migrate->clearOrders();
    }
}
