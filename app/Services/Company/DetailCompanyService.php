<?php

namespace App\Services\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DetailCompanyService
{
    protected string $headerTextName = 'header_text';
    protected string $headerTextLangName = 'company.header_text';

    public function addHeaderTextForCompany(Model $company): Model
    {
        $company->{$this->headerTextName} = __($this->headerTextLangName,
            [
                'uniq_num' => $company->uniq_num,
                'date_pact' => $company->date_pact
            ]);
        return $company;
    }
}
