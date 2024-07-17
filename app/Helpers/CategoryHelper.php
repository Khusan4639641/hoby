<?php

namespace App\Helpers;

use App\Models\CatalogCategory;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class CategoryHelper
{
    public static function getPhoneCategoryIDs(): array
    {
        if (Redis::exists('phone_category_ids')) {
            return json_decode(Redis::get('phone_category_ids'));
        }
        $phoneCategoryIDs = CatalogCategory::where('is_phone', 1)->pluck('id')->all();
        Redis::set('phone_category_ids', json_encode($phoneCategoryIDs), 'ex', 3600);
        return $phoneCategoryIDs;
    }

    public static function getParentCategoryIDs(int $category_id): array
    {
        $categories = DB::select("WITH RECURSIVE ancestors AS
        (
            SELECT * FROM catalog_categories
            WHERE id = $category_id
            UNION
            SELECT parent_category.*
            FROM catalog_categories AS parent_category, ancestors AS a
            WHERE parent_category.id = a.parent_id)
            SELECT id FROM ancestors"
        );

        foreach($categories as $key => $category) {
            $categories[$key] = $category->id;
        }

        return $categories;
    }

    public static function isPhone(int $category_id): bool
    {
        $parentCategoryIDs = self::getParentCategoryIDs($category_id);
        $phoneCategoryIDs = self::getPhoneCategoryIDs();
        for ($i = count($parentCategoryIDs) - 1; $i >= 0; $i--) {
            for ($j = 0; $j < count($phoneCategoryIDs); $j++) {
                if ($parentCategoryIDs[$i] === $phoneCategoryIDs[$j]) {
                    return true;
                }
            }
        }
        return false;
    }
}
