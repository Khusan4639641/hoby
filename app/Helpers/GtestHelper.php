<?php
namespace App\Helpers;

use App\Http\Controllers\Core\CardController;
use App\Models\Card;
use App\Models\Catalog;
use App\Models\CatalogCategory;
use App\Models\Company;
use App\Models\News;
use App\Models\PayService;
use App\Models\PaySystem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Class MenuHelper
 * @package App\Helpers
 */
class GtestHelper {


    // для вставки в BuyerController

    public static function paySystems()
    {
        $data = [];

        if( $paySystems = PaySystem::where('status',1)->get() ){

            foreach ($paySystems as $paySystem){
                $data[] =
                    [
                        "title" => $paySystem->title,
                        "img" => $paySystem->getImage(),
                        "url" => $paySystem->url,
                        "id" => $paySystem->id
                    ];
            }

        }

        return ['status'=>'success','data'=>$data];

    }

    public static function cards(){

        $user = Auth::user();

        $data = [];

        // получить все карты пользователя
        if( $cards = Card::where('user_id',$user->id)->get() ){
            foreach ($cards as $card){
                $data[] = [
                    "title" => EncryptHelper::decryptData($card->card_name),
                    "img" => CardHelper::getImage(EncryptHelper::decryptData($card->type)),
                    "pan" => CardHelper::getCardNumberMask( EncryptHelper::decryptData($card->card_number) ),
                    "exp" => EncryptHelper::decryptData($card->card_valid_date),
                    "id" => $card->id
                ];
            }

        }

        return ['status'=>'success','data'=> $data];

    }

    public static function addDeposit(Request $request){

        // !! значение в сум, не в тийинах !!
        if($request->amount && $request->amount>=1000 ){
            if($request->card_id ){

                // списание с карты на баланс клиента без смс информирования

                if($card = Card::find($request->card_id)) {

                    $cardController = new CardController();
                    $request->merge(['card_id' => $card->id, 'info_card' =>
                        ['token' => EncryptHelper::decryptData($card->token),
                         //'card_id' => $card->id,
                        'card_number' => EncryptHelper::decryptData($card->card_number),
                        'card_valid_date' => EncryptHelper::decryptData($card->card_valid_date)]
                    ]);

                    $result = $cardController->payment($request); // оплата

                    if($result['status']=='success'){

                        // пополняем лицевой счет пользователя
                        if($user = User::find(Auth::user()->id)){
                            $user->settings->balance += $request->amount; // в сумм
                            $user->settings->save();
                        }

                    }

                    return [
                        'status' => $result['status']
                    ];

                }else{
                    $error = 'card_not_found';
                }
            }else{
                $error = 'card_id_not_fill';
            }
        }else{
            $error = 'amount_incorrect';
        }

        return [
            'status'=> 'error','info'=>$error
        ];


    }


    public static function catalog(){

        $data = [];

        if( $catalog = Catalog::where('status',1)->orderBy('pos')->get() ){

            foreach ($catalog as $cat){
                $data[] = [
                    'title' => $cat->title,
                    'img' => $cat->getImage(),
                    'id' => $cat->id
                ];
            }


        }

        return [ 'status'=>'success', 'data'=>$data ];

    }

    public static function catalogPartners(Request $request){

        $data = [];

        if($request->catalog_id) {
            if ($catalog = Catalog::with('companies')->where('status', 1)->where('id', $request->catalog_id)->orderBy('pos')->get()) {

                foreach ($catalog->companies as $company) {
                    $data[] = [
                        'title' => $company->title,
                        'img' => $company->getImage(),
                        'id' => $company->id
                    ];
                }

            }

            return ['status' => 'success', 'data' => $data];
        }

        return ['status' => 'error', 'info' => 'catalog_id not fill'];

    }


    // все филиалы партнера
    public static function catalogPartner(Request $request){

        $data = [];

        if($request->partner_id) {
            if ( $filials = Company::where('status', 1)->where('parent_id', $request->partner_id)->get() ) {

                foreach ($filials as $filial) {
                    $data[] = [
                         'fillial_id'=> $filial->id, // Номер филиала партнера в системе test
                         'title'=> $filial->title, // Имя партнера
                         'address'=> $filial->address, // Адресс партнера
                         'img'=> $filial->getImage(), // Лого партнера
                         'phone'=> $filial->phone, // номер телефона партнера
                    ];
                }

            }

            return ['status' => 'success', 'data' => $data];
        }

        return ['status' => 'error', 'info' => 'partner_id not fill'];

    }

    public static function payServices(Request $request){

        $data = [];

        if($services = PayService::where('status',1)->get()){

            foreach ($services as $service) {

                $data[] = [
                    'id' => $service->id,
                    'title' => $service->name,
                    'type' => $service->type==1 ? 'mobile' : 'other', // тип
                    'img' => $service->getImgAttribute(), // logo
                ];

            }

        }

        return ['status'=>'success','data' => $data];

    }


}
