<?php

namespace App\Http\Controllers\Web\Cabinet;

use App\Http\Controllers\Core\BuyerProfileController;
use App\Models\Area;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use App\Models\City;
use App\Models\Region;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;


class ProfileController extends BuyerProfileController
{


    /**
     * @return array|mixed|null
     */
    public static function userInfo() {
        $buyer = Buyer::find(Auth::user()->id)->load('settings');

        //Debts
        $debt = 0;
        if(count($buyer->debts) > 0)
            foreach($buyer->debts as $item)
                $debt += $item->balance;

        $info = null; //Cache::get('user.info.'.$user->id);

        if(
            $info == null /*||
            $info->balance != $buyer->settings->balance ||
            $info->limit != $buyer->settings->limit ||
            $info->personal_account != $buyer->settings->personal_account*/
        ) {

            $info = [
                'id'                    => $buyer->id,
                'phone'                 => $buyer->phone,
                'status'                => $buyer->status,
                'debt'                  => $debt,
                'buyer'                 => $buyer,
                'avatar'                => $buyer->avatar?Storage::url($buyer->avatar->path):null,
                'fio'                   => $buyer->fio,
                'rating'                => @$buyer->settings->rating,
                'personal_account'      => @$buyer->settings->personal_account,
                'limit'                 => @$buyer->settings->limit,
                'period'                => @$buyer->settings->period,
                'balance'               => @$buyer->settings->balance,
                'zcoin'                 => @$buyer->settings->zcoin,
                'message'               => $buyer->verify_message
            ];

            //Задолженность покупателя
            /*if($buyer->contracts) {
                foreach ($buyer->contracts as $contract)
                    if ($contract->debts)
                        foreach ($contract->debts as $debt)
                            $info['debt'] += $debt->balance;
            }*/

            //Cache::put('user.info.'.$user->id, $info, 900);
        }

        return $info;
    }


    /**
     * Aside user card
     *
     * @return Application|Factory|View
     */
    public static function card(){
        $info = self::userInfo();
        return view('templates/cabinet/parts/card', compact('info'));
    }


    /**
     * Top user panel
     *
     * @return Application|Factory|View
     */
    public static function panel(){
        $info = self::userInfo();
        return view('templates/cabinet/parts/panel', compact('info'));
    }


    /**
     * Display the specified resource.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function show() {

        $user = Auth::user();
        $result = $this->detail($user->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            return redirect(localeRoute('cabinet.index'))->with('message', $this->result['response']['message']);
        } else
            return view('cabinet.profile.show', ['buyer' => $result['data']]);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function edit()
    {
        $user = Auth::user();
        $profile = $this->detail($user->id);
        if ($profile['status'] != 'success' && $profile['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect('billing')->with('message', $this->result['response']['message']);
        } else
            return view('cabinet.profile.edit', ['profile' => $profile['data']], compact('user'));
    }



    /**
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function verify() {
        $user         = Auth::user();

        if(in_array($user->status, [1, 3])){
            $result = $this->detail( $user->id);

            if ( $result['status'] != 'success' && $result['response']['code'] == 403 ) {
                $this->message( 'danger', __( 'app.err_access_denied' ) );

                return redirect( 'billing' )->with( 'message', $this->result['response']['message'] );
            } else {
                $buyer = $result['data'];

                if($buyer->personals) {

                    $personals['passport_number'] = $buyer->personals->passport_number;
                    $personals['passport_issued_by'] = $buyer->personals->passport_issued_by;
                    $personals['passport_date_issue'] = $buyer->personals->passport_date_issue;
                    $personals['home_phone'] = $buyer->personals->home_phone;
                    $personals['birthday'] = $buyer->personals->birthday;
                    $personals['pinfl'] = $buyer->personals->pinfl;
                    $personals['work_company'] = $buyer->personals->work_company;
                    $personals['work_phone'] = $buyer->personals->work_phone;

                    $personals['passport_selfie']['id'] = $buyer->personals->passport_selfie->id ?? null;
                    $personals['passport_selfie']['preview'] = isset($buyer->personals->passport_selfie) ? Storage::url($buyer->personals->passport_selfie->path) : null;
                    $personals['passport_first_page']['id'] = $buyer->personals->passport_first_page->id ?? null;
                    $personals['passport_first_page']['preview'] = isset($buyer->personals->passport_first_page) ? Storage::url($buyer->personals->passport_first_page->path) : null;
                    // $personals['passport_with_address']['id'] = $buyer->personals->passport_with_address->id ?? null;
                    // $personals['passport_with_address']['preview'] = isset($buyer->personals->passport_with_address) ? Storage::url($buyer->personals->passport_with_address->path) : null ;

                    $address = $buyer->addressResidential ?? new BuyerAddress();

                    $nameLocale = 'name' . ucfirst(app()->getLocale());

                    if ($address->region !== '') {
                        $address->areaList = Area::where('regionid', $address->region)->orderBy($nameLocale)->get();
                    }
                    if ($address->area !== '') {
                        $address->cityList = City::where('areaid', $address->area)->orderBy($nameLocale)->get();
                    }

                }else{
                    $personals = new BuyerPersonal();
                    $address = $buyer->addressResidential ?? new BuyerAddress();
                    $address->areaList = Area::get();
                    $address->cityList = City::get();
                }

                return view( 'cabinet.profile.verify', compact('buyer', 'personals', 'address'), compact( 'user' ) );
            }
        }else
            return redirect(localeRoute('cabinet.index'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request)
    {
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if( $result['response']['code'] == 403 )
                $route = localeRoute('cabinet.profile.show');
            else
                $route = localeRoute('cabinet.profile.edit');

            return redirect($route)
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            Cache::forget('user.info');
            return redirect(localeRoute('cabinet.profile.show'))->with( 'message', $result['response']['message'] );
        }
    }
}
