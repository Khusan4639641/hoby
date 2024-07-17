<?php


namespace App\Http\Controllers\Core;

use App\Helpers\EncryptHelper;
use App\Models\Buyer;
use App\Models\Buyer as Model;
use App\Models\Card;
use App\Models\User;
use Carbon\Carbon;
use Doctrine\DBAL\Schema\AbstractAsset;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BuyerDelayController extends CoreController
{

    private $config = [
        'status' => null
    ];


    public function __construct()
    {
        parent::__construct();
        $this->config['status'] = Config::get('test.user_status');
        $this->model = app(Model::class);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function filter($params = [])
    {

        $paramsSearch = [];

        //Searching query
        if (isset($params['search']) || isset($params['searchID'])) {

            if (isset($params['search']) && $params['search'] != '') {
                if (is_numeric($params['search'])) {
                    $paramsSearch['phone__like'] = $params['search'];
                } else {
                    $paramsSearch['or__surname__like'] = $params['search'];
                    $paramsSearch['or__name__like'] = $params['search'];
                    $paramsSearch['or__patronymic__like'] = $params['search'];
                }
                unset($params['search']);
            }

            if (isset($params['searchID']) && $params['searchID'] != '') {
                $paramsSearch['id'] = $params['searchID'];
                unset($params['searchID']);

            }

            $filterSearch = parent::filter($paramsSearch);
            $filterSearch = $filterSearch['result']->pluck('id')->toArray();

            $params['id'] = $filterSearch;
        }


        return parent::filter($params);
    }


    /**
     * @param array $params
     *
     * @return array|bool|false|string
     */
    public function list(array $params = [])
    {

        $user = Auth::user();
        $request = request()->all();

        if (isset($request['api_token'])) {
            unset($request['api_token']);
            $params = $request;
        }

        $params['debts'] = 1;

        //Filter elements
        $filter = $this->filter($params);

        foreach ($filter['result'] as $index => $item) {
            if ($user->can('detail', $item)) {

                $totalDebt = 0;
                foreach ($item->contracts as $contract) {
                    foreach($contract->schedule as $schedule){
                        $payment_date = strtotime($schedule->payment_date);
                        $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));
                        if($schedule->status == 0 && $payment_date <= $now){
                            $totalDebt += $schedule->balance;
                        }
                    }
                }

                $item->totalDebt = $totalDebt;

                $item->status_caption = __('user.status_' . $item->status);
            } else {
                $filter['result']->forget($index);
            }
        }

        $this->result['status'] = 'success';
        $this->result['response']['total'] = $filter['total'];


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
     * @param $id
     * @param array $with
     *
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    protected function single($id, $with = [])
    {
        $single = parent::single($id, array_merge($this->loadWith, []));

        if (!$single) {
            return redirect()->to('panel.login');
        }

        $single->status_list = Config::get('test.user_status');
        $single->status_caption = __('user.status_' . $single->status);

        $dt = new \DateTime();
        $single->scoring = [
            'date_start' => $dt->modify('-6 months')->format('d.m.Y'),
            'date_end' => date('d.m.Y'),
            'sum' => 1000000,
        ];

        if ($single->personals) {
            foreach ($single->personals->getAttributes() as $key => $value) {
                $single->personals[$key] = in_array($key, $this->encryptedFields) ? EncryptHelper::decryptData($value) : $value;
            }
        }

        $single->totalDebt = 0;
        foreach ($single->debts as $debt)
            $single->totalDebt += $debt->total;


        $katmRegion = KatmRegion::get();
        $regions = [];

        for ($k = 0; $k < sizeof($katmRegion); $k++) {
            $item = $katmRegion[$k];
            $regions[$item->region]['name'] = $item->region_name;
            $regions[$item->region]['id'] = $item->region;
            $regions[$item->region]['local_region'][] = ['id' => $item->local_region, 'name' => $item->local_region_name];

        }

        $single->katm_regions = json_encode($regions);

        return $single;
    }


    /**
     * @param $id
     *
     * @return array|bool|false|string
     */
    public function detail($id)
    {
        $buyer = $this->single($id, 'cards', 'guarants', 'cardsPnfl');
        $user = Auth::user();

        if ($buyer) {
            if ($user->can('detail', $buyer)) {

                $buyer->phonesEquals = true;

                if ($cards = $buyer->cards) {
                    foreach ($cards as $card) {
                        if (EncryptHelper::decryptData($card->phone) != $buyer->phone) {
                            $buyer->phonesEquals = false;
                            break;
                        }
                    }
                }
                // pnfl uzcard
                if ($cards_pnfl = $buyer->cardsPnfl) {

                }
                $this->result['status'] = 'success';
                $this->result['data'] = $buyer;
            } else {
                //Error: access denied
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 403;
                $this->message('danger', __('app.err_access_denied'));
            }
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        return $this->result();
    }

    /**
     * @Request $request
     *
     * получить баланс карты по ее айди
     *
     * @return array|bool|false|string
     */
    public function cardBalance(Request $request){

        if($request->card_id) {
            $card = Card::find($request->card_id);

            $user = Auth::user();

            $request->merge([
                'buyer_id' => $card->user_id
            ]);

            $cardController = new CardController();
            $result = $cardController->balance($request);

            $this->result['status'] = 'success';
            $this->result['data']['balance'] = $result['result']['balance'];
            $this->result['response']['code'] = 200;


        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.card_not_found'));

        }

        return $this->result();

    }






}
