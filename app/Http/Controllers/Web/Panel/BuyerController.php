<?php


namespace App\Http\Controllers\Web\Panel;

use App\Helpers\EncryptHelper;
use App\Helpers\NdsStopgagHelper;
use App\Helpers\SmsHelper;
use App\Helpers\UniversalHelper;
use App\Http\Controllers\Core\EmployeeBuyerController as Controller;
use App\Libs\KycHistoryLibs;
use App\Models\AccountCBU;
use App\Models\Area;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\BuyerSetting;
use App\Models\CardScoringLog;
use App\Models\City;
use App\Models\Employee;
use App\Models\GeneralCompany;
use App\Models\KatmRegion;
use App\Models\KycHistory;
use App\Models\MyIDJob;
use App\Models\OrderProduct;
use App\Models\Payment;
use App\Models\User;
use App\Services\API\V3\UserPayService;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BuyerController extends Controller {

    private $defaultRegionId = 26; // 26 - Tashkent
    /**
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function index() {
        $user = Auth::user();

        if ( $user->can( 'modify', new Buyer()) ) {

            // Active orders
            /* $params = [
                 'status' => 4, // 4 - верифицирован
                 'total_only' => 'yes'
             ];*/

            // dev_nurlan 13.05.2022 13:42 (Убрали счётчики)
//            $counter['verified'] = User::whereRoleIs('buyer')->where('status',4)->where('company_id',null)->count(); // $this->filter($params)['total'];

            //Credit orders
            /* $params = [
                 'status' => 2, // 2 -
                 'total_only' => 'yes'
             ];*/
            $counter['verification'] = User::whereRoleIs('buyer')
                                        ->where('status', 2)
                                        ->where('company_id', null)
                                        ->count()
            ;
            //  $this->filter($params)['total'];

            //Credit orders
            /*$params = [
                'status' => 3, // доработка
                'total_only' => 'yes'
            ];*/

            // dev_nurlan 13.05.2022 13:42 (Убрали счётчики)
//            $counter['correction'] = User::whereRoleIs('buyer')->where('status',3)->where('company_id',null)->count(); // $this->filter($params)['total'];
//            $counter['photo'] = User::whereRoleIs('buyer')->where('status',5)->where('company_id',null)->count(); // $this->filter($params)['total'];
//            $counter['denied'] = User::whereRoleIs('buyer')->where('status',8)->where('company_id',null)->count(); // $this->filter($params)['total'];


            //Credit orders
            /*$params = [
                'total_only' => 'yes'
            ];*/

            // dev_nurlan 13.05.2022 13:42 (Убрали счётчики)
//            $counter['all'] = User::whereRoleIs('buyer')->where('company_id',null)->count(); // $this->filter($params)['total'];

            return view('panel.buyer.index', compact('counter'));
        } else {

            $this->message( 'danger', __( 'app.err_access_denied' ) );
            return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        }

    }


    /**
     * Buyer edit form
     * @param int $id
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function edit (int $id)
    {
        if(!Auth::user()->hasRole('admin')) {
            return abort(403);
        }

        $result = $this->detail($id);

        $buyer = $result['data'];

        $limits = Config::get('test.limits');
        $statuses = [2,3,4,9];

        $personals['passport_selfie']['id'] = $buyer->personals->passport_selfie->id ?? null;
        $personals['passport_selfie']['preview'] = isset($buyer->personals->passport_selfie) ? Storage::url($buyer->personals->passport_selfie->path) : null ;
        $personals['passport_selfie']['path'] = isset($buyer->personals->passport_selfie) ? $buyer->personals->passport_selfie->path : null ;
        $personals['passport_first_page']['id'] = $buyer->personals->passport_first_page->id ?? null;
        $personals['passport_first_page']['preview'] = isset($buyer->personals->passport_first_page) ? Storage::url($buyer->personals->passport_first_page->path) : null ;
        $personals['passport_first_page']['path'] = isset($buyer->personals->passport_first_page) ? $buyer->personals->passport_first_page->path : null ;
        // $personals['passport_with_address']['id'] = $buyer->personals->passport_with_address->id ?? null;
        //$personals['passport_with_address']['preview'] = isset($buyer->personals->passport_with_address) ? Storage::url($buyer->personals->passport_with_address->path) : null ;
        // $personals['passport_with_address']['path'] = isset($buyer->personals->passport_with_address) ? $buyer->personals->passport_with_address->path : null ;

        $addressResidential = $buyer->addressResidential ?? new BuyerAddress();
        $addressRegistration = $buyer->addressRegistration ?? new BuyerAddress();

        $nameLocale = 'name' . ucfirst(app()->getLocale());


        # Address selectors
        $addressResidential->areaList = '[]';
        $addressResidential->cityList = '[]';
        $addressRegistration->areaList = '[]';
        $addressRegistration->cityList = '[]';

        if(!is_null($addressResidential->region) && $addressResidential->region !== '')
            $addressResidential->areaList = Area::where('regionid', $addressResidential->region)->orderBy($nameLocale)->get();

        if(!is_null($addressResidential->area) && $addressResidential->area !== '')
            $addressResidential->cityList = City::where('areaid', $addressResidential->area)->orderBy($nameLocale)->get();

        if(!is_null($addressRegistration->region) && $addressRegistration->region !== '')
            $addressRegistration->areaList = Area::where('regionid', $addressRegistration->region)->orderBy($nameLocale)->get();

        if(!is_null($addressResidential->area) && $addressResidential->area !== '')
            $addressRegistration->cityList = City::where('areaid', $addressRegistration->area)->orderBy($nameLocale)->get();

        return view('panel.buyer.edit', compact('buyer', 'personals', 'addressRegistration', 'addressResidential', 'limits', 'statuses'));
    }

    /**
     * Buyer detail
     * @param int $id
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function show(int $id)
    {
        $result = $this->detail($id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403)
        {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.buyers.index'))->with('message', $this->result['response']['message']);
        }
        else
        {
            $default_region_id = $this->defaultRegionId; //default region_id in dropdown list
            $buyer = $result['data'];
            $verified_by = null;

            if($buyer->verified_by)
            {
                $employee = Employee::find($buyer->verified_by);
                $verified_by = $employee->fio;
            }

            if($buyer->vip)
                $reasons = explode('|', __('panel/buyer.verify_messages_vip'));
            else
                $reasons = explode('|', __('panel/buyer.verify_messages'));

            $katm_status = 11;
            $history     = KycHistory::where('user_id', $buyer->id)->with('kyc')->get();
            $katm_region = KatmRegion::where([['region', $buyer->region],['local_region', $buyer->local_region]])->first();

            $buyer_personal_validation_rules = [
//                "dimensions" => [
//                    "w" => 1024,
//                    "h" => 720
//                ],
                "dimensions" => null,
                "size" => 35840,
                "mimes" => [
                    ".bmp",
                    ".jpe",
                    ".jpg",
                    ".jpeg",
                    ".png",
                    ".webp"
                ]
            ];
            $myid_report = MyIDJob::where('user_id',$id)->where('result_code',1)->orderBy('id','DESC')->exists();

            $myId = MyIDJob::where('user_id', $buyer->id)->orderBy('created_at', 'desc')->first() ?? null;
            return view('panel.buyer.show', compact(
                    'buyer',
                    'reasons',
                    'verified_by',
                    'katm_status',
                    'history'/*, 'cards_pnfl'*/ /* 'acts' */,
                    'katm_region',
                    'default_region_id',
                    'myId',
                    'myid_report',
                )
            );
        }
    }

    /**
     * Buyers list data table
     * @param array|Collection $buyers
     * @return array
     */
    protected function formatDataTables( $buyers ) {

        $i    = 0;
        $data = [];

        $statuses = [
            0=>'Новый',
            1=>'Новый',
            2=>'Ожидание',
            3=>'Отказ',
            4=>'Верифицирован',
            5=>'Паспорт',
            6=>'Паспорт',
            7=>'Паспорт',
            8=>'Блокирован',
            9=>'Блокирован',
            10=>'Селфи',
            11=>'Прописка',
            12=>'Доверитель',
        ];

        $rejected_statuses = [3, 5, 6, 7, 8, 9, 10, 11, 12];



        foreach ( $buyers as $buyer ) {

            $debtClass = $buyer->totalDebt > 0 ? 'red': '';

            $passport_number = $buyer->personals->passport_number ?? '';

            if ( isset($buyer->kyc) ) {
                $kyc_user = $buyer->kyc->name . ' ' . $buyer->kyc->surname;
            }
            else {
                $kyc_user = '';
            }

            $reason = KycHistoryLibs::getKycReason($buyer->history->reason ?? null); // получить причину
            $status = @$statuses[$buyer->status];

            if(!isset($statuses[$buyer->status])) {
                Log::channel('users')->info('UserID: ' . $buyer->id . ' - status not found: ' . $buyer->status);
            }

            $icon = $buyer->status == 4 ? '<img class="icon-status" src="/images/icons/icon_ok_circle_green.svg" />' : '<img class="icon-status" src="/images/icons/icon_attention.svg" />';

            if($buyer->gender==2){
                $gender = 'Ж';
            }elseif ($buyer->gender==1){
                $gender = 'М';
            }else{
                $gender = '-';
            }

            // black-list status
            $black_list = $buyer->black_list == 1 ?  "<img src='/images/black_list.png' width='25' height='25'/>" : '';

            // TODO: Убрать формирование HTML из контроллера!!! (08.07.2022 DEV-277/DEV-289/feature)

            if ( in_array($buyer->status, $rejected_statuses, true) ) {
                $data[ $i ][] = $icon . ' ' . $status . '<br><br>' . "<div class='passport'>{$reason}</div>";
            } else {
                $data[ $i ][] = $icon . ' ' . $status . '<br><br>';
            }

            $data[ $i ][] = "<div class='kyc-user'>{$kyc_user}</div>";
            $data[ $i ][] = "<div class='updated'>{$buyer->updated_data}</div>";
            $data[ $i ][] = "<div class='id'><a href='".localeRoute('panel.buyers.show', $buyer)."'></a>ID {$buyer->id}</div>";
            $data[ $i ][] = "<div class='fio'>{$buyer->fio}</div>";
            $data[ $i ][] = "<div class='passport'>{$passport_number}</div>";
            $data[ $i ][] = "<div class='gender'>{$gender}</div>";
            $data[ $i ][] = "<div class='birth_date'>{$buyer->birth_date}</div>";
            $data[ $i ][] = "<div class='phone'>{$buyer->phone}</div>";


            $data[ $i ][] = $buyer->settings->limit ?? 0; // 20

            $data[ $i ][] = "<div class='debt {$debtClass}'>{$buyer->totalDebt}</div>";
            /*$data[ $i ][] = "<div class='rating'>{$item->settings->rating}</div>";*/

            // black-list-status
            $data[ $i ][] = "$black_list";

            $i ++;
        }

        return parent::formatDataTables( $data );
    }


    // запрос скоринга карты для пользователей, у которых статус 1
    public function rescoring(Request $request){

        if($request->isMethod('POST')) {

            $query = CardScoringLog::has('user')
                ->select('card_scoring_logs.*')
                ->leftJoin('users', function ($query) {
                    $query->on('users.id', 'card_scoring_logs.user_id');
                })->where('users.status', 1)
                ->where('card_scoring_logs.status', 0)
                ->whereIn('ball', [2, 3, 4]);

            $scoring = $query->get();

            if ($scoring) {

                $m1 = date('m');

                $day = date('d', time());

                if ($day < 25) {
                    $months = ' -6 month'; // текущий месяц + еще 6 прошедших
                } else {
                    $months = ' -5 month'; // текущий месяц + еще 5 прошедших
                }

                $to = date('Ym25', time());

                $from = date('Ym01', strtotime($to . $months));

                $timer = 0;

                foreach ($scoring as $item) {

                    // дата последнего скоринга
                    $m2 = date('m', strtotime($item->updated_at));

                    /**
                     * 1) Необходимо в card_scoring_logs вытащить всех пользователей у кого количество баллов равняется 4, 3, 2;
                     * 2) Посмотреть дату скоринга, если от текущей даты и даты последнего скоринга прошло 1,2,3  месяца (в зависимости от баллов)
                     * то дать повторную проверку на скоринг // В повторной проверке проводится текущая логика скоринга.
                     *
                     * Пример
                     * Пользователи с 4 баллами - 20 шт   // смотрим дату скоринга, если от даты скоринга прошел 1 месяц то скорим.
                     * Пользователи с 3 баллами - 30 шт // смотрим дату скоринга, если от даты скоринга прошел 2 месяц то скорим.
                     * Пользователи с 2 баллами - 40 шт // смотрим дату скоринга, если от даты скоринга прошел 3 месяц то скорим.
                     */

                    $response = json_decode($item->request, true);

                    if (isset($response['params'])) {

                        $_request = [
                            'info_card' => [
                                'card_number' => EncryptHelper::decryptData($response['params']['card_number']),
                                'card_valid_date' => $response['params']['expire']
                            ],
                            'start_date' => $from,
                            'end_date' => $to,
                            // 'phone' => $card_phone,
                        ];

                        $dm = abs($m1 - $m2);

                        if (($dm >= 1 && $item->ball == 4) ||
                            ($dm >= 2 && $item->ball == 3) ||
                            ($dm >= 3 && $item->ball == 2)
                        ) {

                            // тестовый локальный скоринг

                            // прод
                            $result = UniversalHelper::getScoring($_request);

                            Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
                            $scoring_result = UniversalHelper::scoringScore($result['response']['result']);

                            $scoring = $scoring_result['scoring'];

                            $item->scoring = $scoring > 0 ? $scoring : 0; // если повторно также не прошел скоринг
                            $item->ball = $scoring_result['ball'];
                            $item->status = $scoring > 0 ? 1 : 0;
                            $item->response = json_encode($result,JSON_UNESCAPED_UNICODE); // новый ответ
                            $item->scoring_count++;
                            $item->save();

                            Log::info('scoring result');
                            Log::info($scoring_result);

                            if ($scoring > 0) {

                                // статус пройденного скоринга
                                User::changeStatus($item->user, User::STATUS_CARD_ADD);

                                // запись истории
                                KycHistory::insertHistory($item->user_id, User::KYC_STATUS_RESCORING, User::KYC_STATUS_RESCORING);

                                if (!$settings = BuyerSetting::where('user_id', $item->user_id)->first()) {
                                    //Buyer settings  - информация о лимит рассрочки
                                    $settings = new BuyerSetting();
                                    $settings->user_id = $item->user_id;
                                    $settings->period = Config::get('test.buyer_defaults.period');
                                    $settings->zcoin = 0;
                                    $settings->personal_account = 0;
                                    $settings->rating = 0;
                                }
                                $settings->limit = $scoring;
                                $settings->balance = $settings->limit;
                                if (!$settings->save()) {
                                    Log::info('not save settings');
                                }
                                (new UserPayService)->createClearingAccount($item->user_id);

                                $msg = "To'lov rejasini tuzish uchun o'z hisobingizga kiring yoki ro'yxatdan o'ting: resusnasiya.uz";
                                SmsHelper::sendSms($item->user->phone, $msg);

                                Log::info('save buyer-settings buyer_id: ' . $settings->user_id . ' bs_id: ' . $settings->id);
                                Log::info($settings);

                            }

                        }

                    } // isset $response['params']

                    $timer++;

                    if ($timer == 50) { // по 50 шт
                        sleep(1);
                        $timer = 0;
                    }

                }

            } // scoring
        } // isMethod('POST')

        // выборка для доп скоринга
        $query = CardScoringLog::has('user')
            ->select(DB::raw('card_scoring_logs.scoring, SUM(card_scoring_logs.scoring_count) as sum, COUNT(card_scoring_logs.scoring) as cnt'))
            ->leftJoin('users',function($query){
                $query->on('users.id', 'card_scoring_logs.user_id');
            })->where('users.status',1)
            ->where('card_scoring_logs.status',0)
            ->whereIn('scoring',[2,3,4])
            ->groupBy('card_scoring_logs.scoring');

        $data = $query->get();

        return view('panel.buyer.rescoring', compact('data') );

    }
    public function mkoReport(Request $request){
        $companies = GeneralCompany::where('is_mfo', 1)->get();
        return view('panel.reports.mko_for_ixbs', compact('companies'));
    }
    // 01.07.2021 - отчет по клиентам
    public function report(Request $request){

        $query = false;
        $date_filter = report_filter($query);

        $date_from = $date_filter['date_from']; // date('Y-m-d 00:00:00', time());
        $date_to = $date_filter['date_to']; // date('Y-m-d 23:59:59', time());

        switch($request->type){
            case 'custom':

                if(!isset($request->date)) return false;

                [$date_from,$date_to] = explode(',',$request->date);

                if (!empty($date_from)) {
                    $date_from = date('Y-m-d 00:00:00', strtotime($date_from ));
                }

                if (!empty($date_to)) {
                    $date_to = date('Y-m-d 23:59:59', strtotime($date_to ));
                }

                break;

            case 'last_week': // за неделю
                $date_from = date('Y-m-d H:i:s', strtotime('-7 days'));
                $date_to = date('Y-m-d 23:59:59',time());
                break;
            case 'last_month': // за месяц
                $m = date('m');
                $date_from = date('Y-'.$m.'-1 month 00:00:00', time() );
                $date_to = date('Y-m-d 23:59:59', time());
                break;
            case 'last_half_year': // за полгода
                $date_from = date('Y-m-d H:i:s', strtotime( '-6 months'));
                $date_to = date('Y-m-d 23:59:59',time());
                break;
            case 'last_day': // текущий день
            default:
                $date_from = date('Y-m-d 00:00:00', time() );
                $date_to = date('Y-m-d 23:59:59', time());
        }

        $buyers = DB::select(
            "SELECT
                count(csl.status) AS card,
                count(ks.katm_status) AS katm,
                count(rc.status) AS royxat,
                count(bp.pinfl_status) AS pinfl,
                if(u.verify_message='Отказ по возрасту',COUNT(u.id),0) AS age,
                if(u.verify_message='Держатель карты не соответствует',count(u.verify_message),0) AS incorrect
                FROM `users` AS u
                LEFT JOIN `card_scoring_logs` as csl on csl.user_id=u.id AND csl.status=0
                LEFT JOIN `katm_scorings` as ks on ks.user_id=u.id AND ks.`katm_status`!=1
                LEFT JOIN `royxat_credits` as rc on rc.user_id=u.id AND rc.status=0
                LEFT JOIN `buyer_personals` as bp on bp.user_id=u.id AND bp.`pinfl_status`=0
                WHERE u.created_at BETWEEN '{$date_from}' AND '{$date_to}'
                AND u.status=8
                group by u.status ,u.verify_message,csl.status, ks.katm_status, rc.status, bp.pinfl_status;");

        // по лимитам
        $limits = DB::select("SELECT
                bs.limit,
                COUNT(u.id) AS cnt
                FROM `users` AS u
                left JOIN `buyer_settings` as bs on bs.user_id=u.id
                WHERE u.status=4 AND u.created_at BETWEEN '{$date_from}' AND '{$date_to}'
                GROUP BY bs.limit;");

        /*        Log::channel('report')->info("SELECT
                        bs.limit,
                        COUNT(u.id) AS cnt
                        FROM `users` AS u
                        left JOIN `buyer_settings` as bs on bs.user_id=u.id
                        WHERE u.status=4 AND u.created_at BETWEEN '{$date_from}' AND '{$date_to}'
                        GROUP BY bs.limit;"); */

        // $date = date('Y-m-d H:i:s',time()-2592000 ); // за месяц - 30 дней
        // $date = date('Y-m-d H:i:s',time() ); // за весь период

		$paysystem_report = [];

        // платежные
        $pay_system = DB::select("SELECT count(id) as cnt,sum(amount) as sum,payment_system
				FROM `payments`
				WHERE type in('user','refund') and payment_system in ('APELSIN','OCLICK','PAYME','UPAY','UZCARD','HUMO','PNFL','PAYNET','MIB','BANK','autopay')
				and created_at BETWEEN '{$date_from}' and '{$date_to}'
				group by payment_system
				order by payment_system;");

		$paysystem_sum=0;
		if($pay_system){
            foreach($pay_system as $item){
                if($item->payment_system == 'autopay' ) $item->payment_system = 'AUTOPAY';

				if($item->sum>0){
					$paysystem_sum+=$item->sum;
					$paysystem_report[] = '<tr class="dropdown_paysystem hide"><td>' . $item->payment_system . '</td><td align="right">' . number_format($item->sum,2,'.',' ') . '</td></tr>';
				}
            }
        }
		$paysystem_report = implode('',$paysystem_report);

        // ожидаемая сумма - просроченная
        $total_sql = DB::select("SELECT SUM(cps.total) as total, DATE_FORMAT(cps.payment_date,'%Y.%m') as cdate
				FROM `contracts` AS c
				INNER JOIN contract_payments_schedule as cps ON cps.contract_id=c.id
				WHERE c.status in (1,3,4,9) AND cps.`payment_date` <= '{$date_to}'
				GROUP BY cdate;");


        //dd($total_sql);

        // ожидаемая сумма - просроченная
        $debt_sql = DB::select("SELECT cps.status, SUM(cps.balance) as debt, SUM(cps.total) as total, DATE_FORMAT(cps.payment_date,'%Y.%m') as cdate
				FROM `contracts` AS c
				INNER JOIN contract_payments_schedule as cps ON cps.contract_id=c.id
				WHERE c.status in (1,3,4,9) AND cps.status=0 AND cps.`payment_date` <= '{$date_to}'
				GROUP BY cps.status,cdate;");

        /*
         отношение суммы просрочки к ожидаемой сумме к оплате по графику
         cps.status - 0 не оплачены 1 оплачены с учетом всех 0+1
        */


       //dd($total_sql);
        $delays_report = [];
        $debts = 0;
        if($debt_sql){
            //$total = $total_sql[0]->total ?? 1;
            $n=0;
            foreach($debt_sql as $debt){
                $debts += $debt->debt;
            }

            $debtDateBegin = Carbon::now()->format('Y.m');
            $debtDateEnd = Carbon::now()->format('Y.m');
            $totalDateBegin = Carbon::now()->format('Y.m');
            $totalDateEnd = Carbon::now()->format('Y.m');

            if (count($debt_sql) > 0) {
                $debtDateBegin = $debt_sql[0]->cdate;
                $debtDateEnd = $debt_sql[count($debt_sql) - 1]->cdate;
            }

            if (count($total_sql) > 0) {
                $totalDateBegin = $total_sql[0]->cdate;
                $totalDateEnd = $total_sql[count($total_sql) - 1]->cdate;
            }

            $dateCurrent = Carbon::createFromFormat('Y.m', $debtDateBegin) < Carbon::createFromFormat('Y.m', $totalDateBegin) ? Carbon::createFromFormat('Y.m', $debtDateBegin) : Carbon::createFromFormat('Y.m', $totalDateBegin);
            $dateEnd = Carbon::createFromFormat('Y.m', $debtDateEnd) > Carbon::createFromFormat('Y.m', $totalDateEnd) ? Carbon::createFromFormat('Y.m', $debtDateEnd) : Carbon::createFromFormat('Y.m', $totalDateEnd);
            $dateEnd->addMonth();

            $n = 1;

            while ($dateCurrent->format('Y.m') != $dateEnd->format('Y.m')) {

                $date = $dateCurrent->format('Y.m');
                $debt = 0;
                $total = 0;

                foreach ($debt_sql as $debtItem) {
                    if ($debtItem->cdate == $dateCurrent->format('Y.m')) {
                        $debt = $debtItem->debt;
                        break;
                    }
                }

                foreach ($total_sql as $totalItem)
                {
                    if ($dateCurrent->format('Y.m') == $totalItem->cdate) {
                        $total = $totalItem->total;
                        break;
                    }
                }

                $percent = number_format($total == 0 ? 0 : (($debt / $total) * 100),2,'.',' ');

                $delays_report[] = '<tr class="dropdown_delays hide">' .
                    '<td>'.$n.'</td>' .
                    '<td>' . $date . '</td>' .
                    '<td align="right">' . number_format($debt,2,'.',' ') . '</td>' .
                    '<td align="right">' . $percent . '</td>' .
                    '<td align="right">' . number_format($total,2,'.',' ') . '</td>' .
                    '</tr>';
                $n ++;

                $dateCurrent->addMonth();
            }







//            foreach($debt_sql as $i=>$debt){
//                if(!isset($total_sql[$i])) continue;
//                $n++;
//                $percent = number_format(($debt->debt / $total_sql[$i]->total) * 100,2,'.',' ');
//                $delays_report[] = '<tr class="dropdown_delays hide"><td>'.$n.'</td><td>' . $debt->cdate . '</td><td align="right">'
//                    . number_format($debt->debt,2,'.',' ') . '</td><td align="right">'
//                    . $percent . '</td><td align="right">'
//                    . number_format($total_sql[$i]->total,2,'.',' ') . '</td></tr>';
//
//            }
            /*foreach($total_sql as $total_s){
                if(!in_array($total_s->cdate, $debt_sql)){
                    dd($total_s->cdate);
                    array_unshift($delays_report,

                    );
                }
            }*/

        }


        $delays_report = implode('',$delays_report);

        // ожидаемая оплата всего - total
        $debt_sql = DB::select("SELECT c.status, SUM(cps.total) as debt
				from `contracts` AS c
				inner JOIN contract_payments_schedule as cps ON cps.contract_id=c.id
				WHERE c.status in (1,3,4) AND cps.`payment_date` BETWEEN '{$date_from}' AND '{$date_to}'
				GROUP BY c.status;");

        $wait = 0 ; // ожидаемая сумма списания
        if($debt_sql){
            foreach($debt_sql as $debt){
                $wait += $debt->debt;
            }
        }

        $_payments = [
            'wait'=>$wait,
            'card' => 0,
            'card_pnfl' => 0,
            'deposit' => 0,
			'paysystem_sum'=>$paysystem_sum,
        ];

        if( $payments = Payment::has('schedule')->select(DB::raw('SUM(amount) as sum,payment_system'))->whereIn('type',['auto','refund'])->whereIn('payment_system',['HUMO','UZCARD','DEPOSIT','ACCOUNT','PNFL'])->groupBy('payment_system')->whereBetween('created_at',[$date_from,$date_to])->get() ){
            $card = 0;
            $card_pnfl = 0;
            $deposit = 0;
            $account = 0;
            //$other = 0;
            foreach ($payments as $payment) {

                if($payment->payment_system == 'DEPOSIT') {
                    $deposit += $payment->sum;
                }elseif($payment->payment_system == 'ACCOUNT') {
                    $account += $payment->sum;
                }elseif($payment->payment_system == 'UZCARD' || $payment->payment_system == 'HUMO'){
                    $card += $payment->sum;
                }elseif($payment->payment_system == 'PNFL' ){
                    $card_pnfl += $payment->sum;
                }

            }
            $_payments['card'] = $card;
            $_payments['card_pnfl'] = $card_pnfl;
            $_payments['deposit'] = $deposit;
            $_payments['account'] = $account;

        }
        // группировка списаний по карте и лс
        /**
        select(DB::raw('SUM(amount) as sum,payment_system'))
         * ->whereIn('type',['auto','refund'])
         * ->whereIn('payment_system',['HUMO','UZCARD','DEPOSIT','ACCOUNT'])
         * ->groupBy('payment_system')
         * ->whereBetween('created_at',[$date_from,$date_to])
         */


        $card_report = [];
        $cardpnfl_report = [];
        $account_report = [];
        /// из план графика за выбранный период
        if($payments_delay = DB::select("SELECT  sum(p.amount) sum, DATE_FORMAT(cps.payment_date,'%Y.%m') as cdate,payment_system
                FROM `payments` p
                LEFT JOIN contract_payments_schedule cps ON cps.id=p.schedule_id
                LEFT JOIN contracts c ON c.id=cps.contract_id
                WHERE p.created_at BETWEEN '{$date_from}' AND '{$date_to}' AND c.status IN(1,3,4,9)
                AND type IN('auto','refund') AND payment_system IN ('HUMO','UZCARD','ACCOUNT','PNFL')
                GROUP BY payment_system, cdate ORDER BY cdate;")){   // AND cps.payment_date<=NOW()

            $payments_delays = [];

            foreach ($payments_delay as $payment) {

                if($payment->payment_system=='ACCOUNT'){
                    $system = 'account';
                }elseif($payment->payment_system=='PNFL'){
                    $system = 'card_pnfl';
                }else{
                    $system = 'card';
                }

                if(!isset($payments_delays[$system][$payment->cdate])) $payments_delays[$system][$payment->cdate] = 0;
                $payments_delays[$system][$payment->cdate] += $payment->sum;

            }

            //dd($payments_delays);

            $n=0;
            //$sum_price = 0;

            if(isset($payments_delays['card'])) {
                foreach ($payments_delays['card'] as $date => $price) {
                    $n++;
                    $card_report[] = '<tr class="dropdown_paycard hide"><td>' . $n . '</td><td>' . $date . '</td><td align="right">' . number_format($price, 2, '.', ' ') . '</td></tr>';
                    //$sum_price += $price;
                }
                //$card_report[] = '<tr class="dropdown_paycard hide bold"><td colspan="2">ИТОГО:</td><td align="right">' . number_format($sum_price, 2, '.', ' ') . '</td></tr>';
            }
            $n=0;
            if(isset($payments_delays['card_pnfl'])) {
                foreach ($payments_delays['card_pnfl'] as $date => $price) {
                    $n++;
                    $cardpnfl_report[] = '<tr class="dropdown_paycard_pnfl hide"><td>' . $n . '</td><td>' . $date . '</td><td align="right">' . number_format($price, 2, '.', ' ') . '</td></tr>';
                    //$sum_price += $price;
                }
                //$card_report[] = '<tr class="dropdown_paycard hide bold"><td colspan="2">ИТОГО:</td><td align="right">' . number_format($sum_price, 2, '.', ' ') . '</td></tr>';
            }
            $n=0;
            //$sum_price = 0;
            if(isset($payments_delays['account'])) {
                foreach ($payments_delays['account'] as $date => $price) {
                    $n++;
                    $account_report[] = '<tr class="dropdown_payaccount hide"><td>' . $n . '</td><td>' . $date . '</td><td align="right">' . number_format($price, 2, '.', ' ') . '</td></tr>';
                    //$sum_price += $price;
                }
                // $account_report[] = '<tr class="dropdown_payaccount hide bold"><td colspan="2">ИТОГО:</td><td align="right">' . number_format($sum_price, 2, '.', ' ') . '</td></tr>';
            }

        }

        $card_report = implode('',$card_report);
        $cardpnfl_report = implode('',$cardpnfl_report);
        $account_report = implode('',$account_report);

        $_buyers = [
            'count' => 0,
            'wait' => 0,
            'success' => 0,
            'card' => 0,
            'katm' => 0,
            'royxat' => 0,
            'pinfl' => 0,
            'age' => 0,
            'incorrect' => 0,
            'statuses' => ''
        ];

        $buyer_count = 0;
        $buyer_wait = 0;
        $buyer_success = 0;

        // $statuses = '';

        if($buyer = Buyer::select(DB::raw('Count(id) as cnt,status'))->whereNull('company_id')->whereBetween('created_at',[$date_from,$date_to])->groupBy('status')->get()) {

            /* $_statuses = [
                 '0' => 'Создано',
                 '1' => 'Новый',
                 '2' => 'Ожидают верификации',
                 '3' => 'Отказано',
                 '4' => 'Верифицированы',
                 '5' => 'Карта не добавлена',
                 '6' => 'Фото не прошло',
                 '7' => 'Фото не прошло',
                 '8' => 'Заблокированых',
                 '9' => 'Удалены',
                 '10' => 'Селфи не добавлено',
                 '11' => 'Прописка не добавлена',
                 '12' => 'Доверитель не добавлен',
             ]; */

            foreach ($buyer as $item) {
                //if ($item->status !=8 && $item->status !=4) { // == 2) { // ожидающие
                //$buyer_wait += $item->cnt;
                // $statuses .= '<tr class="dropdown_wait hide"><td class="padding_left">' . @$_statuses[$item->status] . '</td><td class="gray">'.$item->cnt .'</td></tr>';
                //}
                $buyer_count += $item->cnt; // все
            }

            //$_buyers['statuses'] = $statuses;
            $_buyers['count'] = $buyer_count;
            //$_buyers['wait'] = $buyer_wait;

        }
        $_buyers['count_blocked'] = 0;
        if(count($buyers)) {

            foreach ($buyers as $buyer){
                $_buyers['card'] += $buyer->card;
                $_buyers['katm'] += $buyer->katm;
                $_buyers['royxat'] += $buyer->royxat;
                $_buyers['pinfl'] += $buyer->pinfl;
                $_buyers['age'] += $buyer->age;
                $_buyers['incorrect'] += $buyer->incorrect;
                $_buyers['count_blocked'] += $buyer->card + $buyer->katm + $buyer->royxat + $buyer->pinfl + $buyer->age + $buyer->incorrect;
            }

        }

        $_limits = [
            '350000'=>0,
            '1000000'=>0,
            '3000000'=>0,
            '6000000'=>0,
            '9000000'=>0,
            '12000000'=>0,
            '15000000'=>0,
        ];
        $sum_limits = 0;
        if(count($limits)){
            foreach ($limits as $limit){
                $_limits[$limit->limit] = $limit->cnt;
                $sum_limits += $limit->limit * $limit->cnt;
                $buyer_success += $limit->cnt;
            }
        }

        $_buyers['success'] = $buyer_success;

        $_contracts = [
            'limit' => [
                1=>0,
                4 => $debts,
                5=>0
            ],
            'count' => 0,
            'sum' => 0,
            'sum_partner' => 0,
            'profit' => 0,
        ];

        $contracts_report = [];
        $contracts_report_table = [];

        $orderProducts = OrderProduct::with(['order.contract','order.company','order.buyer','order.buyer.user'])
            ->has('order.company')
            ->has('order.buyer')
            ->has('order.buyer.user')
            ->has('order.contract')
            ->leftJoin('contracts', function ($query) {
                $query->on('contracts.order_id', 'order_products.order_id');
            })
            ->whereIn('contracts.status',[1,3,4,5,9]) // также учесть просроченные
            ->whereBetween('contracts.created_at', [$date_from, $date_to]);

        $orderProducts = $orderProducts->get();
        $contracts_report_index = [];

        $contracts_count = [];

        if($orderProducts){

            $sum = 0;
            $sum_partner = 0;

            $prod_nds = NdsStopgagHelper::getActualNds();
            foreach ($orderProducts as $product) {

                $pa = $product->price * $product->amount;
                $prod_priceNds = $pa / ($prod_nds + 1) * $prod_nds; // 0.1725
                $sum_price = $pa;
                $status = $product->order->contract->status;

                if($status==3) $status = 4;

                if($status==5){ // отмененные

                    $_contracts['limit'][5] += $sum_price - $prod_priceNds;

                }else {
                    $isMFOCompany = boolval($product->order->contract->generalCompany->is_mfo);
                    $nds = @$product->order->partner->settings->nds > 0 ? NdsStopgagHelper::getActualNds() : 0;
                    $price = $product->price_discount * $product->amount;
                    $priceNds = $nds > 0 ? $price / NdsStopgagHelper::getActualNdsPlusOne() * $nds : 0;

                    $priceNoNdsSum = $price - $priceNds;

                    if ($isMFOCompany){
                        $final_price = $sum_price;
                    } else {
                        $final_price = $sum_price - $prod_priceNds;
                    }

                    $sum += $final_price;
                    $sum_partner += $priceNoNdsSum;

                    $company = $product->order->company->name;

                    if(!isset($contracts_report[$company]['price'])) $contracts_report[$company]['price'] = 0;
                    $contracts_report[$company]['price'] += $final_price;

                }

                if( in_array($status,[1,4,9]) ) $contracts_count[$product->order->contract->id] = 1; //$_contracts['count']++;

            }

            foreach ($contracts_report as $company=>&$item){
                $contracts_report_index[] = [
                    'company' => $company,
                    'price' => $item['price']
                ];
            }

            $price = array_column($contracts_report_index, 'price');
            array_multisort($price, SORT_DESC, $contracts_report_index);

            $_contracts['count'] = count($contracts_count); // всего договоров

            $_contracts['sum'] = $sum;
            $_contracts['sum_partner'] = $sum_partner;
            $_contracts['profit'] = $sum - $sum_partner;
            $_contracts['mean'] = $_contracts['count'] >0 ? $sum / $_contracts['count'] : 0;
            $_contracts['limit'][1] = $sum_limits;
            $n=0;
            foreach ($contracts_report_index as $item){
                $n++;
                $percent = number_format(($item['price'] / $sum)*100,2,'.',' ');
                $contracts_report_table[] = '<tr><td>'.$n.'</td><td>'.$item['company'] .'</td><td align="right">'.number_format($item['price'],2,'.',' ') .'</td><td align="right">'.$percent .'</td></tr>';
            }

        }
        $contracts_report_table = implode('',$contracts_report_table);
        $total_amount = AccountCBU::where('mask', 10513)->sum('balance');
        $data = [
            'date_from'=>$date_from,
            'date_to'=>$date_to,
            'limits' => $_limits,
            'contracts' => $_contracts,
            'users' => $_buyers,
            'payments' => $_payments
        ];

        return view('panel.buyer.report', compact('data','request','contracts_report_table','card_report','cardpnfl_report','account_report','delays_report','paysystem_report', 'total_amount') );

    }


    public function payments(Request $request){

        //abort(404);
        $query = false;
        $date_filter = report_filter($query);

        $date_from = $date_filter['date_from']; // date('Y-m-d 00:00:00', time());
        $date_to = $date_filter['date_to']; // date('Y-m-d 23:59:59', time());

        $paysystem_report = [];


        // 'UZCARD','HUMO','PINFL' - списания
        // 'APELSIN','OCLICK','PAYME','UPAY' - пополнение

        // платежные
        $pay_system = DB::select("SELECT count(id) as cnt,sum(amount) as sum,payment_system
            FROM `payments`
            WHERE type in('user','refund') and payment_system in ('APELSIN','OCLICK','PAYME','UPAY','UZCARD','HUMO','PINFL','PAYNET')
            and created_at BETWEEN '{$date_from}' and '{$date_to}'
            group by payment_system
            order by payment_system;");

        $paysystem_sum=0;
        if($pay_system){
            foreach($pay_system as $item){

                if($item->sum>0){
                    $paysystem_sum+=$item->sum;
                    $paysystem_report[] = '<tr class="dropdown_paysystem hide"><td>' . $item->payment_system . '</td><td align="right">' . number_format($item->sum,2,'.',' ') . '</td></tr>';
                }

            }


        }
        $paysystem_report = implode('',$paysystem_report);

        $_payments = [
            //'wait'=>$wait,
            'card' => 0,
            'deposit' => 0,
            'paysystem_sum'=>$paysystem_sum,
        ];

        if( $payments = Payment::has('schedule')->select(DB::raw('SUM(amount) as sum,payment_system'))->whereIn('type',['auto','refund'])->whereIn('payment_system',['HUMO','UZCARD','DEPOSIT','ACCOUNT'])->groupBy('payment_system')->whereBetween('created_at',[$date_from,$date_to])->get() ){
            $card = 0;
            $deposit = 0;
            $account = 0;
            //$other = 0;
            foreach ($payments as $payment) {

                if($payment->payment_system == 'DEPOSIT') {
                    $deposit += $payment->sum;
                }elseif($payment->payment_system == 'ACCOUNT') {
                    $account += $payment->sum;
                }elseif($payment->payment_system == 'UZCARD' || $payment->payment_system == 'HUMO'){
                    $card += $payment->sum;
                }

            }
            $_payments['card'] = $card;
            $_payments['deposit'] = $deposit;
            $_payments['account'] = $account;

        }
        // группировка списаний по карте и лс
        /**
        select(DB::raw('SUM(amount) as sum,payment_system'))
         * ->whereIn('type',['auto','refund'])
         * ->whereIn('payment_system',['HUMO','UZCARD','DEPOSIT','ACCOUNT'])
         * ->groupBy('payment_system')
         * ->whereBetween('created_at',[$date_from,$date_to])
         */


        $card_report = [];
        $account_report = [];
        /// из план графика за выбранный период
        if($payments_delay = DB::select("SELECT  sum(p.amount) sum, DATE_FORMAT(cps.payment_date,'%Y.%m') as cdate,payment_system
                FROM `payments` p
                LEFT JOIN contract_payments_schedule cps ON cps.id=p.schedule_id
                LEFT JOIN contracts c ON c.id=cps.contract_id
                WHERE p.created_at BETWEEN '{$date_from}' AND '{$date_to}' AND c.status IN(1,3,4,9)
                AND type IN('auto','refund') AND payment_system IN ('HUMO','UZCARD','ACCOUNT')
                GROUP BY payment_system, cdate ORDER BY cdate;")){   // AND cps.payment_date<=NOW()

            $payments_delays = [];

            foreach ($payments_delay as $payment) {

                if($payment->payment_system=='ACCOUNT'){
                    $system = 'account';
                }else{
                    $system = 'card';
                }

                if(!isset($payments_delays[$system][$payment->cdate])) $payments_delays[$system][$payment->cdate] = 0;
                $payments_delays[$system][$payment->cdate] += $payment->sum;

            }

            //dd($payments_delays);

            $n=0;
            $sum_price = 0;

            if(isset($payments_delays['card'])) {
                foreach ($payments_delays['card'] as $date => $price) {
                    $n++;
                    $card_report[] = '<tr class="dropdown_paycard hide"><td>' . $n . '</td><td>' . $date . '</td><td align="right">' . number_format($price, 2, '.', ' ') . '</td></tr>';
                    $sum_price += $price;
                }
                //$card_report[] = '<tr class="dropdown_paycard hide bold"><td colspan="2">ИТОГО:</td><td align="right">' . number_format($sum_price, 2, '.', ' ') . '</td></tr>';
            }
            $n=0;
            $sum_price = 0;
            if( isset( $payments_delays['account'] ) ) {
                foreach ( $payments_delays['account'] as $date => $price ) {
                    $n++;
                    $account_report[] = '<tr class="dropdown_payaccount hide"><td>' . $n . '</td><td>' . $date . '</td><td align="right">' . number_format($price, 2, '.', ' ') . '</td></tr>';
                    $sum_price += $price;
                }
                // $account_report[] = '<tr class="dropdown_payaccount hide bold"><td colspan="2">ИТОГО:</td><td align="right">' . number_format($sum_price, 2, '.', ' ') . '</td></tr>';
            }

        }

        $card_report = implode('',$card_report);
        $account_report = implode('',$account_report);

        $data = [
            'date_from'=>$date_from,
            'date_to'=>$date_to,
        ];

        $payment_header = 0;
        $payment_data = 0;
        $payment_sum = 0;

        return view('panel.buyer.payments', compact( 'data','payment_header','payment_data','payment_sum') );


    }


}
