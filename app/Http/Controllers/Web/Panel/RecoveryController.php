<?php


namespace App\Http\Controllers\Web\Panel;

use App\Exports\DelayExExport;
use App\Helpers\FileHelper;
use App\Helpers\SmsHelper;
use App\Http\Controllers\Core\RecoveryController as Controller;
use App\Models\Contract;
use App\Models\ContractRecovery;
use App\Models\CronUsersDelays;
use App\Models\File;
use App\Models\KycHistory;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;


class RecoveryController extends Controller {


     /**
     * @param Collection | array $items
     *
     * @return array
     */
    protected function formatDataTables( $items ) {

        $i    = 0;
        $data = [];


        foreach ( $items as $item ) {

            if( !$item->buyer ) continue;

            //Расчеты
            $extinguished = $item->total - $item->balance;
            $debtsClass = $item->totalDebt > 0 ? 'red': '';

            $insurance = isset($item->insurance) && $item->insurance->status > 0 ? '2' : '1';
            $lawsuit = (isset($item->lawsuit) && $item->lawsuit->status > 0) ? '2' : '1';

            $companyName = isset( $item->company ) ? $item->company->name : '';
            $contractLink = $companyName !== '' ? '<a href="' . localeRoute('panel.contracts.show', $item) . '">' . $item->id . '</a>' : $item->id;

            if( (int)$item->buyer->gender == 2 ){
                $gender = 'Ж';
            }elseif ( (int)$item->buyer->gender == 1 ){
                $gender = 'М';
            }else{
                $gender = '-';
            }

            $data[ $i ][] = '<div class="created_at">' . $item->created_at . '</div>';
            $data[ $i ][] = '<div class="contract_id">'.$contractLink.'</div>';
            $data[ $i ][] = '<div class="partner">' . $companyName . '</div>';
            $data[ $i ][] = '<div class="client"><a target="_blank" href="'.localeRoute('panel.buyers.show', $item->buyer).'">' . $item->buyer->fio . '</a></div>';
            $data[ $i ][] = '<div class="gender">' . $gender . '</div>';
            $data[ $i ][] = "<div class='birth_date'>{$item->buyer->birth_date}</div>";
            $data[ $i ][] = '<div class="phone">' . $item->buyer->phone . '</div>';
            $data[ $i ][] = '<div class="total">' . number_format($item->total,2,'.','&nbsp;') . '/<span class="period">'. $item->period.'</span></div>';

            $data[ $i ][] = '<div class="extinguished">' . number_format($extinguished,2,'.','&nbsp;') . '</div>';
            $data[ $i ][] = '<div class="debts '.$debtsClass.'">' . number_format($item->totalDebt,2,'.','&nbsp;') . '</div>';
            $data[ $i ][] = '<div class="day '. $debtsClass .'">' . $item->delayDays . ' (<span class="small">' . $item->payment_date . '</span>)</div>';
            $data[ $i ][] = '<div class="status-' . $item->status . '">' . __('contract.status_' . $item->status) . '</div>';
//            $data[ $i ][] = '<button class="change-status" onclick="change('. $item->id .',' . $item->recovery .')">' . __('panel/contract.tab_recover_' . $item->recovery) . '</button>';
//            $data[ $i ][] = '<div class="insurance"><img src="/images/icons/icon_insurance' . $insurance . '.svg" alt=""></div>';
//            $data[ $i ][] = '<div class="lawsuit"><img src="/images/icons/icon_lawsuit' . $lawsuit . '.svg" alt=""></div>';

            $i ++;
        }

        return parent::formatDataTables( $data );
    }


    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index() {


        $user = Auth::user();
        if ( $user->can( 'modify', new Contract()) ) {

            $params = [
                'total_only' => 'yes',
                'recovery' => 0,
                'status' => 4,
                'action' => 0
            ];
            $counter['all_30'] =  $this->filter($params)['total'];

            $params = [
                'total_only' => 'yes',
                'recovery' => 0,
                'status' => 4,
                'action' => 3
            ];
            $counter['all_45'] =  $this->filter($params)['total'];

            //Active orders
            $params = [
                'recovery' => 0,
                'total_only' => 'yes',
                'status' => 4,
                'action' => 1
            ];
            $counter['all_60'] =  $this->filter($params)['total'];

            $debts = CronUsersDelays::select(DB::raw('SUM(balance) as balance'))->first();
            $debts = number_format($debts->balance,2,'.',' ');

            $recovery = 0;
            $status = 4;
            $action = 0; // 0 - 0-30   1 - 45-59  2 - 60+ 3 - 31-60

            return view( 'panel.recovery.index', compact( 'user', 'counter','debts','recovery','action','status' ) );

        } else {

            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        }
    }



    public function contractsRecovery(){

        $user = Auth::user();
        if ( $user->can( 'modify', new Contract()) ) {

            $params = [
                'total_only' => 'yes',
                'recovery' => 0,
                'action' => 0,
            ];
            $counter['all_30'] = $this->filter($params)['total']; // < 30

            $params = [
                'total_only' => 'yes',
                'recovery' => 0,
                'action' => 1,
            ];
            $counter['call'] = $this->filter($params)['total']; // > 30

            //Active orders
            $params = [
                'recovery' => 1,
                'total_only' => 'yes',
                'action' => -1,
            ];
            $counter['call_wait'] = $this->filter($params)['total'];

            //Credit orders
            $params = [
                'recovery' => 2,
                'total_only' => 'yes',
                'action' => -1,
            ];
            $counter['letter'] = $this->filter($params)['total'];

            //Credit orders
            $params = [
                'recovery' => 3,
                'total_only' => 'yes',
                'action' => -1,
            ];
            $counter['letter_wait'] = $this->filter($params)['total'];

            $params = [
                'recovery' => 4,
                'total_only' => 'yes',
                'action' => -1,
            ];
            $counter['notarius'] = $this->filter($params)['total'];

            $params = [
                'recovery' => 5,
                'total_only' => 'yes',
                'action' => -1,
            ];
            $counter['mib'] = $this->filter($params)['total'];

            $params = [
                'recovery' => 6,
                'total_only' => 'yes',
                'action' => -1,
            ];
            $counter['control'] = $this->filter($params)['total'];

            $params = [
                'recovery' => [1,2,3,4,5,6,7],
                'total_only' => 'yes',
                'action' => -1,
            ];
            $counter['complete'] = $this->filter($params)['total'];

            $debts = CronUsersDelays::select(DB::raw('SUM(balance) as balance'))->first();
            $debts = number_format($debts->balance,2,'.',' ');

            //$counter[] = ''; //['cancel_act_verify'] = $this->filter($params)['total'];

            $recovery = 0;
            $status = 3;
            $action = 0; // 0 - 0-30   1 - 31-59  2 - 60+

            return view( 'panel.recovery.delays', compact( 'user', 'counter','debts','recovery','action','status' ) );

        } else {

            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        }

    }

    /* private function getTotal ($params){

        switch($params['action']){
            case 0: // 30
                $date_from = 0;
                $date_to = date('Y-m-d 23:59:59', time() - 30*86400);
                break;
            case 1: // 60
                $date_from = date('Y-m-d 23:59:59', time() - 60*86400);
                $date_to = date('Y-m-d 23:59:59', time() - 30*86400);
                break;
            case 2: // 60+
                $date_from = date('Y-m-d 23:59:59', time() - 10000*86400);
                $date_to = date('Y-m-d 23:59:59', time() - 61*86400);
                break;
            default:
                $date_from = date('Y-m-d 23:59:59', time() - 10000*86400);
                $date_to = date('Y-m-d 23:59:59', time() - 61*86400);

        }

        $query = $this->model::query();
        $query->leftJoin('contract_payments_schedule as cps', function ($query) use ($date_from,$date_to) {
            $query->on('cps.contract_id', 'contracts.id')->where('cps.status', 0);
        })
            ->select(DB::raw('DISTINCT contracts.order_id, contracts.*'))
            ->where('cps.status', 0)
            ->whereBetween('cps.payment_date', [$date_from,$date_to])
            ->whereIn('contracts.status', [1, 3, 4]);
        return $query->count();

    } */

    public function show($id){

        $result = $this->detail($id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.contracts.index'))->with('message', $this->result['response']['message']);
        } else {
            $contract = $result['data'];
            $category = 0;
            if( $products = $contract->order->products ){
                foreach ($products as $product){
                    if($product->category_id==1){
                        $category = 1;
                        break;
                    }
                }
            }

            return view('panel.recovery.show', compact('contract','category'));
        }

    }

    /*public function contractsDelay(){

    }*/
    public function contractsReport(){


        //if(!Auth::user()->hasRole('admin') ) abort(404);

        $model9 = 'delaysEx';
        $model15 = 'ordersCancel';
        $model5 = 'contracts';
        $model22 = 'filesHistory';
        $access = 'recovery';
        return view( 'panel.recovery.reports', compact( 'model9', 'model5', 'model15', 'model22', 'access' ) );

    }
    public function export(Request $request,$model){
        if($model=='delaysEx') {
            return $this->excel->download(new DelayExExport, 'delaysEx.xlsx');
        }
        abort(404);
    }

    public function getDocument($element_id,$type){

        if($file = File::where('element_id',$element_id)->where('type',$type)->first()){

            $filepath = 'https://newres.test.uz/' . FileHelper::url($file->path); // FileHelper::url($file->path);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            //header('Content-Length: ' . filesize($filepath));
            flush();
            readfile($filepath);

        }

        exit;


    }

    public function checkDays(Request $request){

        if( $recoveries = ContractRecovery::with('contract','contract.buyer')
            ->from('contracts_recovery as cr')
            ->where('day','>',0)
            ->where('status',1)
            ->whereRaw('ADDDATE(cr.created_at,cr.day) < NOW()')
            ->get() ){


            Log::info('CONTRACT-RECOVERY: Проверка времени отсрочки.' );
            Log::info('CONTRACT-RECOVERY. Смена recovery статуса для:');

            //Log::info($recoveries);

            // dd($recoveries);

            foreach ( $recoveries as $recovery ){

                $fio = $recovery->contract->buyer->fio;
                $phone = $recovery->contract->buyer->phone;
                // $date = date('d-m-Y', strtotime('+' . $recovery->day . ' day' ));
                $debts = number_format($recovery->contract->totalDebt, 2, '.', ',');
                $msg = '';

                switch($recovery->recovery){
                    case 1:
                        $msg = "Hurmatli {$fio}. Siz tomoningizdan shartnoma asosida qarzdorligingizni {$debts}
                        to'lamaganligingiz uchun yashash manzilingizga e'tiroz xati jo'natildi.";
                        KycHistory::insertHistory($recovery->user_id, User::RECOVER_LETTER);
                        break;
                    case 3:
                        $msg = "Hurmatli {$fio}. Siz tomoningizdan shartnoma asosida qarzdorligingizni {$debts}
                        to'lamaganligingiz uchun hujjatlaringiz yuristlarimiz tomonidan fuqarolik sudi (yoki notarius orqali)
                        undirish choralari ko'riladi.";
                        KycHistory::insertHistory($recovery->user_id, User::RECOVER_NOTARIUS);
                        break;
                }

                $recovery->contract->recovery += 1;
                $recovery->contract->save();

                $recovery->status = 2;
                $recovery->save();

                Log::info('CONTRACT-RECOVERY. id: ' . $recovery->id . ' contract_id: ' . $recovery->contract_id );

                if($msg!='') SmsHelper::sendSms($phone,$msg);


            }

        }

    }

}
