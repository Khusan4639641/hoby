<?php

namespace App\Providers;

use App\Models\Buyer;
use App\Models\Card;
use App\Models\CatalogCategory;
use App\Models\CatalogProduct;
use App\Models\CatalogProductField;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Faq;
use App\Models\File;
use App\Models\News;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Saller;

use App\Models\Payment;
use App\Models\Slide;
use App\Policies\BuyerPolicy;
use App\Policies\CardPolicy;
use App\Policies\CatalogCategoryPolicy;
use App\Policies\CatalogProductFieldPolicy;
use App\Policies\CatalogProductPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ContractPaymentsSchedulePolicy;
use App\Policies\ContractPolicy;
use App\Policies\DiscountPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\FaqPolicy;
use App\Policies\FilePolicy;
use App\Policies\NewsPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PartnerPolicy;
use App\Policies\SallerPolicy;

use App\Policies\PaymentPolicy;
use App\Policies\SlidePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider {
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
        Card::class            => CardPolicy::class,
        File::class            => FilePolicy::class,
        News::class            => NewsPolicy::class,
        Faq::class            => FaqPolicy::class,
        Discount::class        => DiscountPolicy::class,
        Employee::class        => EmployeePolicy::class,
        Contract::class        => ContractPolicy::class,
        Buyer::class           => BuyerPolicy::class,
        Partner::class         => PartnerPolicy::class,
        Company::class         => CompanyPolicy::class,
        Order::class           => OrderPolicy::class,
        CatalogProduct::class  => CatalogProductPolicy::class,
        CatalogProductField::class  => CatalogProductFieldPolicy::class,
        CatalogCategory::class => CatalogCategoryPolicy::class,
        ContractPaymentsSchedule::class => ContractPaymentsSchedulePolicy::class,
        Payment::class => PaymentPolicy::class,
        Slide::class => SlidePolicy::class,
        Saller::class  => SallerPolicy::class,

    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        $this->registerPolicies();

        //
    }
}
