<?php

namespace App\Http\Controllers\Core;

use App\Facades\BuyerDebtor;
use App\Helpers\FileHelper;
use App\Helpers\SmsHelper;
use App\Models\Act;
use App\Models\Buyer;
use App\Models\BuyerPersonal;
use App\Models\CollectionDocument;
use App\Models\Contract;
use App\Models\Contract as Model;
use App\Models\ContractPaymentsSchedule;
use App\Models\ContractRecovery;
use App\Models\File;
use App\Models\KycHistory;
use App\Models\MyID;
use App\Models\MyIDJob;
use App\Models\Record;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RecoveryController extends CoreController {

    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Eager load
        $this->loadWith = ['buyer','debts'];
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter($params = []){

        return parent::filter($params);

        /*if(isset($params['status'])) {

            if( (is_array($params['status']) && in_array(4,$params['status'])) || $params['status']==4 /*|| $params['status']==3* / ) {

                $date = date('Y-m-d 23:00:00',time() );

                $orders = DB::select("select c.id
				from `contracts` AS c
				inner JOIN contract_payments_schedule as cps ON cps.contract_id=c.id
				WHERE c.status IN (1,3,4) AND cps.status=0 AND cps.`payment_date` <= '{$date}'");

                $ordersID = [];

                foreach ($orders as $order){
                    $ordersID[] = $order->id;
                }

                $params['id'] = $ordersID ?? [];

                $params['status'] = [1,3,4];
            }else{

                $params['id__not'] = $ordersID ?? [];

            }

        }

        return parent::filter($params);*/

    }


    public function list( array $params = []) {
        $user = Auth::user();


        //Get data from REQUEST if api_token is set
        $request = request()->all();
        if ( isset( $request['api_token'] ))
            $params = $request;

        //dd($params);


        //Filter elements
        $filter = $this->filter($params);

        //Render items
        foreach ($filter['result'] as $index => $item){
            $item->permissions = $this->permissions($item, $user);

            if ($user->can('detail', $item)){
                //Debt calculation
                $item->totalDebt = 0;

                foreach ($item->debts as $debt) {
                    $item->totalDebt += $debt->balance;
                }

            }else {
                $filter['result']->forget($index);

            }

        }

        //Collect data
        $this->result['response']['total']  = $filter['total'];
        $this->result['status']             = 'success';

        //Format data
        if(isset($params['list_type']) && $params['list_type'] == 'data_tables') {
            //dd($filter['result']);
            $filter['result'] = $this->formatDataTables($filter['result']);

        }

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }

    public function recoveryStep(Request $request)
    {
        try{

            if ($contract = Contract::with('buyer', 'recoveries')->where('id', $request->contract_id)->first()) {

                $recovery_tab = $contract->recovery;

                $debts = number_format($contract->totalDebt, 2, '.', ',');
                $fio = $contract->buyer->fio;
                $date = $request->has('day') ? date('d-m-Y', strtotime('+' . $request->day . ' day' )) : '';
                switch ($contract->recovery) { // текущий статус recovery
                    case 0: // до 30 дней / обзвон юристами

                        if ($request->has('day') && (int) $request->day > 0) {
                            $comment = 'Количество дней отсрочки при первом обзвоне: ' . $request->day . ". " . $request->comment;
                            KycHistory::insertHistory($contract->user_id, User::RECOVER_CALL_WAIT, null, null, $comment);
                            $contract->recovery = 1; // не переходить на следующий этап

                            // cmc 1
                            $msg = "Hurmatli {$fio}. Sizga shartnoma asosida qarzdorligingizni {$debts} to'lash maqsadida, {$date} gacha muddat berildi.";


                        } else {
                            $comment = $request->comment;
                            KycHistory::insertHistory($contract->user_id, User::RECOVER_LETTER, null, null, $comment);
                            $contract->recovery = 2;

                            // cmc 2
                            $msg = "Hurmatli {$fio}. Siz tomoningizdan shartnoma asosida qarzdorligingizni {$debts} to'lamaganligingiz uchun yashash manzilingizga e'tiroz xati jo'natildi.";

                        }

                        // реестр контрактов на взыскание
                        $this->addRecovery($contract, ['comment' => $comment, 'day' => $request->day ?? '']);


                        break;
                    case 1: // ожидание обзвона, автопереход статуса на следующий этап по истечении срока отсрочки

                        // здесь условие, если клиент отсрочил переместить на следющий этап - в ожидание
                        // иначе перейти в recovery += 2 передача нотариусу
                        //KycHistory::insertHistory($contract->user_id,User::RECOVER_CALL);
                        //$contract->recovery = 2;

                        // cmc 2 - автоотправка смс при окончании срока ожидания
                        // $msg = "Hurmatli {$fio}. Siz tomoningizdan shartnoma asosida qarzdorligingizni {$debts} to'lamaganligingiz uchun yashash manzilingizga e'tiroz xati jo'natildi.";


                        break;
                    case 2: // письма к отправке

                        $comment = $request->comment;;
                        if ($request->has('day') && $request->day > 0) {
                            $comment = 'Количество дней отсрочки по письму ' . $request->day . '<br>' . $comment;
                            KycHistory::insertHistory($contract->user_id, User::RECOVER_LETTER_WAIT, null, null, $comment);
                            $contract->recovery = 3; // не передавать на следующий статус

                            // cmc 3
                            $msg = "Hurmatli {$fio}. Sizga shartnoma asosida qarzdorligingizni {$debts} to'lash maqsadida, {$date} gacha muddat berildi.";

                        } else {
                            KycHistory::insertHistory($contract->user_id, User::RECOVER_LETTER, null, null, $comment);
                            $contract->recovery = 4;

                            // cmc 4
                            $msg = "Hurmatli {$fio}. Siz tomoningizdan shartnoma asosida qarzdorligingizni {$debts} to'lamaganligingiz uchun
                            hujjatlaringiz yuristlarimiz tomonidan fuqarolik sudi (yoki notarius orqali) undirish choralari ko'riladi.";

                        }

                        $not_work = $request->has('not_work') ? 1 : 0;

                        // реестр контрактов на взыскание
                        $this->addRecovery($contract, ['comment' => $comment, 'day' => $request->day ?? '', 'not_work' => $not_work]);




                        break;
                    case 3: // ожидание по письму, автопереход статуса на следующий этап по истечении срока отсрочки

                        // KycHistory::insertHistory($contract->user_id, User::RECOVER_LETTER_WAIT);
                        // $contract->recovery = 4;

                        // cmc 4 - автоотправка смс при окончании срока ожидания
                        // $msg = "Hurmatli {$fio}. Siz tomoningizdan shartnoma asosida qarzdorligingizni {$debts} to'lamaganligingiz uchun
                        // hujjatlaringiz yuristlarimiz tomonidan fuqarolik sudi (yoki notarius orqali) undirish choralari ko'riladi.";


                        break;
                    case 4: // из передача нотариусу

                        $fn_invoice = $fn_letter = '';
                        if( $file = File::where('element_id',$contract->id)->where('type','invoice')->first() ) {
                            $fn_invoice = FileHelper::url($file->path);
                        }
                        if( $file = File::where('element_id',$contract->id)->where('type','execute')->first() ) {
                            $fn_letter = FileHelper::url($file->path);
                        }

                        $links = "<a href='{$fn_invoice}' download>Инвойс нотариуса</a><br><a href='{$fn_letter}' download>Исполнительная надпись</a>";

                        KycHistory::insertHistory($contract->user_id, User::RECOVER_MIB,null,null,$links);
                        $contract->recovery = 5;

                        $comment = '';
                        // реестр контрактов на взыскание
                        $this->addRecovery($contract, ['comment' => $comment, 'day' => $request->day ?? '']);


                        // Task test-1158: save documents' path:
// =====================================================================================================================

                        if ( $request->hasFile("invoice") ) {
                            $file_path = FileHelper::saveLetterFile($request->file("invoice"), [
                                "model"         => File::MODEL_CONTRACTS_RECOVERY,
                                "element_id"    => $contract->id,
                                "type"          => File::TYPE_INVOICE,
                                "user_id"       => Auth::id(),
                            ]);

                            if ( $file_path ) {
                                // Create CollectionDocument record in DB table:
                                $collection_document = new CollectionDocument();
                                $collection_document->contract_id = $contract->id;
                                $collection_document->user_id     = Auth::id();
                                $collection_document->type        = File::TYPE_INVOICE;
                                $collection_document->file_link   = FileHelper::url($file_path);
                                $collection_document->save();
                            }
                        }

                        if ( $request->hasFile("execute") ) {

                            $file_path = FileHelper::saveLetterFile($request->file("execute"), [
                                "model"         => File::MODEL_CONTRACTS_RECOVERY,
                                "element_id"    => $contract->id,
                                "type"          => File::TYPE_INVOICE_EXECUTE,
                                "user_id"       => Auth::id(),
                            ]);

                            if ( $file_path ) {
                                // Create CollectionDocument record in DB table:
                                $collection_document = new CollectionDocument();
                                $collection_document->contract_id = $contract->id;
                                $collection_document->user_id     = Auth::id();
                                $collection_document->type        = File::TYPE_INVOICE_EXECUTE;
                                $collection_document->file_link   = FileHelper::url($file_path);
                                $collection_document->save();
                            }
                        }
// =====================================================================================================================

                        break;

                    case 5:  // передано в МИБ
                        // EXTRA CODE START
                        // Добавляет данные с формы из вкладки 'МИБ' в таблицу 'acts'
                        $act = new Act();
                        $act->contract_id     = $request->contract_id;
                        $act->user_id         = $contract->user_id;
                        $act->initiation_date = $request->date;
                        $act->number          = $request->delo;
                        $act->observer_name   = $request->name;
                        $act->observer_phone  = $request->phone;
                        $act->save();
                        // EXTRA CODE END

                        KycHistory::insertHistory($contract->user_id, User::RECOVER_CONTROL);
                        $contract->recovery = 6;

                        break;

                    case 6: // контроль
                        if(isset($contract->collcost)){
                            if($contract->collcost->status == 0){
                                $this->result['status'] = 'not_allowed';
                                return $this->result();
                            }
                        }
                        KycHistory::insertHistory($contract->user_id, User::RECOVER_COMPLETE);
                        $contract->recovery = 7;
                        $contract->status = 9;
                        break;

                    // case 7: // договор завершен
                    default :

                }

                $production = false;

                try {
                    $contract->save();
                } catch (\Exception $e) {
                    dd($e);
                }

                $this->result['status'] = 'success';

                $this->result['recovery-tab'] = $recovery_tab; //$contract->recovery;


                return $this->result();

            }



        }catch(\Exception $e){
            Log::channel('errors')->info($e);
            dd($e);
        }

        $this->result['error'] = 'contract not found';
        $this->result['status'] = 'error';

        return $this->result();

    }

    // установка
    private function addRecovery(&$contract,$data){

        $recovery_history = new ContractRecovery();
        $recovery_history->contract_id = $contract->id;
        $recovery_history->user_id = $contract->user_id;
        $recovery_history->status = 1;
        $recovery_history->comment = isset($data['comment']) ? $data['comment'] : '';
        $recovery_history->day = isset($data['day']) ? (int)$data['day'] : 0;
        $recovery_history->not_work = isset($data['not_work']) ? 1 :  0;

        $recovery_history->recovery = $contract->recovery;
        try {
            $recovery_history->save();
        }catch (\Exception $e){
            dd($e);
        }

    }

    public function getDebts(Request $request){
        $query = BuyerDebtor::overdueContracts(30, $request->recovery, isset($request->action) ? $request->action : null);
        $query->selectRaw('SUM(schedules.balance) AS balance');
        $query->leftJoin(DB::raw('contract_payments_schedule AS schedules'), 'schedules.contract_id', '=', 'contracts.id');
        $query->where('payment_date', '<', Carbon::now()->format('Y-m-d H:i:s'));
        $query->whereRaw('schedules.status = 0');
        $query->orderBy('schedules.payment_date');
        $query->groupByRaw('NULL');
        $row = $query->first();
        $debit = 0;
        if ($row) {
            $debit = $row['balance'];
        }
        return [
            'status' => 'success',
            'debts' => $debit,
        ];
    }

    public function buyerComment(Request $request){

        if($record = Record::where('user_id',$request->user_id)->where('contract_id',$request->contract_id)->orderBy('created_at','DESC')->first()) {

            return ['status' => 'success', 'comment' => $record->text];
        }
        return ['status' => 'success','comment'=>''];
    }

    public function myIdStatus(Request $request){
        if($myId = MyIDJob::where([
                    ['user_id',$request->user_id],
                    ['result_code','1'],
                ])->orderBy('created_at','DESC')->first()) {

            return ['status' => 'success', 'my_id' => $myId->id];

        }else{
            return ['status' => 'error', 'my_id' => null];
        }
    }

}
