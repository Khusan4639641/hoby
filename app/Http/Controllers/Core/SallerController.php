<?php

namespace App\Http\Controllers\Core;

use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Http\Controllers\Core\Auth\RegisterController;
use App\Models\BuyerPersonal;
use App\Models\BuyerSetting;
use App\Models\Card;
use App\Models\Saller;
use App\Models\User;
use App\Services\API\V3\UserPayService;
use App\Rules\AffiliateInn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SallerController extends CoreController
{

    private $validatorRules = [
        'name'                    => [ 'required', 'string', 'max:255' ],
        'surname'                 => [ 'required', 'string', 'max:255' ],
        //'patronymic'              => [ 'required', 'string' ],
        'phone'                   => [ 'required', 'string' ],
        'pinfl'                     => [ 'required', 'string' ],
    ];

    /**
     * SallerController constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = app( Saller::class );
        $this->config = Config::get('test.preview');
        $this->loadWith = ['passport','company','personals'];
    }


    /**
     * @param array $params
     *
     * @return array
     */
    public function filter( $params = [] ) {


        $paramsSearch = [];

        //Searching query
        if ( isset( $params['search']) && $params['search'] != '' ) {

            if ( is_numeric( $params['search'] ) ){
                if( preg_match('/(998)\d{0,9}?$/',$params['search']) ){
                    $paramsSearch['phone__like']   = $params['search'];
                }else{
                    $paramsSearch['id__like'] = $params['search'];
                }
            } else {
                $paramsSearch['or__surname__like']    = $params['search'];
                $paramsSearch['or__name__like']       = $params['search'];
                $paramsSearch['or__patronymic__like'] = $params['search'];
            }
            unset( $params['search'] );

            if (isset($params['seller_company_brand'])) {
                $paramsSearch['seller_company_brand'] = $params['seller_company_brand'];
            }

            $filterSearch = parent::filter( $paramsSearch );

            $filterSearch = $filterSearch['result']->pluck( 'id' )->toArray();

            $params['id'] = $filterSearch;

        }
        $params['type'] = 'saller';
        $params['show'] = 'pinfl';

        return parent::filter( $params );
    }

    /**
     * @param $id
     * @param array $with
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    protected function single($id, $with = []) {
        $single = parent::single($id, array_merge($this->loadWith, []));
        $single->status_list = Config::get( 'test.user_status' );
        return $single;
    }

  /*  public function list(){

    }*/


    /**
     * @param $id
     * @return array|false|string
     */
    public function detail( $id ) {
        $user    = Auth::user();
        $saller = $this->single( $id );

        if ( $saller ) {
            if ( $user && $user->can( 'detail', $saller ) || $saller->status === 1 ) {
                $this->result['status'] = 'success';
                $this->result['data']   = $saller;
            } else {
                //Error: access denied
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }


    /**
     * @param Request $request
     * @return array|false|string
     */
    /*public function confirm( Request $request ) {
        $saller = Model::find( $request->saller_id );
        $user    = Auth::user();

        if ( $saller ) {

            if ( $user->can( 'modify', $saller ) ) {
                $saller->status = 1;
                $saller->save();

                $passwd = Str::random(12);
                $msg = Str::replaceArray('?', [$saller->id, $passwd], __('panel/saller.txt_sms_confirm'));
                SmsHelper::sendSms($saller->user->phone, $msg);
                $saller->user->password = Hash::make($passwd);
                $saller->user->save();

                RegisterController::generateApiToken($saller->user);

                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/saller.txt_confirm' ) );
            } else {
                //Error: access denied
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }


        }

        return $this->result();
    } */


    // перевыпустить пароль = на введенный админом номер телефона
    /**
     * @param Request $request
     * @return array|false|string
     */
    /* public function resend( Request $request ) {
        $saller = Model::find( $request->saller_id );

        //if($request->phone) $phone = correct_phone($request->phone);

        if ( $saller ) {
            $passwd = Str::random(12);
            $msg = Str::replaceArray('?', [$saller->id, $passwd], __('panel/saller.txt_sms_confirm'));
            SmsHelper::sendSms($saller->user->phone, $msg);  // input пользователя
            //SmsHelper::sendSms($phone, $msg);  // тел в бд продавца
            $saller->user->password = Hash::make($passwd);
            $saller->user->save();

            RegisterController::generateApiToken($saller->user);

            $this->result['status'] = 'success';
            $this->message( 'success', __( 'panel/saller.txt_resend' ) );
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'auth.error_company_is_empty' ) );
        }

        return $this->result();
    } */


    /**
     * @param Request $request
     * @return array|false|string
     */
    public function block( Request $request ) {
        $saller = Saller::find( $request->saller_id );
        $user    = Auth::user();

        if ( $saller ) {

            if ( $user->can( 'modify', $saller ) ) {
                $saller->status = 0;
                $saller->save();

                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/saller.txt_block' ) );
            } else {
                //Error: access denied
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }


        }

        return $this->result();
    }

    /**
     * @param Request $request
     * @return array|false|string
     */
    public function add(Request $request){
        $user = Auth::user();

        Log::info('add saller');
        Log::info($request);

        if($user->can('add', Saller::class)) {

            Log::info('can add');

            if( !$saller = Saller::where('phone',$request->phone)->first()){

                Log::info('no saller');

                $saller = new Saller();
                $saller->status       = User::KYC_STATUS_BLOCKED;

            }else{
                Log::info('saller exist ' . $saller->id);
            }

            Log::info('before save saller');

            //Create user
            $saller->is_saller    = 1;
            $saller->name         = $request->name;
            $saller->surname      = $request->surname;
            $saller->patronymic   = $request->patronymic;
            $saller->phone        = $request->phone;
            $saller->seller_company_id   = $request->company_id;


            if($saller->save()){
                RegisterController::generateApiToken($saller);
                //User role
                if(!$saller->hasRole('saller')) $saller->attachRole('saller');
            }

            Log::info('save saller');


            //Creating saller settings
            $sallerPersonal = new BuyerPersonal();
            $sallerPersonal->pinfl                = EncryptHelper::encryptData($request->pinfl);
            $sallerPersonal->pinfl_hash           = md5($request->pinfl);
            $sallerPersonal->pinfl_status  = 1;
            $sallerPersonal->user_id  = $saller->id;

            $sallerPersonal->passport_number      = EncryptHelper::encryptData($request->passport_number);
            $sallerPersonal->passport_number_hash = md5($request->passport_number);

            $sallerPersonal->save();


            Log::info('save saller pers');


            if(!$sallerSettings = BuyerSetting::where('user_id',$saller->id)->first()){
                $sallerSettings = new BuyerSetting();
            }
            $sallerSettings->user_id = $saller->id;
            $sallerSettings->save();
            (new UserPayService)->createClearingAccount($saller->user_id);
            Log::info('save saller sett');


            // добавление карты продавца
            $card = new Card();
            $card->user_id = $saller->id;

            $validDate = '';

            if($request->exp != ''){
                $year = substr($request->exp, 0, 2);
                $month = substr($request->exp, -2, 2);
                $validDate = "$month/$year";
            }

            $card->card_valid_date = EncryptHelper::encryptData($validDate);
            $type = CardHelper::checkTypeCard($request->card)['name'];
            $card->card_number = EncryptHelper::encryptData($request->card);
            $card->type = EncryptHelper::encryptData($type);
            $card->guid = md5($request->card);
            $card->card_number_prefix = substr($request->card, 0, 8);
            $card->save();

            Log::info('save saller ' . $saller->id);

            //Save files
            if (count($request->file()) > 0) {
                $params = [
                    'files' => $request->file(),
                    'element_id' => $saller->id,
                    'model' => 'saller'
                ];
                FileHelper::upload($params, [], true);

//                    if($saller->passport){
//                        //Making preview
//                        $previewName = 'preview_'.$saller->passport->name;
//                        $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
//                        $previewPath = $storagePath.str_replace($saller->passport->name, $previewName, $saller->passport->path);
//                        $preview = new ImageHelper($storagePath.$saller->passport->path);
//                        $preview->resize($this->config['width'], $this->config['height']);
//                        $preview->save($previewPath);
//
//                    }
//                    if($saller->passportAddress){
//                        //Making preview
//                        $previewName = 'preview_'.$saller->passportAddress->name;
//                        $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
//                        $previewPath = $storagePath.str_replace($saller->passportAddress->name, $previewName, $saller->passportAddress->path);
//                        $preview = new ImageHelper($storagePath.$saller->passportAddress->path);
//                        $preview->resize($this->config['width'], $this->config['height']);
//                        $preview->save($previewPath);
//
//                    }
            }

            /* Skip to phase 2 */
            // Update seller bonus sharers
            /*if ($request->bonus_sharers) {
                $this->processBonusSharers($saller, $request->bonus_sharers);
            }*/

            //Success: news item created
            $this->result['status'] = 'success';
            $this->message( 'success', __( 'billing/affiliate.txt_created' ) );

        } else {
            //Error: access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }

        return $this->result();
    }


    /**
     * @param Request $request
     *
     * @return array|false|string
     */
    public function modify( Request $request ) {

        $user    = Auth::user();
        $saller = Saller::find( $request->saller_id );

        if ( $saller ) {

            if ( $user->can( 'modify', $saller ) ) {

                $saller->name       = $request->name;
                $saller->surname    = $request->surname;
                $saller->patronymic = $request->patronymic;
                $saller->phone      = $request->phone;
                $saller->seller_company_id     = $request->company_id;
                $saller->save();

                //Settings

                $saller->personals->pinfl      = EncryptHelper::encryptData($request->pinfl);
                $saller->personals->pinfl_hash      = md5($request->pinfl);
                $saller->personals->pinfl_status      = 1;

                $saller->personals->passport_number      = EncryptHelper::encryptData($request->passport_number);
                $saller->personals->passport_number_hash = md5($request->passport_number);

                $saller->personals->save();

                // измененеие карты продавца

                if($request->card) {

                    if (!$card = Card::where('user_id', $saller->id)->first()) {
                        $card = new Card();
                        $card->user_id = $saller->id;
                    }

                    $validDate = '';

                    if ($request->exp != '') {
                        $year = substr($request->exp, 0, 2);
                        $month = substr($request->exp, -2, 2);
                        $validDate = "$month/$year";
                    }

                    $card->card_valid_date = EncryptHelper::encryptData($validDate);
                    $type = CardHelper::checkTypeCard($request->card)['name'];
                    $card->card_number = EncryptHelper::encryptData($request->card);
                    $card->type = EncryptHelper::encryptData($type);
                    $card->guid = md5($request->card);
                    $card->card_number_prefix = substr($request->card, 0, 8);
                    $card->save();
                }

                //Delete files
                $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];
                if(count($filesToDelete) > 0) FileHelper::delete($filesToDelete);

                //Save files
                if (count($request->file()) > 0) {
                    $params = [
                        'files' => $request->file(),
                        'element_id' => $saller->id,
                        'model' => 'saller'
                    ];
                    FileHelper::upload($params, [], true);
                }

                /* Skip to phase 2 */
                // Update seller bonus sharers
                /*if ($request->bonus_sharers) {
                    $this->processBonusSharers($saller, $request->bonus_sharers);
                }*/

                $this->result['status'] = 'success';
                $this->result['data']   = $saller;
                $this->message( 'success', __( 'panel/partner.txt_updated' ) );

            } else {
                //Error: access denied
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }

        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'auth.error_user_not_found' ) );
        }

        return $this->result();

    }

    private function processBonusSharers($seller, $bonus_sharers)
    {
        $bonus_sharers_ids = [];
        $old_bonus_sharers_ids = $seller->bonusSharers()->pluck('id')->toArray();

        foreach ($bonus_sharers as $bonus_sharer) {
            $bonus_sharers_ids[] = $seller->bonusSharers()->updateOrCreate(
              ['sharer_id' => $bonus_sharer['sharer_id']],
              $bonus_sharer
            )->id;
        }

        if (array_diff($old_bonus_sharers_ids, $bonus_sharers_ids)) {
            $seller->bonusSharers()->whereIn('id', array_diff($old_bonus_sharers_ids, $bonus_sharers_ids))->delete();
        }
    }

    private function isTotalBonusPercentCorrect($request) {

        $total_percent = $request->seller_bonus_percent;

        if ($request->bonus_sharers) {
            foreach ($request->bonus_sharers as $bonus_sharer) {
                $total_percent += $bonus_sharer['percent'];
            }
        }

        if ($total_percent != 100) {
            return false;
        }

        return true;
    }

}
