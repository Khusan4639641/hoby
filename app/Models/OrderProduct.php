<?php

namespace App\Models;

use App\Helpers\NdsStopgagHelper;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_CANCELED = 2;

    protected $fillable = [
        'order_id',
        'product_id',
        'vendor_code',
        'name',
        'label',
        'price',
        'price_discount',
        'amount',
        'weight',
        'category_id',
        'imei',
        'unit_id',
        'original_name',
        'original_imei',
        'psic_code',
        'original_price',
        'total_nds',
        'original_price_client',
        'total_nds_client',
        'used_nds_percent',
        'status',
        'external_id'
    ];

    protected $appends = [
        'source_price',
        'nds_sum_of_price',
        'source_total_sum',
        'total_sum',
        'total_nds_sum',
    ];

    public function info(){
        return $this->hasOne(CatalogProduct::class,  'id', 'product_id' );
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function category()
    {
        return $this->belongsTo(CatalogCategory::class, 'category_id');
    }

    public function getSourcePriceAttribute()
    {
        return round($this->price - $this->nds_sum_of_price, 2);
    }

    public function getNdsSumOfPriceAttribute()
    {
        return round($this->price / NdsStopgagHelper::getActualNdsPlusOne($this->created_at) *
            NdsStopgagHelper::getActualNds($this->created_at), 2);
    }

    public function getSourceTotalSumAttribute()
    {
        return round($this->amount * $this->source_price, 2);
    }

    public function getTotalSumAttribute()
    {
        return round($this->amount * $this->price, 2);
    }

    public function getTotalNdsSumAttribute()
    {
        return round($this->amount * $this->nds_sum_of_price, 2);
    }

    //Excel Отчеты аттрибуты
    /*public function getWithoutNdsDiscountAttribute()
    {
        if (@$this->order->partner->settings->nds == 1) {
            return $this->price_discount / 1.15;
        } else {
            return $this->price_discount;
        }
    }

    public function getNdsPriceAttribute()
    {
        return $this->price /1.15;
    } */

}
