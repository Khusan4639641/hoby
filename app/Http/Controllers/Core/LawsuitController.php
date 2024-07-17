<?php

namespace App\Http\Controllers\Core;

use App\Http\Requests\Core\LawsuitController\GetNotariesListRequest;
use App\Http\Requests\Core\LawsuitController\StoreExecutiveWritingRequest;
use App\Models\ContractLawsuit as Model;
use App\Models\CollectCost;
use App\Models\Contract;
use App\Models\ContractInvoice;
use App\Models\ExecutiveWriting;
use App\Models\NotarySetting;
use App\Models\User;
use App\Services\API\V3\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Http\Requests\SaveInvoiceNumber;  // Form Request (валидатор)


class LawsuitController extends CoreController
{
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Eager load
        $this->loadWith = [];
    }


    public function add(Request $request){
        $user = Auth::user();
        $contract = Contract::find($request->contract_id);

        if($contract){
            $lawsuit = new Model();
            $lawsuit->contract_id       = $contract->id;
            $lawsuit->user_id           = $contract->buyer->id;
            //$lawsuit->insurance_id      = $contract->insurance->id;
            $lawsuit->status            = $request->status;
            $lawsuit->date_filling      = $request->date_filling;
            $lawsuit->date_review       = $request->date_review;
            $lawsuit->number            = $request->number;
            $lawsuit->judical_authority = $request->judical_authority;
            $lawsuit-> date_effective   = $request-> date_effective ;
            $lawsuit->date_decision     = $request->date_decision;
            $lawsuit->save();


            $contract->buyer->status = 8;
            $contract->buyer->save();

            $this->result['status'] = 'success';
            $this->message('success', __('panel/lawsuit.txt_request_created'));
            $this->result['data'] = $lawsuit;
            $this->result['data']['status_caption'] = __('lawsuit.status_'.$lawsuit->status);

        }else {
            $this->result['status'] = 'error';
            $this->message('danger', __('panel/lawsuit.err_contract_not_found'));
        }

        return $this->result();
    }

    public function checkCanSaveInvoiceNumber(Request $request)
    {
        $invoice_number = ContractInvoice::where('contract_id', '=', $request->contract_id_invoice)->get()->toArray();
        $there_is_already_invoice_number_with_this_contract_id = (bool)$invoice_number;

        if ( !$there_is_already_invoice_number_with_this_contract_id ) {
            $this->result['status'] = 'success';
        } else {
            $this->result['status'] = 'error';
            if ( $invoice_number[0]["is_fix_type_invoice"] && $invoice_number[1]["is_percent_type_invoice"] ) {
                $this->result['data']['invoice_number_fix'] = $invoice_number[0]["invoice_number"];
                $this->result['data']['invoice_number_percent'] = $invoice_number[1]["invoice_number"];
            } elseif ( $invoice_number[1]["is_fix_type_invoice"] && $invoice_number[0]["is_percent_type_invoice"] ) {
                $this->result['data']['invoice_number_fix'] = $invoice_number[1]["invoice_number"];
                $this->result['data']['invoice_number_percent'] = $invoice_number[0]["invoice_number"];
            }
            $this->message('error', __('panel/contract_invoice.invoice_number_with_this_contract_id'));
        }
        return $this->result();
    }

    public function getNotariesList(GetNotariesListRequest $request)
    {
        $contract = Contract::find($request->contract_id);

        $contractNotary = $contract->collcost->notary ?? "";
        if ( $contractNotary && $contractNotary->is_visible ) {
            $contractNotary = $contract->collcost->notary ?? '';
        } else {
            $contractNotary = "";
        }

        $notaries = NotarySetting::where('is_visible', 1)->get();
        $this->result['status'] = 'success';
        $this->result['code'] = 200;
        $this->result['data']['notaries_list'] = $notaries;
        $this->result['data']['contract_notary'] = $contractNotary;

        return $this->result();
    }

    public function saveInvoiceNumber(SaveInvoiceNumber $request)
    {
        $buyer = User::find($request->user_id_invoice);
        $contract = Contract::find($request->contract_id_invoice);

        $there_is_already_invoice_number_with_this_contract_id = (bool)ContractInvoice::where('contract_id', '=', $request->contract_id_invoice)->count();
        $there_is_already_fix = (bool)ContractInvoice::where('invoice_number', '=', $request->fix_inv_number)
                                        ->where('is_fix_type_invoice', '=', true)
                                        ->where('is_percent_type_invoice', '=', false)->count();
        $there_is_already_percent = (bool)ContractInvoice::where('invoice_number', '=', $request->percent_inv_number)
                                        ->where('is_fix_type_invoice', '=', false)
                                        ->where('is_percent_type_invoice', '=', true)->count();

        if ($there_is_already_invoice_number_with_this_contract_id) {
            $this->result['status'] = 'error';
            $this->message('error', __('panel/contract_invoice.invoice_number_with_this_contract_id'));
        } elseif ( $there_is_already_fix && $there_is_already_percent ) {
            $this->result['status'] = 'error';
            $this->message('error', __('panel/contract_invoice.invoice_number_already_exists'));
        } elseif ( $there_is_already_fix ) {
            $this->result['status'] = 'error';
            $this->message('error', __('panel/contract_invoice.invoice_number_already_exists_fix'));
        } elseif ( $there_is_already_percent ) {
            $this->result['status'] = 'error';
            $this->message('error', __('panel/contract_invoice.invoice_number_already_exists_percent'));
        } elseif ( $contract && ( $request->fix_inv_number && $request->percent_inv_number ) ){
            ContractInvoice::create([
                'contract_id'                   => $contract->id,
                'user_id'                       => $buyer->id,
                'invoice_number'                => $request->fix_inv_number,
                'fix_debt'                      => $contract->collcost->fix,
                'percent_debt'                  => null,
                'is_fix_type_invoice'           => true,
                'is_percent_type_invoice'       => false,
            ]);
            ContractInvoice::create([
                'contract_id'                   => $contract->id,
                'user_id'                       => $buyer->id,
                'invoice_number'                => $request->percent_inv_number,
                'fix_debt'                      => null,
                'percent_debt'                  => $contract->collcost->persent,
                'is_fix_type_invoice'           => false,
                'is_percent_type_invoice'       => true,
            ]);

            $this->result['status'] = 'success';
            $this->message('success', __('panel/contract_invoice.txt_invoice_successfully_saved'));
        } else {
            $this->result['status'] = 'error';
            $this->message('error', __('panel/lawsuit.err_contract_not_found'));
        }

        return $this->result();
    }

    public function modify(Request $request){
        $user = Auth::user();
        $lawsuit = Model::find($request->id);

        if($lawsuit){
            $lawsuit->status            = $request->status;
            $lawsuit->date_filling      = $request->date_filling;
            $lawsuit->date_review       = $request->date_review;
            $lawsuit->number            = $request->number;
            $lawsuit->judical_authority = $request->judical_authority;
            $lawsuit-> date_effective   = $request-> date_effective ;
            $lawsuit->date_decision     = $request->date_decision;
            $lawsuit->save();

            $this->result['status'] = 'success';
            $this->message('success', __('panel/lawsuit.txt_request_created'));
            $this->result['data'] = $lawsuit;
            $this->result['data']['status_caption'] = __('lawsuit.status_'.$lawsuit->status);

        }else {
            $this->result['status'] = 'error';
            $this->message('danger', __('panel/lawsuit.err_lawsuit_not_found'));
        }

        return $this->result();
    }

    /**
     * получение суммы договора взыскания
     *
     *
     * @param Request $request
     * @return mixed
     */
    public function getCollectionAmount(Request $request)
    {
        if($contract = Contract::find($request->contract_id))
        {
            $notaries     = NotarySetting::where('is_visible', 1)->get();
            $per          = Config::get( 'test.collect_persent' ); // процент от остаточной суммы долга договора
            $persent      = number_format($contract->balance * $per, 2, '.', ''); // проценты от остаточной суммы долга договора
//            $amount       = number_format($persent + $fix, 2, '.', '');
//            $total_amount = number_format($persent + $fix + $contract->balance, 2, '.', '');

            $data = [
                'debt'         => $contract->balance,
                'persent'      => $persent,
                'notaries'     => $notaries,
            ];

            $this->result['status'] = 'success';
            $this->message('success', __('panel/lawsuit.txt_success_created'));
            $this->result['data'] = $data;
        }
        else
        {
            $this->result['status'] = 'error';
            $this->message('success', __('panel/lawsuit.err_contract_not_found'));
        }

        return  $this->result;
    }

    public function addCollectionCost(Request $request)
    {
        if (!$contract = Contract::whereId($request->get('contract_id'))->with('buyer')->first()) {
            $this->result['status'] = 'error';
            $this->message('danger', __('panel/lawsuit.err_contract_not_found'));
        } else {
            if (!CollectCost::where('contract_id', $request->get('contract_id'))->first()) {
                $collectCost = $this->createCollectCost($request, $contract);
                $this->result['status'] = 'success';
                $this->message('success', __('panel/lawsuit.txt_success_created'));
                $this->result['data'] = $collectCost;
                $this->result['data']['status_caption'] = __('lawsuit.status_' . $collectCost->status);
            } else {
                $this->result['status'] = 'error';
                $this->message('danger', __('panel/lawsuit.err_lawsuit_exist'));
            }
        }

        return $this->result();
    }

    public function storeExecutiveWriting(StoreExecutiveWritingRequest $request)
    {
        ExecutiveWriting::create
        ([
            'user_id' => Contract::where('id', $request->contract_id)->pluck('user_id')->first(),
            'contract_id' => $request->contract_id,
            'registration_number' => $request->registration_number,
        ]);

        BaseService::handleResponse(['panel/contract.successfully_saved']);
    }

    private function createCollectCost($request, $contract)
    {
        return CollectCost::create(['contract_id'  => $request->get('contract_id'),
                                    'user_id'      => $contract->buyer->id,
                                    'status'       => 0,
                                    'fix'          => $request->get('fix'),
                                    'persent'      => $request->get('persent'),
                                    'amount'       => $request->get('amount'),
                                    'balance'      => $request->get('amount'),
                                    'total_amount' => $request->get('total_amount'),
                                    'notary_id'    => $request->get('notary_id'),
                                    'exp_days'     => $contract->expired_days]);
    }
}
