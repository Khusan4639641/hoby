<?php

namespace App\Console\Commands;

use App\Helpers\CategoryHelper;
use App\Models\OrderProduct;
use App\Models\UzTax;
use App\Models\UzTaxError;
use App\Traits\UzTaxTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateIncorrectPsicCodes extends Command
{
    use UzTaxTrait;

    const PHONE_CATEGORY_PSIC_CODE = '08517001001000000';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:incorrect-psic-codes {startDate?} {endDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to update incorrect psic codes (status = 700 in uz_tax_errors table) in order_products table';

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
        $errors = UzTaxError::query()
            ->where('error_code', [UzTax::OFD_INCORRECT_PSIC_CODE])
            ->when($this->argument('startDate') !== null, function ($subQuery) {
                $subQuery->where('created_at', '>=', $this->argument('startDate'));
            })
            ->when($this->argument('endDate') !== null, function ($subQuery) {
                $subQuery->where('created_at', '<=', $this->argument('endDate'));
            });
        $this->output->progressStart($errors->count());
        $errors = $errors->get();

        for ($i = 0; $i < count($errors); $i++, $this->output->progressAdvance()) {
            if (!isset($errors[$i]->orderProducts)) {
                continue;
            }
            foreach ($errors[$i]->orderProducts as $product) {

                if (isset($product->category) && CategoryHelper::isPhone($product->category->id)) {
                    DB::transaction(function () use ($product, $errors, $i) {
                        OrderProduct::where('id', $product->id)->update([
                            'psic_code' => self::PHONE_CATEGORY_PSIC_CODE,
                        ]);
                        UzTaxError::where('id', $errors[$i]->id)->update([
                            'error_code' => UzTax::OFD_SERVER_ERROR,
                        ]);
                    });
                }

            }
        }
        $this->output->progressFinish();

        return self::SUCCESS;
    }
}
