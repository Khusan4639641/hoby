<?php


namespace App\Http\Controllers\Core;

use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;

use App\Helpers\SmsHelper;
use App\Helpers\UniversalHelper;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\CardPnfl;
use App\Models\CardPnfl as Model;
use App\Models\KycHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class CardPnflController extends CoreController{



    /**
     * Controller constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

    }



    /**
     * Списко доступных карт пользователя
     * @param array $params
     * @return mixed
     */
    public function list($params = [])
    {
        $user = Auth::user();
        $request = request()->all();
        if (isset($request['api_token']))
            $params = $request;
        unset($params['api_token']);

        $buyer = Buyer::where('id', $params['user_id'])->with('pnflContract')->first();

        // получить все карты клиента
        $rq = new Request();
        $rq->merge([
            'clientId' => $buyer->pnflContract->clientId ?? null,
        ]);

//        $info = UniversalPnflController::getInfo($rq);

         //Filter elements
        $filter = $this->filter($params);

//        foreach ($filter['result'] as $index => $item) {
//            // в реальном времени
//            $item->balance = $info['balance'][$item->card_id] ?? 2; // tiin
//            $item->status = $info['status'][$item->card_id] ?? 2; // доступность для списания
//            if($item->status == 2) $item->state = 2;
//            if(isset($info['card_phone'][$item->card_id])){
//                $item->card_phone = $item->card_phone == $info['card_phone'][$item->card_id] ? $item->card_phone : $info['card_phone'][$item->card_id];
//            }
//
//        }

        //Collect data
        $this->result['response']['total'] = $filter['total'];
        $this->result['status'] = 'success';

        //Format data
        if (isset($params['list_type']) && $params['list_type'] == 'data_tables') {
            $filter['result'] = $this->formatDataTables($filter['result']);
        }
        //Collect data

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();

    }



    /**
     * @param Model $card
     *
     * @return array|bool|false|string
     */
    public function activate($id)
    {
        $user = Auth::user();
        if ($id) {
            $card = Model::find($id);
            $card->status = Card::CARD_ACTIVE;
            $card->save();

            $str = EncryptHelper::decryptData($card->card_number);
            $card_number = substr($str, 0, 4) . '****' . substr($str, -4);

            // добавляем в историю запись
            KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_ACTIVE, null ,$card_number);

            $this->result['status'] = 'success';
            $this->message('success', __('panel/employee.txt_activated'));
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        return $this->result();
    }


    /**
     * @param Model $card
     *
     * @return array|bool|false|string
     */
    public function deactivate($id)
    {
        $user = Auth::user();
        if ($id) {
            $card = Model::find($id);
            $card->status = Card::CARD_INACTIVE;
            $card->save();

            $str = EncryptHelper::decryptData($card->card_number);
            $card_number = substr($str, 0, 4) . '****' . substr($str, -4);

            // добавляем в историю запись
            KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_INACTIVE, null, $card_number);

            $this->result['status'] = 'success';
            $this->message('success', __('panel/news.txt_deactivated'));

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        return $this->result();
    }

    /**
     * Delete card
     *
     * @param int $id
     * @return array|bool|false|string
     */
    public function delete(int $id)
    {

        $user = Auth::user();

        if ($id) {
            $card = Model::find($id);
            if ($card) {
                $card->status = Card::CARD_DELETED;
                $card->save();

                $str = EncryptHelper::decryptData($card->card_number);
                $card_number = substr($str, 0, 4) . '****' . substr($str, -4);

                // добавляем в историю запись
                KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_DELETED, null, $card_number);

                $this->result['status'] = 'success';
                $this->message('success', __('panel/employee.txt_deleted'));
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        return $this->result();
    }






}
