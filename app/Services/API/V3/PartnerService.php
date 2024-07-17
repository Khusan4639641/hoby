<?php

namespace App\Services\API\V3;

use App\Helpers\FileHelper;
use App\Models\CatalogPartners;
use App\Models\Company;
use Illuminate\Http\Request;
use DB;

class PartnerService extends BaseService
{
    public static function list(Request $request)
    {
        $lang = app()->getLocale();
        $result = CatalogCategoryService::list($request->all(), true);
        $partners = (new self)->getPartners();
        if ($result) {
            foreach ($result as $key => $value) {
                $items = [];
                foreach ($partners as $partner) {
                    if ($partner->catalog_id == $value->id) $items[] = $partner;
                }
                $value->items = $items;
            }
        }
        return self::handleResponse($result);
    }

    public static function detail($id)
    {
        $id = (int)$id;
        $companies = Company::with('categories')
            ->select('companies.id', 'companies.name', 'files.path AS logo', 'companies.description', 'companies.brand', 'companies.region_id', 'companies.address', 'companies.legal_address', 'companies.status', 'companies.created_at', 'companies.updated_at', 'companies.website', 'companies.phone',  'companies.lat', 'companies.lon', 'fias_regions.nameRu', 'fias_regions.nameUz', 'fias_regions.codelat', 'fias_regions.codecyr')
            ->leftJoin('files', 'files.element_id', '=', 'companies.id')
            ->leftJoin('fias_regions', 'fias_regions.regionid', '=', 'companies.region_id')
            ->where('companies.id', $id)
            ->where('files.model', 'company')->where('files.type', 'logo')
            ->first();
        if(!$companies){
            return self::handleError([],'error',404);
        }
        return self::handleResponse($companies);
    }

    public static function settings($id)
    {
        $id = (int) $id;

        $company = Company::with('settings')->find($id);

        if (!$company || !$company->settings) {
            return self::handleError([],'error',404);
        }

        // Populate data array with necessary settings
        $data = [
            'is_scoring_enabled' => $company->settings->is_scoring_enabled,
            'is_mini_scoring_enabled' => $company->settings->is_mini_scoring_enabled,
        ];

        return self::handleResponse($data);
    }

    private function getPartners()
    {
        $file_path = FileHelper::url('');
        $result = CatalogPartners::select('catalog_partners.catalog_id', 'catalog_partners.partner_id', 'companies.id', 'companies.name', 'companies.description', 'companies.brand', 'companies.region_id', 'companies.address', 'companies.legal_address', 'companies.status', 'companies.created_at','companies.parent_id', 'companies.updated_at', 'companies.website', 'companies.phone',  'companies.lat', 'companies.lon', 'fias_regions.nameRu', 'fias_regions.nameUz', 'fias_regions.codelat', 'fias_regions.codecyr')
            ->addSelect(DB::raw("CONCAT('$file_path',files.path) AS logo"))
            ->leftJoin('companies', 'companies.id', '=', 'catalog_partners.partner_id')
            ->leftJoin('files', 'files.element_id', '=', 'companies.id')
            ->leftJoin('fias_regions', 'fias_regions.regionid', '=', 'companies.region_id')
            ->where('files.model', 'company')->where('files.type', 'logo')
            ->where('companies.status',1)
            ->get();
        return $result;
    }
}
