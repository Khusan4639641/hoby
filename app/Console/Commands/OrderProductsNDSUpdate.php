<?php

namespace App\Console\Commands;

use App\Helpers\NdsStopgagHelper;
use App\Models\OrderProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class OrderProductsNDSUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order-products-nds:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates and Updates original_price, total_nds, original_price_client, total_nds_client, used_nds_percent fields on table order_products.';

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
        DB::table('order_products')
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->join('companies', 'orders.company_id', '=', 'companies.id')
            ->join('partner_settings', 'partner_settings.company_id', '=', 'companies.id')
            ->join('general_companies', 'companies.general_company_id', '=', 'general_companies.id')
            ->select('order_products.*', 'general_companies.is_mfo', 'partner_settings.nds')
            ->where([
                ['order_products.original_price', '=', 0],
                ['order_products.total_nds', '=', 0],
                ['order_products.original_price_client', '=', 0],
                ['order_products.total_nds_client', '=', 0],
                ['order_products.used_nds_percent', '=', 0],
                ['order_products.status', '=', OrderProduct::STATUS_ACTIVE],
            ])
            ->orderBy('order_products.id')
            ->chunkById(20000, function (Collection $order_products) {
                foreach ($order_products as $order_product) {
                    $data = [
                        'original_price' => 0,
                        'total_nds' => 0,
                        'original_price_client' => 0,
                        'total_nds_client' => 0,
                        'used_nds_percent' => 0
                    ];
                    $nds = NdsStopgagHelper::getActualNdsPlusOne($order_product->created_at); // 1.15 or 1.12. В зависимости от даты(до 31.12.2022 15%, после 31.12.2022 12%)
                    $actualNds = NdsStopgagHelper::getActualNds($order_product->created_at); // 0.12 or 0.15. В зависимости от даты(до 31.12.2022 15%, после 31.12.2022 12%)
                    if ($order_product->is_mfo) {
                        $data['original_price'] = round($order_product->price_discount, 2);
                        $data['original_price_client'] = round($order_product->price, 2);
                    } else {
                        if ($order_product->nds) {
                            $data['original_price'] = round($order_product->price_discount / $nds, 2);
                            $data['total_nds'] = round(($order_product->price_discount * $order_product->amount) / $nds * $actualNds, 2);
                        } else {
                            $data['original_price'] = round($order_product->price_discount, 2);
                        }
                        $source_price = $order_product->price - round(($order_product->price / $nds) * $actualNds, 2);
                        $data['original_price_client'] = round($source_price, 2);
                        $data['total_nds_client'] = round($order_product->amount * round(($order_product->price / $nds) * $actualNds, 2), 2);
                        $data['used_nds_percent'] = $actualNds;
                    }
                    DB::table('order_products')->where('id', $order_product->id)->update($data);
                    $this->info('Order product ID: ' . $order_product->id . ' was updated!');
                }
                $this->info('Memory in use: ' . round((memory_get_usage() / 1024) / 1024, 2) . ' MB');
            }, 'order_products.id', 'id');
        $this->info('Finished!');
    }
}
