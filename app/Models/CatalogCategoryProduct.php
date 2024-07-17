<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class CatalogCategoryProduct extends Model {

    protected $table = 'catalog_category_catalog_product';

    public $timestamps = false;

    protected $primaryKey = 'catalog_product_id';



}
