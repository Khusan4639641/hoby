<?php

namespace App\Http\Controllers\Web\Billing;

use App\Http\Controllers\Core\PartnerProfileController;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;

use App\Models\Partner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends PartnerProfileController
{

    public function __construct()
    {
        parent::__construct();
        // $this->middleware('profile', ['except' => 'verify']);

    }

    public static function userInfo()
    {
        /* $info = Cache::get('partner.info');

         if($info == null) {*/

        //Получаем покупателя
        $partner = Partner::find(Auth::user()->id)->load('company');


        $info = [
            'company_id' => $partner->company->id ?? 0,
            'company_description' => $partner->company->short_description ?? '',
            'company_name' => $partner->company->name ?? '',
            'company_inn' => $partner->company->inn ?? '',
            'logo' => null,
            'api_token' => $partner->api_token
        ];

        if ($partner->company && $partner->company->logo) {
            $info['logo'] = $partner->company->logo->preview ?? null;
        }

        //Cache::put('partner.info', $info, 900);
        /*}*/

        return $info;
    }


    /**
     * Aside user card
     *
     * @return Application|Factory|View
     */
    public static function card()
    {
        $info = self::userInfo();
        //$info['company_description'] = Str::limit($info['company_description'], 50);

//      original card view
//        return view('templates/billing/parts/card', compact('info'));

//        edited card view
        return view('templates/billing/parts/edited/card', compact('info'));
    }


    /**
     * Display the specified resource.
     *
     * @return void
     */
    public function show()
    {
        $user = Auth::user();
        $result = $this->detail($user->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect('billing')->with('message', $this->result['response']['message']);
        } else
            return view('billing.profile.show', $result['data']);
    }


    /**
     * Display the specified resource.
     *
     * @return void
     */
    public function settings()
    {
        $user = Auth::user();
        $result = $this->detail($user->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect('billing')->with('message', $this->result['response']['message']);
        } else
            return view('billing.profile.settings', $result['data']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return Application|RedirectResponse|Redirector
     */
    public function edit()
    {
        $user = Auth::user();
        $result = $this->detail($user->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect('billing')->with('message', $this->result['response']['message']);
        } else
            return view('billing.profile.edit', $result['data']);


    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Partner $partner
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request)
    {
        $partner = Partner::find(Auth::user()->id);

        $request->merge(['id' => $partner->id]);
        $result = $this->modify($request);

        if ($result['status'] != 'success') {

            //Define redirect route
            if ($result['response']['code'] == 403) {
                $route = localeRoute('billing.index');
            } else
                $route = localeRoute('billing.profile.edit', $partner);

            return redirect($route)
                ->withErrors($result['response']['errors'])
                ->withInput()
                ->with('message', $result['response']['message']);

        } else {
            return redirect(localeRoute('billing.profile.index'))->with('message', $result['response']['message']);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Partner $partner
     * @return Application|RedirectResponse|Redirector
     */
    public function updateSettings(Request $request)
    {
        $partner = Partner::find(Auth::user()->id);

        $request->merge(['id' => $partner->id]);
        $result = $this->modifySettings($request);

        if ($result['status'] != 'success') {

            //Define redirect route
            if ($result['response']['code'] == 403) {
                $route = localeRoute('billing.index');
            } else
                $route = localeRoute('billing.profile.edit', $partner);

            return redirect($route)
                ->withErrors($result['response']['errors'])
                ->withInput()
                ->with('message', $result['response']['message']);

        } else {
            return redirect(localeRoute('billing.profile.settings'))->with('message', $result['response']['message']);
        }
    }


}
