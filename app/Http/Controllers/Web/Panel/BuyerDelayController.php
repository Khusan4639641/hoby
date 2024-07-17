<?php


namespace App\Http\Controllers\Web\Panel;

use App\Helpers\EncryptHelper;
use App\Helpers\UniversalHelper;
use App\Helpers\helpers;
use App\Http\Controllers\Core\BuyerDelayController as Controller;
use App\Http\Controllers\Core\UniversalPnflController;
use App\Models\Buyer;
use App\Models\Card;
use App\Models\CardPnfl;
use App\Models\CardPnflContract;
use App\Models\Contract;
use App\Models\KycHistory;
use App\Models\User;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BuyerDelayController extends Controller
{

    /**
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->can('modify', new Buyer())) {

            return view('panel.buyer.buyer_delay');
        } else {

            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        }

    }

    /**
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function cards()
    {
        $user = Auth::user();
        //dd(1111);
        $date = date('Y-m-d 23:59:00', strtotime('-10 day'));
        $query = Buyer::query(); //user
        $query->whereHas('contracts', function ($query) use ($date) {
            $query->leftJoin('contract_payments_schedule as cps', function ($query) {
                $query->on('cps.contract_id', 'contracts.id');
            })
                ->where('cps.status', 0)
                ->where('cps.payment_date', '<=', $date)
                ->whereIn('contracts.status', [1, 3, 4])
                ;
        });
        $count = $query->count();
        $buyers = $query->paginate(10);


        if ($user->can('modify', new Buyer())) {

            return view('panel.buyer.cards_delay', compact('buyers', 'count'));
        } else {

            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        }

    }


    /**
     * Buyers list data table
     * @param array|Collection $items
     * @return array
     */
    protected function formatDataTables($items)
    {

        $i = 0;
        $j = 0;
        $data = [];
        $cards_quid = [];

        $statuses = [
            0 => 'Новый',
            1 => 'Новый',
            2 => 'Ожидание',
            3 => 'Отказ',
            4 => 'Верифицирован',
            5 => 'Паспорт',
            6 => 'Паспорт',
            7 => 'Паспорт',
            8 => 'Блокирован',
            9 => 'Блокирован',
            10 => 'Селфи',
            11 => 'Прописка',
            12 => 'Доверитель',
        ];

        foreach ($items as $item) {
            //dd($item->cards);
            foreach ($item->cards as $card) {
                $card_number = EncryptHelper::decryptData($card->card_number);
                $data[ $i ]['cards'][ $j ][] = $card->card_name;
                $data[ $i ]['cards'][ $j ][] = $card_number;
                $data[ $i ]['cards'][ $j ][] = $card->phone;
                $data[ $i ]['cards'][ $j ][] = $card->balance;
                $data[ $i ]['cards'][ $j ][] = $card->type;

                if($card->sms_info == 0){
                    $data[ $i ]['cards'][ $j ][] = 'ON';
                }else{
                    $data[ $i ]['cards'][ $j ][] = 'OFF';
                }
                if($card->status == 0){
                    $data[ $i ]['cards'][ $j ][] = 'активная';
                }else{
                    $data[ $i ]['cards'][ $j ][] = 'не активная';
                }
                $j++;
            }
            //dd($data[ $i ]['cards']);

            $debtClass = $item->totalDebt > 0 ? 'red' : '';
            $passport_number = $item->personals->passport_number ?? '';

            $status = @$statuses[$item->status];
            if (!isset($statuses[$item->status])) {
                Log::channel('users')->info('status not found: ' . $item->status);
            }

            $icon = $item->status == 4 ? '<img class="icon-status" src="/images/icons/icon_ok_circle_green.svg" />' : '<img class="icon-status" src="/images/icons/icon_attention.svg" />';
             $data[$i][] = $icon . ' ' . $status ;
            $data[$i][] = "<div class='id'><a href='" . localeRoute('panel.buyers.show', $item->id) . "'>ID {$item->id}</a></div>";
            //$data[$i][] = "<div class='fio'>{$item->fio}</div>";
            $data[ $i ][] = '<div class="client"><a target="_blank" href="'.localeRoute('panel.buyers.show', $item->id).'">' . $item->fio . '</a></div>';
            $data[$i][] = "<div class='rating'>{$item->phone}</div>";
            $data[$i][] = '<div class="debt ' . $debtClass . '">' . number_format($item->totalDebt, 2, '.', '&nbsp;') . '</div>';
            /*$data[ $i ][] = "<div class='rating'>{$item->settings->rating}</div>";*/
            $data[$i][] = '<button onclick="addCardsHumoById('.$item->id.')" class="btn btn-sm btn-archive" type="button">'.str_replace(' ', '&nbsp;', __('app.btn_upload_humo')).'</button>';
            //dd($data);
            $i++;
        }

        return parent::formatDataTables($data);
    }

    /**
     * add humo cards to users cards
     * @return mixed
     */
    public function addCardsHumo()
    {
        $request = new Request();
        $cards_quid = [];

        $date = date('Y-m-d 23:59:00', strtotime('-10 day'));
        $query = Buyer::query(); //user
        $query->whereHas('contracts', function ($query) use ($date) {
            $query->leftJoin('contract_payments_schedule as cps', function ($query) {
                $query->on('cps.contract_id', 'contracts.id');
            })
                ->where('cps.status', 0)
                ->where('cps.payment_date', '<=', $date)
                ->whereIn('contracts.status', [1, 3, 4]);
        });
        $buyers = $query->get();

        // если такая карта уже есть, не добавляем
        foreach ($buyers as $buyer) {
            if (isset($buyer->cards)) {
                foreach ($buyer->cards as $card) {
                    $cards_quid[] = $card->guid;
                }
            }
        }

        foreach ($buyers as $buyer) {

                $phone = $buyer->phone;
                $request->merge([
                    'phone' => $phone,
                    'type' => 2
                ]);

                $result = \App\Http\Controllers\Core\UniversalController::getCardsList($request);

                if (isset($result['result']['cards'])) {
                    $new_cards = $result['result']['cards'];


                    foreach ($new_cards as $new_card) {
                        if (isset($new_card['number'])) {
                            $card_number = EncryptHelper::encryptData($new_card['number']);
                            $card_valid_date = EncryptHelper::encryptData($new_card['expire']);
                            $card_phone = EncryptHelper::encryptData($new_card['phone']);
                            $type = EncryptHelper::encryptData('HUMO');
                            $guid = md5($new_card['number']);

                            if (!in_array($guid, $cards_quid)) {
                                $request->merge([
                                    'info_card' => [
                                        'card_number' => $card_number,
                                        'card_valid_date' => $card_valid_date
                                    ]
                                ]);

                                $resl = \App\Http\Controllers\Core\UniversalController::getCardInfo($request);
                                $user_cards = new Card();
                                $user_cards->user_id = $buyer->id;
                                $user_cards->card_name = $resl['result']['owner'];
                                $user_cards->card_number = $card_number;
                                $user_cards->card_valid_date = $card_valid_date;
                                $user_cards->phone = $card_phone;
                                $user_cards->type = $type;
                                $user_cards->guid = $guid;
                                $user_cards->status = 0;
                                $user_cards->hidden = 0;
                                $user_cards->card_number_prefix = substr($new_card['number'], 0, 8);

                                if ($user_cards->save()) {
                                    $cards_quid[] = $guid;
                                }
                            }
                        } else {
                            foreach ($new_card as $new_card) {
                                $card_number = EncryptHelper::encryptData($new_card['number']);
                                $card_valid_date = EncryptHelper::encryptData($new_card['expire']);
                                $card_phone = EncryptHelper::encryptData($new_card['phone']);
                                $type = EncryptHelper::encryptData('HUMO');
                                $guid = md5($new_card['number']);

                                if (!in_array($guid, $cards_quid)) {
                                    $request->merge([
                                        'info_card' => [
                                            'card_number' => $card_number,
                                            'card_valid_date' => $card_valid_date
                                        ]
                                    ]);

                                    $resl = \App\Http\Controllers\Core\UniversalController::getCardInfo($request);
                                    $user_cards = new Card();
                                    $user_cards->user_id = $buyer->id;
                                    $user_cards->card_name = $resl['result']['owner'];
                                    $user_cards->card_number = $card_number;
                                    $user_cards->card_valid_date = $card_valid_date;
                                    $user_cards->phone = $card_phone;
                                    $user_cards->type = $type;
                                    $user_cards->guid = $guid;
                                    $user_cards->status = 0;
                                    $user_cards->hidden = 0;
                                    $user_cards->card_number_prefix = substr($new_card['number'], 0, 8);

                                    if ($user_cards->save()) {
                                        $cards_quid[] = $guid;
                                    }
                                }
                            }
                        }

                    }
                }

        }
        return true;

    }






}
