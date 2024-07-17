<?php

namespace App\Http\Controllers\Web\Billing;

use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Helpers\QRCodeHelper;
use App\Http\Controllers\Core\CatalogCategoryController;
use App\Http\Controllers\Core\CatalogProductController;

use App\Models\Buyer;
use App\Models\BuyerGuarant;
use App\Models\BuyerPersonal;
use App\Models\CatalogCategoryLanguage;
use App\Models\CatalogPartners;
use App\Models\Contract;
use App\Models\Order;
use App\Models\Partner;
use App\Models\PartnerSetting;
use App\Models\Saller;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use MongoDB\Driver\Session;
use PDF;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadeRequest;
use App\Http\Controllers\Core\OrderController as Controller;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index($for_cancellation = false) {
        $user = Auth::user();

        if(Config::get('test.is_active_new_merchant_web')) {
            $new_merchant_link = Config::get('test.new_merchant_web_link') . '?token='.$user->api_token;
            return redirect()->away($new_merchant_link,302)->with(Auth::logout());
        }

        $partner = Partner::find($user->id);
        $affiliatesId = null;
        $partnersId = [$user->id];

//        if($partner){
//            if($company = $partner->company){
//                if($affiliates = $company->affiliates){
//                    $affiliatesId = $affiliates->pluck('id')->toArray();
//                }
//            }
//        }
//
//        if($affiliatesId){
//            $partnersId = array_merge($partnersId, $affiliatesId);
//        }

        //Approve orders
        $params = [
            'params'    => [
                [
                    'status'   => 1,
                    'partner_id'    => $partnersId,
                ]
            ],
            'total_only'    => 'yes'
        ];

        $counter['approve'] = $this->filter($params)['total'];

        //Active orders
        $params = [
            'params'    => [
                [
                    'status'   => [3, 4, 6, 7, 8],
                    'partner_id'    => $partnersId,
                ]
            ],
            'total_only'    => 'yes'
        ];
        $counter['active'] = $this->filter($params)['total'];

        //Payments orders
        $params = [
            'params'    => [
                [
                    'credit__more'      => 0,
                    'partner_id'        => $partnersId,
                    'status'            => [4, 6, 7, 8, 9],
                ],
                [
                    'query_operation'   => 'or',
                    'debit__more'   => 0,
                    'partner_id'   => $partnersId,
                    'status'       => [4, 6, 7, 8, 9],
                ]
            ],
            'total_only'        => 'yes'
        ];
        $counter['payment'] = $this->filter($params)['total'];

        //Complete orders
        $params = [
            'params'    => [
                [
//                    'status'            => [5, 9],
                    'partner_id'        => $partnersId,
                ]
            ],
            'total_only' => 'yes'
        ];

        $counter['complete'] = $this->filter($params)['total'];


        //Act needed
        $params = [
            'params'    => [
                [
                    'contract|act_status'   => [0, 2],
                    'partner_id'    => $partnersId,
                ]
            ],
            'total_only'    => 'yes'
        ];
        $counter['act_needed'] = $this->filter($params)['total'];

        // In moderation
        $params = [
            'params'    => [
                [
                    'contract|status'   => [0],
                    'partner_id'    => $partnersId,
                ]
            ],
            'total_only'    => 'yes'
        ];
        $counter['in_moderation'] = $this->filter($params)['total'];

        // In installation
        $params = [
            'params'    => [
                [
                    'contract|status'   => [1],
                    'partner_id'    => $partnersId,
                ]
            ],
            'total_only'    => 'yes'
        ];
        $counter['in_installation'] = $this->filter($params)['total'];

        // Overdue
        $params = [
            'params'    => [
                [
                    'contract|status'   => [3],
                    'partner_id'    => $partnersId,
                ]
            ],
            'total_only'    => 'yes'
        ];
        $counter['overdue'] = $this->filter($params)['total'];
        $params = [
            'query_operation' => 'or',
            'params'    => [
                [
                    'contract|status'   => [4],
                    'partner_id'    => $partnersId,
                ]
            ],
            'total_only'    => 'yes'
        ];
        $counter['overdue'] += $this->filter($params)['total'];

        // Cancelled
        $params = [
            'params'    => [
                [
                    'contract|status'   => [5],
                    'partner_id'    => $partnersId,
                ]
            ],
            'total_only'    => 'yes'
        ];
        $counter['cancelled'] = $this->filter($params)['total'];

        $partnerSetting = $partner->settings;

        $isPartnerNDS = false;
        if ($partnerSetting) {
            $isPartnerNDS = (int)$partnerSetting->nds > 0 ? true : false;
        }

        $partnersId = '[' . implode(',', $partnersId) . ']';

        if($for_cancellation)
            return view('billing.order.contracts_for_cancellation', compact('counter', 'partnersId', 'isPartnerNDS'));
        return view('billing.order.index', compact('counter', 'partnersId', 'isPartnerNDS'));
    }

    public function showOrdersForCancellation() {
        if(Auth::user()->hasRole('sales-manager'))
            return self::index(true);

        return abort(404);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create(Request $request)
    {
        $partner = Partner::find(Auth::user()->id);
        $user = Auth::user();

        // если выбран из обратный расчет процентов (проценты уже заложены в цене)
        if ( $partner->company->reverse_calc === 1 ) {
            $plans = Config::get('test.plans_reverse');
        } else {
            // если выбран из админки - расширенный лимит - 24 мес  27.12.21
            if ( $partner->company->settings->plan_extended_confirm === 1 ) {
                $plans = Config::get('test.plans_extended');
            } else {
                $plans = Config::get('test.plans');

                // набираем включенные лимиты из админки, если не включены - берем все из конфига

                $plans_get = [];
                if($partner->company->settings->limit_3 == 1){
                    $plans_get[3] = $plans[3];
                }
                if($partner->company->settings->limit_6 == 1){
                    $plans_get[6] = $plans[6];
                }
                if($partner->company->settings->limit_9 == 1){
                    $plans_get[9] = $plans[9];
                }
                if($partner->company->settings->limit_12 == 1){
                    $plans_get[12] = $plans[12];
                }

                //if($plans_get) $plans = $plans_get;
                $plans = $plans_get;   // если ни один не задан период, тогда не продаем ничего

            }
        }
        $plan_graf = Config::get('test.plan_graf');

        $product = null;
        if($request->product_id) {
            $productController = new CatalogProductController();
            $product = $productController->single($request->product_id);
        }

        $sallers = Saller::where('seller_company_id',$user->company_id)->where('is_saller',1)->get();

        // Отправляем только те, категории, которые принадлежат этому вендору (временно отключено)
//        $partnerCategories = $partner->company->categories;
        $language = \Illuminate\Support\Facades\Request::segment(1);

        $partnerCategories = CatalogCategoryLanguage::where('language_code', $language)
            ->whereIn('category_id', [1,2,3,4,5,6,7,8,9,10,11,12])->pluck('title', 'category_id');

        $IsCapableToClarify = (bool)PartnerSetting::where('company_id', $partner->company->id)->pluck('is_trustworthy')->first();

        foreach($partnerCategories as $key => $partnerCategory) {
            $partnerCategoryNames[] = [
                'category_id' => $key,
                'title' => $partnerCategory,
            ];
        }
        $partnerCategoryNames = json_encode($partnerCategoryNames);

        return view('billing.order.create', compact('partner', 'plans', 'plan_graf',  'user', 'product','sallers','partnerCategoryNames', 'IsCapableToClarify'));
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function show($id)
    {
        return view('billing.order.index');
    }

    public function cancel(Request $request)
    {
        $result = $this->detail($request->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('billing.orders.index'))->with('message', $this->result['response']['message']);
        }

        $contract = Contract::where('order_id', $request->id)->first();
        $this->addCancellationReason($contract, $request->cancellation_reason);

        $type = $request->type;

        $order = Order::find($request->id);
        $contract = $order->contract;
        $user = User::find($contract->user_id);

        if ( $order->partnerSettings->manager_request == 2 && !Auth::user()->hasRole('sales-manager') && $type == 'cancel') {
            if( FacadeRequest::segment(1) == 'uz' )
                return view('billing.order.contract_cancellation_blank_uz', compact('contract', 'user'));

            return view('billing.order.contract_cancellation_blank', compact('contract', 'user'));
        }

        return \view('billing.order.cancel', $result['data'],compact('type'));
    }

    public function addCancellationReason($contract, $cancellation_reason)
    {
        $contract->contract_cancellation_reason = $contract->contract_cancellation_reason ? ($cancellation_reason ?? $contract->contract_cancellation_reason) : $cancellation_reason;
        $contract->save();
    }


    public function denyCancellation(Request $request) {
        if($request->contract_id) {

            $contract = Contract::find($request->contract_id);
            $contract->cancellation_status = 2;
            $contract->save();
            return redirect()->back()->with('msg', __('billing/order.successfully_denied'));
        } else
            return redirect()->back()->with('msg', __('billing/order.contract_id_not_found'));
    }

    public function uploadContractCancellation(Request $request)
    {

        $postData = $request->file('image');

        if($postData)
            $file = $postData;
        else
            return back()->with('message', __('billing/order.act_image_is_required'));

        $fileArray = array('image' => $file);

        $rules = array(
            'image' => 'mimes:jpg,png,pdf'
        );
        $messages = [
            'mimes' => __('billing/order.upload_file_with_jpg_png_or_pdf_format'),
        ];

        $validator = Validator::make($fileArray, $rules, $messages);

        if ($validator->fails())
            return back()->with('message', __('billing/order.upload_file_with_jpg_png_or_pdf_format'));

        $params = [
            'files' => [\App\Models\File::TYPE_IMAGE => $postData],
            'element_id' => $request->contract_id,
            'model' => 'contract'
        ];

        FileHelper::upload($params);

        $contract = Contract::find($request->contract_id);
        $contract->cancellation_status = 1;
        $contract->save();
        if($contract->cancellation_status)
            return redirect(localeRoute('billing.orders.index'))->with('msg', __('billing/order.act_successfully_uploaded'));

    }

    public function sendCancellationAct(Request $request, $contract_id = 0) {

        if(!$contract_id)
            return redirect()->back()->with('error', 'Order id not found');

        $contract = Contract::find($contract_id);

        if(!$contract)
            return redirect()->back()->with('error', 'Contract not found');

        $company = $contract->company;
        $file = \App\Models\File::where('element_id', $contract->id)->latest()->first();
        $fileUrl = FileHelper::url($file->path);

        return view('billing.order.contract_for_cancellation', compact('company', 'fileUrl', 'contract_id'));
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function account($id)
    {
        $result = $this->detail($id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('billing.orders.index'))->with('message', $this->result['response']['message']);
        } else {
            return view('billing.order.account', $result['data']);
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function calculator(Request $request)
    {
        $partner = Partner::find(Auth::user()->id);
        $user = Auth::user();
        $plans = Config::get('test.plans');
        $plan_graf = Config::get('test.plan_graf');

        $product = null;
        if($request->product_id) {
            $productController = new CatalogProductController();
            $product = $productController->single($request->product_id);
        }

        $sallers = Saller::where('company_id',$user->company_id)->where('is_saller',1)->get();

        return view('billing.calculator.index', compact('partner', 'plans', 'plan_graf',  'user', 'product','sallers'));
    }
}
