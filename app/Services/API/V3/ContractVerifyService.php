<?php

namespace App\Services\API\V3;

use App\Helpers\QueryHelper;
use App\Http\Controllers\V3\UzTaxController;
use App\Models\CatalogCategory;
use App\Models\Contract;
use App\Models\GeneralCompany;
use App\Models\OrderProduct;
use App\Models\ContractVerifyLog;
use App\Models\UzTax;
use Illuminate\Http\Request;
use App\Traits\UzTaxTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ContractVerifyService extends BaseService
{
    use UzTaxTrait;

    public static function list(Request $request)
    {
        $companyID = get_value_if_set($request['company_id']);
        $exceptionalCompanyID = get_value_if_set($request['company_id__not']);
        $id = get_value_if_set($request['id']);
        $errorCode = get_value_if_set($request['uz_tax_error_code']);
        $companyName = get_value_if_set($request['company|name__like']);
        $queryHelper = new QueryHelper(new Contract());
        $is_imfo = ($request->mfo ?? 0);
        switch ($request->filter) {
            case 0:
                $query = $queryHelper->constructQuery($request->all());
                $data = $query->whereIn('general_company_id', GeneralCompany::where('is_mfo', $is_imfo)->select('id'))->with('generalCompany:id,name_ru', 'company:id,name', 'order:id,status', 'order.products:id,order_id,name,category_id,imei,unit_id,psic_code')
                    ->paginate(15, ['id', 'order_id', 'created_at', 'company_id', 'general_company_id', 'status', 'verified']);
                break;
            case 3:
                $data = Contract::rightJoin('uz_tax_errors as ute', 'contracts.id', 'ute.contract_id')
                    ->join('uz_taxes as ut', function ($subQuery) {
                        $subQuery->on('ute.receipt_id', 'ut.id')
                            ->whereIn('ute.json_data->ReceiptType', [UzTax::RECEIPT_TYPE_SELL, UzTax::RECEIPT_TYPE_PREPAID]);
                    })
                    ->join('companies', 'contracts.company_id', 'companies.id')
                    ->join('general_companies', 'contracts.general_company_id', 'general_companies.id')
                    ->where('verified', Contract::VERIFIED)->whereIn('contracts.general_company_id', GeneralCompany::where('is_mfo', $is_imfo)->select('id'));

                if ($companyID) $data->whereIn('contracts.company_id', $companyID);
                if ($exceptionalCompanyID) $data->whereNotIn('contracts.company_id', $exceptionalCompanyID);
                if ($id) $data->where('contracts.id', $id);
                if ($errorCode) $data->where('ute.error_code', $errorCode);
                if ($companyName) $data->where('companies.name', 'like', "%$companyName%");

                $data->selectRaw('contracts.id');
                $data->selectRaw('ute.receipt_id as uz_tax_id');
                $data->selectRaw('contracts.created_at');
                $data->selectRaw('contracts.status');
                $data->selectRaw('general_companies.name_ru as general_company_name');
                $data->selectRaw('companies.name as company_name');
                $data->selectRaw('CASE ute.error_code WHEN 0 THEN \'' . __('uz_tax.error_status_0') . '\' WHEN 1 THEN \'' . __('uz_tax.error_status_1') . '\' WHEN 2 THEN \'' . __('uz_tax.error_status_2') . '\' WHEN 3 THEN \'' . __('uz_tax.error_status_3') . '\' WHEN 4 THEN \'' . __('uz_tax.error_status_4') . '\' WHEN 5 THEN \'' . __('uz_tax.error_status_5') . '\' WHEN 6 THEN \'' . __('uz_tax.error_status_6') . '\' WHEN 10 THEN \'' . __('uz_tax.error_status_10') . '\' WHEN 11 THEN \'' . __('uz_tax.error_status_11') . '\' WHEN 500 THEN \'' . __('uz_tax.error_status_500') . '\' WHEN 600 THEN \'' . __('uz_tax.error_status_600') . '\' ELSE \'' . __('uz_tax.error_status_unknown') . '\' END AS uz_tax_error_caption');
                $data = $data->paginate(15);
                break;
        }
        $total = self::getCounts($companyID, $exceptionalCompanyID, $is_imfo);
        $result = $total->merge($data);
        return $result;
    }


    public static function verify($request, bool $should_not_return = false)
    {
        foreach ($request->order_products as $product) {
            $category = CatalogCategory::where([
                'is_definite' => 1,
                'psic_code' => $product['psic_code']
            ])->first();
            if ($category) {
                if ($category->psic_code_status === CatalogCategory::PSIC_CODE_STATUS_NOT_ACTIVE) {
                    return self::errorJson([__('panel/contract_verify.psic_code_is_not_active')]);
                } elseif ($category->psic_code_status === CatalogCategory::PSIC_CODE_STATUS_ACTIVE) {
                    OrderProduct::where('id', $product['id'])->update(['psic_code' => $product['psic_code']]);
                } elseif ($category->psic_code_status === CatalogCategory::PSIC_CODE_STATUS_UNCHECKED) {
                    try {
                        $psicCodeStatus = self::getPsicCodeStatus($product['psic_code']);
                        if ($psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_INCORRECT) {
                            return self::errorJson([__('panel/contract_verify.psic_code_is_incorrect')]);
                        } elseif ($psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_UNCHECKED) {
                            return self::errorJson([__('panel/contract_verify.tasnif_server_is_not_responding')]);
                        }
                    } catch (\Exception $exception) {
                        return self::errorJson([__('panel/contract_verify.tasnif_server_is_not_responding')]);
                    }
                    if ($psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_ACTIVE) {
                        OrderProduct::where('id', $product['id'])->update(['psic_code' => $product['psic_code']]);
                        CatalogCategory::where('id', $category->id)->update(['psic_code_status' => CatalogCategory::PSIC_CODE_STATUS_ACTIVE]);
                    } else {
                        try {
                            $renewedPsicCode = self::getRenewedPsicCodeIfExists($product['psic_code']);
                        } catch (\Exception $exception) {
                            return self::errorJson([__('panel/contract_verify.tasnif_server_is_not_responding')]);
                        }
                        if ($renewedPsicCode) {
                            OrderProduct::where('id', $product['id'])->update(['psic_code' => $renewedPsicCode]);
                            CatalogCategory::where('id', $category->id)->update([
                                'psic_code' => $renewedPsicCode,
                                'psic_code_status' => CatalogCategory::PSIC_CODE_STATUS_ACTIVE,
                            ]);
                        } else {
                            CatalogCategory::where('id', $category->id)->update(['psic_code_status' => CatalogCategory::PSIC_CODE_STATUS_NOT_ACTIVE]);
                            return self::errorJson([__('panel/contract_verify.psic_code_is_not_active')]);
                        }
                    }
                }
            } else {
                try {
                    $psicCodeStatus = self::getPsicCodeStatus($product['psic_code']);
                    if ($psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_INCORRECT) {
                        return self::errorJson([__('panel/contract_verify.psic_code_is_incorrect')]);
                    } elseif ($psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_UNCHECKED) {
                        return self::errorJson([__('panel/contract_verify.tasnif_server_is_not_responding')]);
                    }
                } catch (\Exception $exception) {
                    return self::errorJson([__('panel/contract_verify.tasnif_server_is_not_responding')]);
                }
                if ($psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_ACTIVE) {
                    OrderProduct::where('id', $product['id'])->update(['psic_code' => $product['psic_code']]);
                } else {
                    try {
                        $renewedPsicCode = self::getRenewedPsicCodeIfExists($product['psic_code']);
                    } catch (\Exception $exception) {
                        return self::errorJson([__('panel/contract_verify.tasnif_server_is_not_responding')]);
                    }
                    if ($renewedPsicCode) {
                        OrderProduct::where('id', $product['id'])->update(['psic_code' => $renewedPsicCode]);
                    } else {
                        return self::errorJson([__('panel/contract_verify.psic_code_is_not_active')]);
                    }
                }
            }
        }

        foreach ($request->order_products as $product) {
            self::logOrderProductChanges($product, $request->contract_id);

            OrderProduct::find($product['id'])->update([
                'name' => $product['name'],
                'category_id' => $product['category_id'],
                'unit_id' => $product['unit_id'],
            ]);
        }

        Contract::where('id', $request->contract_id)->update(['verified' => Contract::VERIFIED]);


        $contract = Contract::where('id', $request->contract_id)->with(['generalCompany', 'company.settings', 'orderProducts.category'])->first();

        $listOfTINs = self::getCommissionListFromOfd(); // Список ИНН/ПИНФЛ
        if (!empty($contract->generalCompany) && self::isTINValid($contract, $listOfTINs)) {
            UzTaxController::QrCodeFromOfd($request, $contract);
        } else {
            $uz_tax = new UzTax();
            $uz_tax->save();
            $json_data = self::createJsonData($uz_tax->id, $request->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_SELL);
            self::catchError(0, $request->contract_id, $uz_tax->id, UzTax::OFD_NOT_MATCH_TIN_ERROR, $json_data);
        }


        if (!$should_not_return) {
            return self::handleResponse(['contract_id' => $request->contract_id]);
        }
    }

    private static function logOrderProductChanges($product, $contract_id)
    {

        $order_product = OrderProduct::where('id', $product['id'])->first();

        $should_log = false;

        $log_data = [
            'contract_id' => $contract_id,
            'order_product_id' => $product['id'],
            'user_id' => Auth::id(),
        ];

        if ($order_product->name != $product['name']) {
            $log_data['old_name'] = $order_product->name;
            $log_data['new_name'] = $product['name'];
            $should_log = true;
        }

        if ($order_product->category_id != $product['category_id']) {
            $log_data['old_category_id'] = $order_product->category_id;
            $log_data['new_category_id'] = $product['category_id'];
            $should_log = true;
        }

        if ($order_product->unit_id != $product['unit_id']) {
            $log_data['old_unit_id'] = $order_product->unit_id;
            $log_data['new_unit_id'] = $product['unit_id'];
            $should_log = true;
        }

        if ($should_log) {
            ContractVerifyLog::create($log_data);
        }
    }

    private static function getCounts($companyID, $exceptionalCompanyID, $is_mfo): Collection
    {
        $verifiedWithoutCheque = Contract::rightJoin('uz_tax_errors as ute', 'contracts.id', 'ute.contract_id')
            ->join('uz_taxes as ut', function ($subQuery) {
                $subQuery->on('ut.id', 'ute.receipt_id')
                    ->whereIn('ute.json_data->ReceiptType', [UzTax::RECEIPT_TYPE_SELL, UzTax::RECEIPT_TYPE_PREPAID]);
            })->where('verified', Contract::VERIFIED)->whereIn('general_company_id', GeneralCompany::where('is_mfo', $is_mfo)->select('id'));
        $all = Contract::where('status', Contract::STATUS_ACTIVE)->whereIn('general_company_id', GeneralCompany::where('is_mfo', $is_mfo)->select('id'));
        $verified = Contract::where(['status' => Contract::STATUS_ACTIVE, 'verified' => Contract::VERIFIED])->whereIn('general_company_id', GeneralCompany::where('is_mfo', $is_mfo)->select('id'));
        $notVerified = Contract::where(['verified' => Contract::NOT_VERIFIED])->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_30_DAYS])->whereIn('general_company_id', GeneralCompany::where('is_mfo', $is_mfo)->select('id'));
        if ($companyID) {
            $verifiedWithoutCheque->whereIn('contracts.company_id', $companyID);
            $all->whereIn('company_id', $companyID);
            $verified->whereIn('company_id', $companyID);
            $notVerified->whereIn('company_id', $companyID);
        }
        if ($exceptionalCompanyID) {
            $verifiedWithoutCheque->whereNotIn('contracts.company_id', $exceptionalCompanyID);
            $all->whereNotIn('company_id', $exceptionalCompanyID);
            $verified->whereNotIn('company_id', $exceptionalCompanyID);
            $notVerified->whereNotIn('company_id', $exceptionalCompanyID);
        }
        return collect([
            'all' => $all->count(),
            'verified' => $verified->count(),
            'not_verified' => $notVerified->count(),
            'verified_without_cheque' => $verifiedWithoutCheque->count(),
        ]);
    }

    public static function instantVerification(Contract $contract): void
    {
        if ($contract->status !== Contract::STATUS_ACTIVE ||
            $contract->company->isresus() && (!$contract->generalCompany->isMFO() || $contract->period === 3)
            || $contract->verified !== Contract::NOT_VERIFIED
        ) {
            return;
        }
        if ($contract->company->isresus() && $contract->generalCompany->isMFO() && $contract->period !== 3 && $contract->verified === Contract::NOT_VERIFIED) {
            $request = new Request();
            $request->merge(['contract_id' => $contract->id]);
            self::sendServiceCheque($request);
            return;
        }
        foreach ($contract->orderProducts as $product) {
            if (
                !isset($product->category) ||
                $product->category->is_definite === 0 ||
                CatalogCategory::where('parent_id', $product->category_id)->exists() ||
                empty($product->category->psic_code)
            ) {
                return;
            }
        }
        $products = [];
        foreach ($contract->orderProducts as $product) {
            $orderProduct = [
                'id' => $product->id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'unit_id' => $product->unit_id,
                'psic_code' => $product->category->psic_code,
            ];
            array_push($products, $orderProduct);
        }
        $request = new Request();
        $request->merge(['order_products' => $products]);
        $request->merge(['contract_id' => $contract->id]);
        ContractVerifyService::verify($request, true);
    }

    private static function sendServiceCheque($request)
    {
        Contract::where('id', $request->contract_id)->update(['verified' => Contract::VERIFIED]);

        $contract = Contract::where('id', $request->contract_id)->with(['generalCompany', 'company.settings', 'orderProducts.category'])->first();

        UzTaxController::QrCodeFromOfd($request, $contract);
    }

    public static function isTINValid(Contract $contract, array $list): bool
    {
        if ($contract->generalCompany->isMFO()) {
            if ($contract->company->isresus()) {
                return true;
            }
            if ($contract->company->isIndividualEntrepreneur()) {
                return in_array($contract->company->inn, $list['PINFL'], true);
            } else {
                return in_array($contract->company->inn, $list['TIN'], true);
            }
        }
        return in_array($contract->generalCompany->inn, $list['TIN'], true);
    }

}
