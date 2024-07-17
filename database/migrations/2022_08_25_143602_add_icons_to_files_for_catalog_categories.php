<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\CatalogCategory;
use \App\Models\File;

class AddIconsToFilesForCatalogCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->addIcons();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

    private function addIcons()
    {
        $categories = CatalogCategory::where('parent_id',0)->where('status',1)->get();
        if(count($categories) > 0){
            foreach ($categories as $category) {
                $file = File::where('model','catalog-category')->where('element_id',$category->id)->where('type', CatalogCategory::PARENT_CATEGORY_FILE_TYPE)->first();
                if(!$file){
                    $insert_data = [
                        'element_id' => $category->id,
                        'model' => 'catalog-category',
                        'type' => CatalogCategory::PARENT_CATEGORY_FILE_TYPE,
                        'name' => $category->id.'.png',
                        'path' => 'images/icons/catalog-category/'.$category->id.'.png',
                        'user_id' => 0,
                        'doc_path' => 1,
                    ];
                    File::create($insert_data);
                }
            }
        }
    }
}
