<?php

namespace App\Console\Commands;

use App\Http\Controllers\Core\CatalogProductController;
use App\Models\CatalogCategory;
use App\Models\CatalogProduct;
use App\Models\CatalogProductField;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class ProductsAction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import {provider} {--user-id=} {--arg=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products other system';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $provider = $this->argument('provider');
        $arg = $this->option('arg');
        $userId = $this->option('user-id');
        switch($provider) {
            case 'texnomart':
                switch($arg[0]) {
                    case 'delete':
                        $this->deleteTexnomart($userId);
                        break;
                    case 'load':
                    default:
                        $this->importTexnomart($userId);
                        break;
                }
                break;
            default:
                $this->info('Please input provider');
                break;
        }
        return 0;
    }

    protected function importTexnomart($userId) {
        $entryUrl = 'https://open.zoodmall.com/rpc.php';
        $rowsPerPage = 20; $pages = 2; $arrProducts = [];
        $arCategoryMapping = [
            "3266" => 111,
            "3" => 184,
            "5" => 106
        ];
        if(Redis::get('texnomart')) {
            $arrProducts = json_decode(Redis::get('texnomart'), true);
        } else {
            for ($page = 1; $page <= $pages; $page++) {
                $content = [
                    "jsonrpc" => "2.0",
                    "method" => "ProductRpc.finds",
                    "params" => [[
                        "status" => 1,
                        "page" => $page,
                        "rowsPerPage" => $rowsPerPage
                    ]],
                    "id" => substr(uniqid(), 0, 16)
                ];
                $params = [
                    'http' => [
                        'method' => "POST",
                        'header' => "Accept-language: ru\r\n" .
                            "Content-type: application/json\r\n" .
                            "X-RPC-Auth-Username: 1946\r\n" .
                            "X-Rpc-Auth-Password: f02549a0984f2d3b3eeb72a9140dc5a9\r\n",
                        'content' => json_encode($content)
                    ]
                ];

                $context = stream_context_create($params);

                $response = file_get_contents($entryUrl, false, $context);
                $arrProduct = json_decode($response, true);
                $count = $arrProduct['result']['pagination']['totalCount'];
                $arrProducts = array_merge($arrProducts, $arrProduct['result']['products']);
                $pages = ceil($count / $rowsPerPage);
            }
            Redis::set('texnomart', json_encode($arrProducts));
        }

        $locales = config('app.locales');
        $filter = ["toshkent", "chirchik", "fergana", "namangan", "andijan", "ташкент", "фергана", "чирчик", "наманган", "tashkent", "наличие"];
        $property = [];
        foreach ($arrProducts as $product) {
            for ($p=0;$p<sizeof($product['sproperty']);$p++) {
                $lang = [];
                if (in_array(mb_strtolower($product['sproperty'][$p]['name'], 'UTF-8'), $filter)) continue;
                $lang['ru'] = $product['sproperty'][$p]['name'];
                foreach($locales as $lng) {
                    if(isset($product['spropertyLang'][$lng]) && isset($product['spropertyLang'][$lng][$p]))
                        $lang[$lng] = $product['spropertyLang'][$lng][$p]['name'];
                    else
                        $lang[$lng] = $lang['ru'];
                }
                $property[$product['categoryId']][$product['sproperty'][$p]['name']] = $lang;
            }
        }

        foreach ($property as $catID => $fields) {
            $fields = []; $sort = 100;
            foreach ($property[$catID] as $key => $field) {
                $catalogProductField = new CatalogProductField();
                $currField = $catalogProductField->where('name->ru', $field['ru'])->first();
                if ($currField == null) {
                    $catalogProductField->name = json_encode($field);
                    $catalogProductField->type = 'string';
                    $catalogProductField->save();
                    $property[$catID][$key]['field_id'] = $catalogProductField->id;
                    $fields[$catalogProductField->id] = ['sort' => $sort];
                    $sort += 50;
                } else {
                    $property[$catID][$key]['field_id'] = $currField->id;
                }

            }
            if (sizeof($fields) > 0) {
                $category = CatalogCategory::find($arCategoryMapping[$catID]);
                $category->fields()->attach($fields);
            }
        }

        foreach ($arrProducts as $product) {

            $catalogProduct = new CatalogProductController();

            $reqProduct = new Request();

            $ruTitle = $product['name'];
            $ruDescription = str_replace(["\n","\r"], ['<br>',''], $product['description']);

            if (isset($product['nameLang']['uz'])) {
                $uzTitle = $product['nameLang']['uz'];
            } else {
                $uzTitle = $ruTitle;
            }

            if (isset($product['descriptionLang']['uz'])) {
                $uzDescription = str_replace(["\n","\r"], ['<br>',''], $product['descriptionLang']['uz']);
            } else {
                $uzDescription = $ruDescription;
            }

            $uzDescription = preg_replace('/^(.*Y\<br\>)/','',$uzDescription);
            $ruDescription = preg_replace('/^(.*Y\<br\>)/','',$ruDescription);

            $images = [];
            foreach ($product['picture']  as $k => $picture) {
                $filename = 'temp-image' . $k . '.jpg';
                $tempImage = tempnam(sys_get_temp_dir(), $filename);
                if (@copy($picture, $tempImage)) {
                    $images[] = new UploadedFile($tempImage, $filename);
                }
            }
            $reqProduct->files->add(['image' => $images]);

            $fields = [];
            for ($p=0;$p<sizeof($product['sproperty']);$p++) {
                $fieldValue = [];
                if(isset($property[$product['categoryId']][$product['sproperty'][$p]['name']]))
                    $fieldId = $property[$product['categoryId']][$product['sproperty'][$p]['name']]['field_id'];
                else continue;
                $fieldValue['ru']['value'] = $product['sproperty'][$p]['value'];
                foreach($locales as $lng) {
                    if(isset($product['spropertyLang'][$lng]) && isset($product['spropertyLang'][$lng][$p]))
                        $fieldValue[$lng]['value'] = $product['spropertyLang'][$lng][$p]['value'];
                    else
                        $fieldValue[$lng]['value'] = $fieldValue['ru']['value'];
                }
                $fields[$fieldId] = $fieldValue;
            }

            $reqProduct->merge([
                'vendor_code' => $product['sku'],
                'price_origin' => $product['defaultPrice'],
                'quantity' => 1,
                'weight' => $product['productWeight'],
                'categories' => [$arCategoryMapping[$product['categoryId']]],
                'uz_title' => $uzTitle,
                'uz_slug' => Str::slug($ruTitle),
                'uz_preview_text' => '-',
                'uz_detail_text' => $uzDescription,
                'ru_title' => $ruTitle,
                'ru_slug' => Str::slug($ruTitle),
                'ru_preview_text' => '-',
                'ru_detail_text' => $ruDescription,
                'fields' => $fields
            ]);
            Auth::loginUsingId($userId, true);

            $updateProduct = CatalogProduct::where('vendor_code', $product['sku'])->first();

            if ($updateProduct != null) {
                $reqProduct->merge([
                    'product_id' => $updateProduct->id
                ]);
                $catalogProduct->modify($reqProduct);
            } else {
                $catalogProduct->add($reqProduct);
            }
        }
        Redis::del('texnomart');
    }

    protected function deleteTexnomart($userId){
        Auth::loginUsingId($userId, true);
        $catalogProduct = new CatalogProductController();
        $list = $catalogProduct->list(['user_id' => $userId]);
        foreach ($list['data'] as $product) {
            $catalogProduct->delete($product);
        }
    }
}
