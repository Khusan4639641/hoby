<?php

namespace App\Console\Commands;

use App\Models\UzTax;
use Illuminate\Console\Command;

use App\Http\Controllers\V3\UzTaxCancelController;

class CancelUzTaxQR extends Command
{

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'uztax:cancel';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command description';

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
    $cancel = new UzTaxCancelController();
    $cancel->index();
    return 0;
  }
}
