<?php

namespace App\Http\Controllers\V3;

use App\Services\API\V3\CatalogCategoryTranslationService;
use App\Http\Requests\V3\CatalogCategoryTranslation\UpdateRequest;
use App\Models\CatalogCategoryLanguage;

class CatalogCategoryTranslationController extends CoreController
{
  protected CatalogCategoryTranslationService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new CatalogCategoryTranslationService();
    }

    public function update(UpdateRequest $request, CatalogCategoryLanguage $catalog_category_translation)
    {
      return $this->service::update($request, $catalog_category_translation);
    }
}
