<?php


namespace App\Http\Controllers\Web\Billing;
use \App\Http\Controllers\Core\StatisticsController as Controller;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;


class StatisticsController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function index() {
        $user = Auth::user();

        if (!$user->hasRole( 'partner' )) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );
            return redirect( localeRoute( 'billing.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {

            /*
            $statistics = $result['data']['statistics'];
            $affiliates = $result['data']['affiliates'];
            return view( 'billing.statistics.report', compact('user', 'statistics', 'affiliates') );
            */


            if ( $company_ = Company::where( 'id', $user->company_id )->first() ) {
                $company = ($company_->parent_id > 0) ? false : true;
            } else {
                $company = false;
            }


            //if ($user->hasRole('finance') || $user->hasRole('admin') ) {
                return view('billing.statistics.report',
                    ['title1' => 'Бухгалтерия',
                        'title2' => 'Списания',
                        'title3' => 'Пополнения',
                        'title4' => 'Верификация',
                        'title5' => 'Договора',
                        'title6' => 'Просрочка',
                        'title7' => 'Вендора',
                        'access' => 'sales_finance',
                        'model' => 'orders',
                        'model2' => 'payments',
                        'model3' => 'history',
                        'model4' => 'verified',
                        'model5' => 'contracts',
                        'model6' => 'delays',
                        'model7' => 'vendors',
                        'model8' => 'filials',
                        'model9' => 'filials-cancel',
                        'company' => $company,
                        'company_id'        => $user->company_id    ?? null,
                        'company_parent_id' => $company_->parent_id ?? null,
                        'user_id'           => $user->id            ?? null,
                    ]);
            //}


        }


    }

    public function graph() {
        $user = Auth::user();
        $result = $this->partner();
        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );
            return redirect( localeRoute( 'billing.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            $statistics = $result['data']['statistics'];
            $affiliates = $result['data']['affiliates'];
            return view( 'billing.statistics.index', compact('user', 'statistics', 'affiliates') );
        }
    }
}
