<?php


namespace App\Http\Controllers\Web\Billing;

use App\Http\Controllers\Core\PartnerBuyerController;
use App\Models\Buyer;
use App\Models\Partner;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class BuyerController
 * @package App\Http\Controllers\Web\Billing
 */
class BuyerController extends PartnerBuyerController {

    /**
     * @return Application|Factory|View
     */
    public function index() {

        //Active orders
        $params = [
            'total_only' => 'yes'
        ];
        $counter['all'] = $this->filter($params)['total'];

        //Credit orders
        $params = [
            'status'           => 4,
            'total_only'        => 'yes'
        ];
        $counter['verified'] = $this->filter($params)['total'];


        //Credit orders
        $params = [
            'status'        => 2,
            'total_only' => 'yes'
        ];
        $counter['verification'] = $this->filter($params)['total'];
        return view( 'billing.buyer.index', compact('counter') );
    }


    /**
     * @param Collection | array $items
     * @return array
     */
    protected function formatDataTables( $items ) {

        $i    = 0;
        $data = [];
        foreach ( $items as $item ) {
            if(!is_null($item->personals))
                if(!is_null($item->personals->passport_selfie))
                    $data[$i][] = '<div class="preview" style="background-image: url(/storage/'.$item->personals->passport_selfie->path.')"></div>';
                else
                    $data[$i][] = '<div class="preview dummy"></div>';
            else
                $data[$i][] = '<div class="preview dummy"></div>';

            $data[ $i ][] = '<div class="id">ID '.$item->id.'</div>';
            $data[ $i ][] = '<div class="phone">'.$item->phone.'</div>';
            $data[ $i ][] = '<div class="name"><a href="' . localeRoute( 'billing.buyers.show', $item ) . '">'.$item->fio.'</div>';
            $data[ $i ][] = '<a class="buyer-link" href="' . localeRoute( 'billing.buyers.show', $item ) . '"><span class="d-inline d-sm-none">'.__('app.btn_more').'</span></a>';
            $data[ $i ][] = localeRoute( 'billing.buyers.show', $item );

            $i ++;
        }
        return parent::formatDataTables( $data );
    }


    /**
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function create() {
        $user = Auth::user();

        if ( $user->can( 'add', Buyer::class ) ) {
            Log::info('billing/buyers/create');
            return view( 'billing.buyer.add-client');
        } else {
            $this->message( 'danger', __( 'app.err_access_denied' ) );
            return redirect( localeRoute( 'billing.buyers.index' ) )->with( 'message', $this->result['response']['message'] );
        }
    }


    /**
     * @param $id
     *
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function show( $id) {

        $result = $this->detail($id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );
            return redirect( localeRoute( 'billing.buyers.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            $user = Auth::user();
            $orderController = new OrderController();

            //Approve orders
            $partner = Partner::find($user->id);
            $affiliatesId = null;
            $partnersId = [$user->id];

//            if($partner){
//                if($company = $partner->company){
//                    if($affiliates = $company->affiliates){
//                        $affiliatesId = $affiliates->pluck('id')->toArray();
//                    }
//                }
//            }
//
//            if($affiliatesId){
//                $partnersId = array_merge($partnersId, $affiliatesId);
//            }
            $params = [
                'params'    => [
                    [
                        'status'   => 1,
                        'partner_id'    => $partnersId,
                        'user_id'       => $result['data']['id'],
                    ]
                ],
                'total_only'    => 'yes'
            ];
            $counter['approve'] = $orderController->filter($params)['total'];


            //Active orders
            $params = [
                'params'    => [
                    [
                        'status'   => [4, 6, 7, 8],
                        'partner_id'    => $partnersId,
                        'user_id'       => $result['data']['id'],
                    ]
                ],
                'total_only'    => 'yes'
            ];
            $counter['active'] = $orderController->filter($params)['total'];

            //Credit orders
            $params = [
                'params'    => [
                    [
                        'credit__more'      => 0,
                        'partner_id'        => $partnersId,
                        'status'            => [4, 6, 7, 8, 9],
                        'user_id'       => $result['data']['id'],
                    ],
                    [
                        'query_operation'   => 'or',
                        'debit__more'   => 0,
                        'partner_id'   => $partnersId,
                        'status'       => [4, 6, 7, 8, 9],
                        'user_id'       => $result['data']['id'],
                    ]
                ],
                'total_only'        => 'yes'
            ];
            $counter['payment'] = $orderController->filter($params)['total'];

            //Complete orders
            $params = [
                'params'    => [
                    [
                        'status'            => [5, 9],
                        'partner_id'        => $partnersId,
                        'user_id'       => $result['data']['id'],
                    ]
                ],
                'total_only' => 'yes'
            ];
            $counter['complete'] = $orderController->filter($params)['total'];

            $partnersId = '[' . implode(',', $partnersId) . ']';

            return view ( 'billing.buyer.show', ['buyer' => $result['data'], 'counter' => $counter, 'partnersId' => $partnersId]);
        }
    }
}
