<?php

namespace App\Http\Controllers\Web\Panel;


// Helpers
use App\Helpers\CategoryHelper;
use App\Helpers\FileHelper;
use App\Models\AutopayDebitHistory;
use App\Models\BuyerAddress;
use App\Models\CatalogCategoryLanguage;
use App\Models\Company;
use App\Models\Contract;
use App\Models\CronUsersDelays;
use App\Models\File;
use App\Models\KatmRegion;
use App\Models\MyIDJob;
use App\Models\NotarySetting;
use App\Models\User;
use App\Models\CollectionDocument;

// Controllers
use App\Http\Controllers\Core\ContractController as Controller;
use App\Http\Controllers\Core\LetterController;

// Requests
use App\Http\Requests\Core\LetterController\LetterFillingDataRequest;
use App\Http\Requests\Web\Panel\ContractController\LetterGenerateWordDocumentRequest;

// Services
use App\Services\API\V3\BaseService;
use App\Services\API\V3\CatalogCategoryService;
use App\Services\Web\Panel\ContractController\GenerateWordFourthLetterService; // PhpWord Document Generator

// Laravel and other packages' Classes & Facades
use Barryvdh\DomPDF\PDF;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

// PhpWord Document Generator
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ContractController extends Controller // Controller = App\Http\Controllers\Core\ContractController as Controller
{

    /**
     * @param Collection | array $items
     *
     * @return array
     */
    protected function formatDataTables($items)
    {
        $request = request();
        $recover = 0;
        if ($request->has('recovery')) {
            $recover = 1;
        }

        $i = 0;
        $data = [];
        foreach ($items as $item) {

            $allowedCost = $item->collcost ? 0 : 1;  // разрешение создавать для текущего договора Расходы взыскания (1-да,0-нет)

            if (!$item->buyer) continue;

            $extinguished = $item->total - $item->balance;
            $debtsClass = $item->totalDebt > 0 ? 'red' : '';
            $insurance = isset($item->insurance) && $item->insurance->status > 0 ? '2' : '1';
            $lawsuit = (isset($item->lawsuit) && $item->lawsuit->status > 0) ? '2' : '1';
            $companyName = isset($item->company) ? $item->company->name : '';
            $contractLink = '<a href="' . localeRoute('panel.contracts.show', $item) . '">' . $item->id . '</a>';

            if ((int)$item->buyer->gender == 2) {
                $gender = 'Ж';
            } elseif ((int)$item->buyer->gender == 1) {
                $gender = 'М';
            } else {
                $gender = '-';
            }

            $data[$i][] = '<div class="created_at">' . $item->created_at . '</div>';
            $data[$i][] = '<div class="contract_id">' . $contractLink . '</div>';
            $data[$i][] = '<div class="partner">' . $companyName . '</div>';
            $data[$i][] = '<div class="client"><a target="_blank" href="' . localeRoute('panel.buyers.show', $item->buyer) . '">' . $item->buyer->fio . '</a></div>';
            $data[$i][] = '<div class="gender">' . $gender . '</div>';
            $data[$i][] = "<div class='birth_date'>{$item->buyer->birth_date}</div>";
            $data[$i][] = '<div class="phone">' . $item->buyer->phone . '</div>';
            $data[$i][] = '<div class="total">' . number_format($item->total, 2, '.', '&nbsp;') . '/<span class="period">' . Contract::find($item->id)->period . '</span></div>';
            $data[$i][] = '<div class="extinguished">' . number_format($extinguished, 2, '.', '&nbsp;') . '</div>';
            $data[$i][] = '<div class="debts ' . $debtsClass . '">' . number_format($item->totalDebt, 2, '.', '&nbsp;') . '</div>';
            $data[$i][] = '<div class="day ' . $debtsClass . '">' . $item->expired_days . '</div>';
            $data[$i][] = '<div class="status-' . $item->status . '">' . __('contract.status_' . $item->status) . '</div>';


            if ($recover == 1) {

                if(in_array($item->recovery,[1,3,7])){
                    $data[$i][] = '<span>Нет</span>';
                }else {

                    $onclick = 'onclick="openModal(' . 'this,' . $item->id . ',' . $item->recovery . ',' . $item->user_id . ')"';

                    if($item->recovery == 4){ // только если создан договор взыскания, можно передать в МИБ, иначе нельзя
                        if($allowedCost){  // договора еще нет, отключаем кнопку
                            $onclick = '';
                        }
                    }

                    $data[$i][] = '<button style="font-size: 14px;" class="change-status btn btn-block btn-danger" data-title="' . __('panel/contract.tab_recover_' . $item->recovery) . '"
                             '. $onclick .'
                             >'
                        . __('panel/contract.button_recover_' . ((int)$item->recovery + 1)) .
                        '</button>';

                }
                $data[$i][] = '<div class="small">' . ($item->generalCompany->name_ru ?? '') . '</div>';

                if($item->recovery==6) {
                    $data[$i][] = $item->date_recovery_start;
                }else{
                    $data[$i][] = '';
                }

            } else {
                $data[$i][] = '<div class="insurance"><img src="/images/icons/icon_insurance' . $insurance . '.svg" alt=""></div>';
                $data[$i][] = '<div class="lawsuit"><img src="/images/icons/icon_lawsuit' . $lawsuit . '.svg" alt=""></div>';
            }

            // создать договор Расходы взыскания - кнопка
            if($allowedCost) {

                if($item->recovery === 4) {
                    $data[$i][] = ' <button style="font-size: 14px; width: 250px" id="collect_cost' .$item->id. '"
                                    onclick="confirmCost('.$item->id.')" type="submit" class="btn btn-primary"
                                    title=" ' . __('panel/lawsuit.btn_create_сollection_costs') . '">' . __('panel/lawsuit.btn_create_сollection_costs') . '
                                    </button>';
                } else {
                    $data[$i][] = '';
                }
            } else {
                $data[$i][] = number_format($item->collcost->fix, 2, '.', '&nbsp;') . '/'
                    . number_format($item->collcost->persent, 2, '.', '&nbsp;') . '/'
                    . number_format($item->collcost->amount, 2, '.', '&nbsp;');
            }

            if($item->act_status == 3)
                $data[$i][] = '<div class="act_status">Да</div>';
            else if($item->act_status == 0 || $item->act_status == 1 || $item->act_status == 2)
                $data[$i][] = '<div class="act_status">Нет</div>';
            else
                $data[$i][] = '<div class="act_status"></div>';

            if($item->imei_status == 1 )
                $data[$i][] = '<div class="imei_status">Да</div>';
            else if($item->imei_status == 0 || $item->imei_status == 2 || $item->imei_status == 3)
                $data[$i][] = '<div class="imei_status">Нет</div>';
            else
                $data[$i][] = '<div class="imei_status"></div>';

            if($item->client_status == 1)
                $data[$i][] = '<div class="client_status">Да</div>';
            else if($item->client_status == 0 || $item->client_status == 2 || $item->client_status == 3)
                $data[$i][] = '<div class="client_status">Нет</div>';
            else
                $data[$i][] = '<div class="client_status"></div>';

            $i++;
        }

        return parent::formatDataTables($data);
    }

    // получение срока просрочки
    private function getRecoveriesDay($recoveries){

        $max_date = 0;

        $result = [
            'day'=>'', // дней отсрочки
            'date' => '', // дата отсрочки
            'comment'=>'', // коммент
            'day_delay'=>0 // осталось дней
        ];
        foreach ($recoveries as $recover){
            if($recover->day>0){
                if($max_date < strtotime($recover->created_at)) {
                    $max_date = strtotime($recover->created_at);
                    $result =[
                        'day'=>$recover->day, // дней отсрочки
                        'date' => $recover->created_at, // дата отсрочки
                        'comment'=> $recover->comment, // коммент
                        'day_delay'=> (int)((time()-$max_date)/86400) // осталось дней
                    ];
                }
            }
        }
        return $result;

    }


    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $user = Auth::user();

        if ( !$user->can('modify', new Contract()) ) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        }

        $debts = CronUsersDelays::select(DB::raw('SUM(balance) as balance'))->first();
        $debts = number_format($debts->balance, 2, '.', ' ');

        return view('panel.contract.index', compact('user', 'debts'));
    }

    public function show($id)
    {
        $result = $this->detail($id);


        if ($result['status'] !== 'success' && $result['response']['code'] === 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        } else {
            $contract = $result['data'];  // контракт
            $autopayDebitHistory = AutopayDebitHistory::select('percent','balance')
                                ->where('contract_id', $contract->id)
                                ->where('percent', ">", 0)
                                ->first()
            ;

            $scheduleCost   = $contract->collcost->schedule ?? null;  // график оплаты для договора Расходы взыскания (если он есть)
            $allowedCost    = $contract->InCollcost ? 0 : 1;  // разрешение создавать для текущего договора Расходы взыскания (1-да,0-нет)
            $collcost       = $contract->collcost;
            $signedContract = $contract->signedContract->path ?? null;
            $category       = 0;
            if ( $contract->order && ( $products = $contract->order->products ) ) {
                foreach ($products as $product) {
                    if ($product->category_id == 1) {
                        $category = 1;
                        break;
                    }
                }
                // категория продукта
                foreach ($products as $product) {
                    $productCategoriesIds = CategoryHelper::getParentCategoryIDs($product->category_id);
                    $productCategoriesImploded = implode(',',$productCategoriesIds);

                    if ($productCategoriesIds && $productCategoriesImploded) {

                        $productCategory = CatalogCategoryLanguage::where('language_code', App::getLocale())
                            ->whereIn('category_id', $productCategoriesIds)
                            ->orderByRaw("FIELD(category_id, $productCategoriesImploded) desc")
                            ->pluck('title')
                            ->toArray();

                        $product->category = implode(' \\ ', $productCategory);
                    }

                }
            }
            $denay_reasons = explode('|', __('panel/contract.cancel_messages'));
            $manager = null;
            if($manager_id = (Company::find($contract->company_id))->manager_id){
                $manager = User::find($manager_id);
            }
            $contract->ACTIVE_STATUSES = [
                Contract::STATUS_ACTIVE,            // contract->status = 1
                Contract::STATUS_OVERDUE_60_DAYS,   // contract->status = 3
                Contract::STATUS_OVERDUE_30_DAYS,   // contract->status = 4
                ($contract->status == Contract::STATUS_COMPLETED && empty($contract->cancel_reason)) ? Contract::STATUS_COMPLETED : ''   // contract->status = 9
            ];
            return view(
                'panel.contract.show',
                compact(
                    'contract',
                    'category',
                    'denay_reasons',
                    'scheduleCost',
                    'allowedCost',
                    'collcost',
                    'manager',
                    'signedContract',
                    'autopayDebitHistory'
                )
            );
        }
    }

    public function executiveLetterFourth(Request $request)
    {
        $result = $this->detail($request->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        }

        return view('panel.contract.executive_letter_fourth', ['contract_id' => $request->id, 'notary_id' => $request->notary_id]);
    }

    public function executiveLetterFirst(Request $request)
    {
        $result = $this->detail($request->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        }

        return view('panel.contract.executive_letter_first');
    }

    public function executiveLetterSecond(Request $request)
    {
        $result = $this->detail($request->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        }

        return view('panel.contract.executive_letter_second');
    }

    public function executiveLetterThird(Request $request)
    {
        $result = $this->detail($request->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        }

        return view('panel.contract.executive_letter_third');
    }

    public function enforcementAgencyLetter(Request $request)
    {
        $result = $this->detail($request->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        }

        return view('panel.contract.letter_to_enforcement_agency');
    }

    public function courtContractPrintForm(Request $request)
    {
        $contract_id = $request->contract_id;

        return view('panel.contract.court_contract_print_form', compact('contract_id'));

    }

//    public function workplaceLetter(Request $request) // Было раньше вместо residencyLetterTwo() Письма домой 2.
//    {
//        $result = $this->detail($request->id);
//
//        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
//            $this->message('danger', __('app.err_access_denied'));
//            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
//        }
//
//        return view('panel.contract.letter_to_workplace');
//    }

    public function residencyLetterTwo(Request $request)
    {
        $result = $this->detail($request->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        }

        return view('panel.contract.letter_to_residency_2');
    }
    public function residencyLetter(Request $request)
    {
        $result = $this->detail($request->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        }

        return view('panel.contract.letter_to_residency');
    }

    public function requirement(Request $request)
    {
        $result = $this->detail($request->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        }

        return view('panel.contract.requirement');
    }

    public function myIdFormOne(MyIDJob $myid, Contract $contract) {
        $forma = [];

        $forma['names'] = $myid->profile['common_data']['first_name']. " ".$myid->profile['common_data']['last_name']." ".$myid->profile['common_data']['middle_name'];
        $forma['first_name'] = $myid->profile['common_data']['first_name'];
        $forma['middle_name'] = $myid->profile['common_data']['middle_name'];
        $forma['last_name'] = $myid->profile['common_data']['last_name'];
        $forma['photo_from_camera'] =  ($img = File::where('user_id',$myid->user_id)->where('type','passport_selfie')) ? $img->orderBy('id',"DESC")->first()->getPreviewAttribute() : "Image not exist!";
        $forma['pinfl'] = $myid->profile['common_data']['pinfl'];
        $forma['gender'] = $myid->profile['common_data']['gender'] == '1' ? "Мужчина" : "Женщина" ;
        $forma['birth_date'] = $myid->profile['common_data']['birth_date'];
        $forma['pass'] = $myid->profile['doc_data']['pass_data'];
        $forma['doc_type'] = $myid->profile['doc_data']['doc_type'];
        $forma['pass_data'] = $myid->profile['doc_data']['pass_data'];
        $forma['issued_date'] = $myid->profile['doc_data']['issued_date'];
        $forma['expire_date'] = $myid->profile['doc_data']['expiry_date'];
        $forma['issued_by'] = $myid->profile['doc_data']['issued_by']." / ".$myid->profile['doc_data']['issued_by_id'];
        $forma['nationality'] = $myid->profile['common_data']['nationality'];
        $forma['citizenship'] = $myid->profile['common_data']['citizenship'];

        $forma['birth_country'] = $myid->profile['common_data']['birth_country'];
        $forma['birth_region'] = $myid->profile['common_data']['birth_place'];
        $forma['birth_district'] = null;

        $forma['permanent_country'] = $myid->profile['address']['permanent_registration']['country'];

        $forma['permanent_district'] = ($local_region = (User::find($myid->user_id)->first())->local_region) ? KatmRegion::where('local_region',($local_region))->first()->local_region_name :  $myid->profile['address']['permanent_registration']['district'];
        $forma['permanent_region'] = ($region = (User::find($myid->user_id)->first())->region) ? KatmRegion::where('region',($region))->first()->region_name :  $myid->profile['address']['permanent_registration']['region'];
        $forma['permanent_address'] = BuyerAddress::where('user_id',($myid->user_id))->first()->region_name ??  $myid->profile['address']['permanent_registration']['address'];


        $forma['temporary_country'] = $myid->profile['address']['temporary_registration']['country'];
        $forma['temporary_district'] = $myid->profile['address']['temporary_registration']['district'];
        $forma['temporary_region'] = $myid->profile['address']['temporary_registration']['region'];
        $forma['temporary_address'] = $myid->profile['address']['temporary_registration']['address'];


        return view('panel.contract.form_1', [ 'forma' => (object) $forma , 'myid' => $myid ]);
    }

    public function myIdFormOneDocX(MyIDJob $myid) {
        header('Content-Disposition: attachment; filename="myid_forma_'.$myid->id.'.doc"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private');
        header('Pragma: private');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

        $forma = [];

        $forma['names'] = $myid->profile['common_data']['first_name']. " ".$myid->profile['common_data']['last_name']." ".$myid->profile['common_data']['middle_name'];
        $forma['first_name'] = $myid->profile['common_data']['first_name'];
        $forma['middle_name'] = $myid->profile['common_data']['middle_name'];
        $forma['last_name'] = $myid->profile['common_data']['last_name'];
        $forma['photo_from_camera'] =  ($img = File::where('user_id',$myid->user_id)->where('type','passport_selfie')) ? $img->orderBy('id',"DESC")->first()->getPreviewAttribute() : "Image not exist!";
        $forma['pinfl'] = $myid->profile['common_data']['pinfl'];
        $forma['gender'] = $myid->profile['common_data']['gender'] == '1' ? "Мужчина" : "Женщина" ;
        $forma['birth_date'] = $myid->profile['common_data']['birth_date'];
        $forma['pass'] = $myid->profile['doc_data']['pass_data'];
        $forma['doc_type'] = $myid->profile['doc_data']['doc_type'];
        $forma['pass_data'] = $myid->profile['doc_data']['pass_data'];
        $forma['issued_date'] = $myid->profile['doc_data']['issued_date'];
        $forma['expire_date'] = $myid->profile['doc_data']['expiry_date'];
        $forma['issued_by'] = $myid->profile['doc_data']['issued_by']." / ".$myid->profile['doc_data']['issued_by_id'];
        $forma['nationality'] = $myid->profile['common_data']['nationality'];
        $forma['citizenship'] = $myid->profile['common_data']['citizenship'];

        $forma['birth_country'] = $myid->profile['common_data']['birth_country'];
        $forma['birth_region'] = $myid->profile['common_data']['birth_place'];
        $forma['birth_district'] = null;

        $forma['permanent_country'] = $myid->profile['address']['permanent_registration']['country'];

        $forma['permanent_district'] = ($local_region = (User::find($myid->user_id)->first())->local_region) ? KatmRegion::where('local_region',($local_region))->first()->local_region_name :  $myid->profile['address']['permanent_registration']['district'];
        $forma['permanent_region'] = ($region = (User::find($myid->user_id)->first())->region) ? KatmRegion::where('region',($region))->first()->region_name :  $myid->profile['address']['permanent_registration']['region'];
        $forma['permanent_address'] = BuyerAddress::where('user_id',($myid->user_id))->first()->region_name ??  $myid->profile['address']['permanent_registration']['address'];


        $forma['temporary_country'] = $myid->profile['address']['temporary_registration']['country'];
        $forma['temporary_district'] = $myid->profile['address']['temporary_registration']['district'];
        $forma['temporary_region'] = $myid->profile['address']['temporary_registration']['region'];
        $forma['temporary_address'] = $myid->profile['address']['temporary_registration']['address'];

        return view('panel.contract.letters.form_1_docx', [ 'forma' => (object) $forma]);
    }

    public function myIdFormOnePdf(MyIDJob $myid){
        $forma = [];

        $forma['names'] = $myid->profile['common_data']['first_name']. " ".$myid->profile['common_data']['last_name']." ".$myid->profile['common_data']['middle_name'];
        $forma['first_name'] = $myid->profile['common_data']['first_name'];
        $forma['middle_name'] = $myid->profile['common_data']['middle_name'];
        $forma['last_name'] = $myid->profile['common_data']['last_name'];
        $forma['photo_from_camera'] =  ($img = File::where('user_id',$myid->user_id)->where('type','passport_selfie')) ? $img->orderBy('id',"DESC")->first()->getPreviewAttribute() : "Image not exist!";
        $forma['pinfl'] = $myid->profile['common_data']['pinfl'];
        $forma['gender'] = $myid->profile['common_data']['gender'] == '1' ? "Мужчина" : "Женщина" ;
        $forma['birth_date'] = $myid->profile['common_data']['birth_date'];
        $forma['pass'] = $myid->profile['doc_data']['pass_data'];
        $forma['pass_data'] = $myid->profile['doc_data']['pass_data'];
        $forma['doc_type'] = $myid->profile['doc_data']['doc_type'];
        $forma['issued_date'] = $myid->profile['doc_data']['issued_date'];
        $forma['expire_date'] = $myid->profile['doc_data']['expiry_date'];
        $forma['issued_by'] = $myid->profile['doc_data']['issued_by']." / ".$myid->profile['doc_data']['issued_by_id'];
        $forma['nationality'] = $myid->profile['common_data']['nationality'];
        $forma['citizenship'] = $myid->profile['common_data']['citizenship'];

        $forma['birth_country'] = $myid->profile['common_data']['birth_country'];
        $forma['birth_region'] = $myid->profile['common_data']['birth_place'];
        $forma['birth_district'] = null;

        $forma['permanent_country'] =  $myid->profile['address']['permanent_registration']['country'];

        $forma['permanent_district'] = ($local_region = (User::find($myid->user_id)->first())->local_region) ? KatmRegion::where('local_region',($local_region))->first()->local_region_name :  $myid->profile['address']['permanent_registration']['district'];
        $forma['permanent_region'] = ($region = (User::find($myid->user_id)->first())->region) ? KatmRegion::where('region',($region))->first()->region_name :  $myid->profile['address']['permanent_registration']['region'];
        $forma['permanent_address'] = BuyerAddress::where('user_id',($myid->user_id))->first()->region_name ??  $myid->profile['address']['permanent_registration']['address'];

        $forma['temporary_country'] = $myid->profile['address']['temporary_registration']['country'];
        $forma['temporary_district'] = $myid->profile['address']['temporary_registration']['district'];
        $forma['temporary_region'] = $myid->profile['address']['temporary_registration']['region'];
        $forma['temporary_address'] = $myid->profile['address']['temporary_registration']['address'];
        $forma = (object) $forma;
        $pdf = PDF::loadView('panel.contract.letters.form_1_pdf', compact('forma'));
        $pdf->setOptions([
            'isFontSubsettingEnabled' => true,
            'isRemoteEnabled' => true,
            'tempDir' => storage_path('logs/'),
            'fontCache' => storage_path('fonts/'),
            'logOutputFile' => storage_path('logs/log.htm')
        ]);
        return  $pdf->download('myid_forma_'.$myid->id.".pdf");
    }

    /**
     * @param LetterGenerateWordDocumentRequest $request
     * @param Contract $contract
     * @param NotarySetting $notary
     * @return StreamedResponse
     */
    public function letterGenerateWordDocument(LetterGenerateWordDocumentRequest $request, Contract $contract, NotarySetting $notary) : StreamedResponse
    {
        $validated = $request->validated();

        $dummyRequest = new LetterFillingDataRequest();
        $dummyRequest->merge([
            "contract_id" => $contract->id,
            "notary_id" => $notary->id,
        ]);
        $letter = (new LetterController)->letterFillingData($dummyRequest)->resolve();

        $word_generate_service = new GenerateWordFourthLetterService();

        $needed_variables = $word_generate_service->prepare_variables($validated, $letter);

        [$phpWord_object, $dir] = $word_generate_service->generateWord($needed_variables);

        $file_name = "court_application_" . $contract->id . ".docx";

        $contents = "";
        try {
            $objectWriter = IOFactory::createWriter($phpWord_object);
            ob_start();
            $objectWriter->save("php://output");
            $contents = ob_get_clean();
            rmdir($dir);
        } catch (Exception $e) {
            // "Couldn't get contents of generated Word document"
            abort(500, __("panel/lawsuit.err_couldn't_save_generated_word_docx_in_php_memory"));
        }

// =====================================================================================================================

        $file_data = [
            "type"          => File::TYPE_FOURTH_EXECUTE,       // string
            "model"         => File::MODEL_CONTRACTS_RECOVERY,  // string
            "element_id"    => $contract->id,                   // int
            "user_id"       => Auth::id(),                      // int
            "extension"     => "docx"
        ];
        $file_path = FileHelper::saveLetterFile($contents, $file_data);

        if ( !$file_path ) {
            Log::channel("letters")->error('Error. Executive-letter-fourth file not saved.');
            BaseService::handleError( [ __( 'app.err_save' ) ], 'error', 500);
        }
        // Create CollectionDocument record in DB table:
        $collection_document = new CollectionDocument();
        $collection_document->contract_id = $contract->id;
        $collection_document->user_id     = Auth::id();
        $collection_document->type        = File::TYPE_FOURTH_EXECUTE;
        $collection_document->file_link   = FileHelper::url($file_path);
        $collection_document->save();
// =====================================================================================================================

        return response()->streamDownload(function () use ($contents) {
            echo $contents;
        }, $file_name);
    }

}
