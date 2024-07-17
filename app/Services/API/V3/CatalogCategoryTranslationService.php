<?php

namespace App\Services\API\V3;

use Illuminate\Http\Request;

class CatalogCategoryTranslationService extends BaseService
{
    public static function update(Request $request, $catalog_category_translation)
    {
        $catalog_category_translation->update($request->all());

        return self::handleResponse($catalog_category_translation->id);
    }
}
