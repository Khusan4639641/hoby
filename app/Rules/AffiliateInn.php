<?php

namespace App\Rules;

use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AffiliateInn implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {

        return true;

        $user = Auth::user();
        $request = request()->all();
        if($user){
            $query = Company::query();
            if($user->hasRole('partner')){
                $companyID = $user->company_id;
                $isAffiliate = false;

                if($partner = Partner::find($user->id)){
                    if($partnerCompany = $partner->company){
                        $isAffiliate = $partnerCompany->parent_id ? true : false;
                    }
                }
                //select * from `companies` where `inn` = ? and ((`parent_id` is null and `id` != ?) or (`parent_id` is not null and `parent_id` != ?))


                if($isAffiliate){
                    $company = $query->where('inn', '=', $value)->where('parent_id', '!=', $partnerCompany->parent_id);
                } else {
                    $company = $query->where('inn', '=', $value)
                        ->where(function($query) use ($companyID) {
                            $query->where(function($query) use ($companyID) {
                                $query->whereNull('parent_id')
                                    ->where('id', '!=', $companyID);
                            })->orWhere(function($query) use ($companyID) {
                                $query->whereNotNull('parent_id')
                                    ->where('parent_id', '!=', $companyID);
                            });
                        });
                }

                return !$company->exists();
            } elseif ($user->hasRole('sales')){

                $companyID = $request['partner_id'];


                $partner = Company::find($companyID);

                $company = $query->where('inn', '=', $value)
                                 ->where(function($query) use ($companyID, $partner) {
                                     $query->where(function($query) use ($companyID, $partner) {
                                         $query->whereNull('parent_id')
                                                 ->where(function($query) use ($companyID, $partner) {
                                                     $query->where('id', '!=', $companyID)
                                                           ->where('id', '!=', $partner->parent_id);
                                                 });
                                     })->orWhere(function($query) use ($companyID, $partner) {
                                         $query->whereNotNull('parent_id');
                                         $query->where('parent_id', '!=', $partner->parent_id ?? $companyID);
                                     });
                                 });

                return !$company->exists();
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.unique');
    }
}
