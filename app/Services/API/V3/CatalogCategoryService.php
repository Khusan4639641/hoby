<?php

namespace App\Services\API\V3;

use App\Helpers\CategoryHelper;
use App\Models\CatalogCategory;
use App\Models\CatalogCategoryLanguage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class CatalogCategoryService extends BaseService
{
    public static function list($filters = [], $return = false)
    {
        $lang = app()->getLocale();
        $status = 0;
        if(!empty($filters['is_mobile'])){
            $status = 1;
        }

        $data = CatalogCategory::select(
            'catalog_categories.id',
            'catalog_categories.sort',
            'catalog_categories.parent_id',
            'catalog_categories.psic_code',
            'catalog_category_languages.title',
            'catalog_category_languages.slug'
        )
            ->leftJoin('catalog_category_languages', 'catalog_category_languages.category_id', '=', 'catalog_categories.id')
            ->where('catalog_category_languages.language_code', $lang)
            ->where('catalog_categories.status', $status);

        if(!empty($filters['is_mobile'])){
            $data = $data->addSelect('files.path AS icon')
                ->leftJoin('files', 'files.element_id', '=', 'catalog_categories.id')
                ->where('model', 'catalog-category')->where('type', CatalogCategory::PARENT_CATEGORY_FILE_TYPE);
        }

        if (!empty($filters['id'])) {
            $data = $data->where('catalog_categories.id', $filters['id']);
        }else{
            $data = $data->where('catalog_categories.parent_id', 0);
        }

        $data = $data->get();

        if (count($data) > 0) {
            return $return ? $data : self::handleResponse($data);
        }

        return self::handleError([], 'error', 404);
    }

    public static function panelList($filters = [], $return = false)
    {
        $lang = app()->getLocale();
        $legacyCategories = CatalogCategory::LEGACY_CATEGORIES;
        array_push($legacyCategories, CatalogCategory::OTHERS_CATEGORY);

        $data = CatalogCategory::select(
            'catalog_categories.id',
            'catalog_categories.sort',
            'catalog_categories.parent_id',
            'catalog_categories.psic_code',
            'catalog_categories.status',
            'catalog_categories.is_definite',
            'catalog_categories.is_phone',
            'catalog_category_languages.title',
            'catalog_category_languages.slug',
        )
            ->leftJoin('catalog_category_languages', 'catalog_category_languages.category_id', '=', 'catalog_categories.id')
            ->where('catalog_category_languages.language_code', $lang)
            ->whereNotIn('catalog_categories.id', $legacyCategories)
            ->orderByRaw('FIELD(is_definite, false)');

        if (empty($filters['parent_id'])) {
            $data = $data->where('catalog_categories.parent_id', 0);
        } elseif($filters['parent_id'] && $filters['parent_id'] != -1) {
            $data = $data->where('catalog_categories.parent_id', $filters['parent_id']);
        }

        if (empty($filters['status'])) {
            $data = $data->where('catalog_categories.status', 1);
        } elseif ($filters['status'] != -1)  {
            $data = $data->where('catalog_categories.status', $filters['status']);
        }
        $data = $data->get();

        return self::handleResponse($data);
    }

    public static function treeList($filters = [], $return = false)
    {
        $data = CatalogCategory::where('parent_id',0)->where('status',1)->with('child','language');
        if (!empty($filters['id'])) {
            $data = $data->where('id', $filters['id']);
        }else{
            $data = $data->where('parent_id', 0);
        }

        $data = $data->get();

        if (count($data) > 0) {
            return $return ? $data : self::handleResponse($data);
        }

        return self::handleError([], 'error', 404);
    }

    public static function all($filters = [], $return = false)
    {
        $data = CatalogCategory::with('languages')
          // Категории, оставленные для созданных контрактов.
          // TODO: Убрать, когда в уже созданных контрактах заменят старые категории на новые.
          ->whereNotIn('id', CatalogCategory::LEGACY_CATEGORIES);

        if (empty($filters['parent_id'])) {
            $data = $data->where('catalog_categories.parent_id', 0);
        } elseif($filters['parent_id'] && $filters['parent_id'] != -1) {
            $data = $data->where('catalog_categories.parent_id', $filters['parent_id']);
        }

        $data = $data->get();

        return self::handleResponse($data);
    }

    public static function get(CatalogCategory $catalog_category)
    {
        $catalog_category->load('languages');
        return self::handleResponse($catalog_category);
    }

    public static function add($request)
    {
        $catalog_category = CatalogCategory::create($request->except('languages'));

        foreach ($request->languages as $language) {

            $catalog_category_language = new CatalogCategoryLanguage($language);

            $catalog_category->languages()->save($catalog_category_language);
        }

        return self::handleResponse($catalog_category);
    }

    public static function update($request, $catalog_category)
    {
        $catalog_category->update($request->all());

        return self::handleResponse($catalog_category->id);
    }

    public static function delete($catalog_category)
    {
        $catalog_category->languages()->delete();
        $catalog_category->delete();

        return self::handleResponse($catalog_category->id);
    }


    public static function searchByPsicCode($psic_code)
    {
        $psicCodes = CatalogCategory::select('catalog_categories.id','catalog_category_languages.title', 'catalog_categories.psic_code', 'catalog_categories.parent_id')
            ->join('catalog_category_languages', 'catalog_categories.id','catalog_category_languages.category_id')
            ->where('catalog_categories.psic_code', 'like', "$psic_code%")
            ->where('catalog_category_languages.language_code', App::getLocale())
            ->get();
        self::handleResponse($psicCodes);
    }

    public function getCategoriesHierarchy(string $search_value, int $limit = null, int $offset = null)
    {
        $result = [];
        $language = App::getLocale();
        $categoryIDs = CatalogCategory::whereHas('languages', function($query) use ($language, $search_value) {
            $query->where('language_code', $language)->where(function($subQuery) use ($search_value) {
                $subQuery->where('title', 'like', "%$search_value%")
                    ->orWhere('psic_code', 'like', "$search_value%");
            });
        })->where([
            'status' => CatalogCategory::STATUS_ACTIVE,
            'is_definite' => 1,
        ])->select('id');
        if ($limit) {
            $categoryIDs->limit($limit);
        }
        if ($offset) {
            $categoryIDs->offset($offset);
        }
        $categoryIDs = $categoryIDs->pluck('id');

        foreach ($categoryIDs as $categoryID) {
            $categories = CategoryHelper::getParentCategoryIDs($categoryID);
            $productCategoriesImploded = implode(',',$categories);
            $productCategories = CatalogCategory::join('catalog_category_languages as ccl', 'ccl.category_id', 'catalog_categories.id')
                ->where('language_code', $language)
                ->whereIn('catalog_categories.id', $categories)
                ->orderByRaw("FIELD(catalog_categories.id, $productCategoriesImploded) desc") // чтобы категории подтягивались в правильном порядке
                ->select('catalog_categories.id', 'ccl.title', 'catalog_categories.psic_code', 'catalog_categories.parent_id', 'catalog_categories.status', 'catalog_categories.is_phone')
                ->get();
            $titlesHierarchy = $productCategories->first()->title;
            $lastProduct = $productCategories->last();
            for($i = 1; $i < count($productCategories); $i++) {
                $titlesHierarchy .= " / {$productCategories[$i]->title}";
                if ($productCategories[$i]->is_phone) {
                    $lastProduct->is_phone = 1;
                }
            }
            $lastProduct->hierarchy_title = $titlesHierarchy;
            $lastProduct->children = null;
            array_push($result, $lastProduct);
        }
        self::handleResponse($result);
    }

}
