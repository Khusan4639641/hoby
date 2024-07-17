<?php

namespace App\Http\Controllers\Web\Cabinet;

use App\Helpers\FileHelper;
use App\Models\Order;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDF;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Core\OrderController as Controller;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {
        $user = Auth::user();
        $counter = [];

        //Active orders
        $params = [
            "params"    => [
                [
                    'contract|status'   => 1,
                    'status' => [2, 3, 4, 6, 7, 8],
                    'user_id'   => $user->id,
                ],
                [
                    'status' => [2, 3, 4, 6, 7],
                    'user_id'   => $user->id,
                    "query_operation"   => "or",
                ]
            ],
            'total_only' => 'yes'
        ];
        $counter['active'] = $this->filter($params)['total'];

        //On approve orders
        $params = [
            "params"    => [
                [
                    'status' => 1,
                    'user_id'   => $user->id,
                ]
            ],
            'total_only' => 'yes'
        ];
        $counter['approve'] = $this->filter($params)['total'];

        //Credit orders
        $params = [
            "params" => [
                [
                    'contract|status'   => 1,
                    'user_id'           => $user->id,
                    'status'      => [4, 6, 7, 8, 9],
                ]
            ],
            'total_only' => 'yes'
        ];
        $counter['credit'] = $this->filter($params)['total'];

        //Complete orders
        $params = [
            "params"    => [
                [
                    'status'        => [5, 9],
                    'user_id'           => $user->id,
                ],
                [
                    "query_operation"   => "or",
                    'contract|status'   => [5, 9],
                    'user_id'           => $user->id,
                ]
            ],
            'total_only' => 'yes'
        ];
        $counter['complete'] = $this->filter($params)['total'];

        return view('cabinet.order.index', compact('counter'));
    }


    /**
     * Display the specified resource.
     *
     * @param Order $order
     * @return Application|RedirectResponse|Redirector
     */
    public function show(Order $order)
    {

        Log::info('Web\Cabinet\OrderController');

        $result = $this->detail($order->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('cabinet.orders.index'))->with('message', $this->result['response']['message']);
        } else {

            if($result['data']['order']->contract) {
                $folderContact = 'contract/';
                $folder = $folderContact . $result['data']['order']->contract->id;

                # Offer .PDF
                $namePdf = 'buyer_offer_'.$result['data']['order']->contract->id.'.pdf';
                $link = $folder.'/'.$namePdf;

                if(!FileHelper::exists($link)) {
                    FileHelper::generateAndUploadPDF($link, 'cabinet.order.parts.offer_pdf', $result['data']);
                }
                $result['data']['offer_pdf'] = '/storage/contract/'.$result['data']['order']->contract->id.'/' . $namePdf;

                # Account .PDF
                $namePdf = 'buyer_account_'.$result['data']['order']->contract->id.'.pdf';
                $link = $folder.'/'.$namePdf;

                if(!FileHelper::exists($link)){
                    FileHelper::generateAndUploadPDF($link, 'cabinet.order.parts.account_pdf', $result['data']);
                }
                $result['data']['account_pdf'] = '/storage/contract/'.$result['data']['order']->contract->id.'/' . $namePdf;
            }
            //return view('cabinet.order.parts.offer_pdf', $result['data']);

            $result['data']['status_list'] = Config::get('test.order_status');
            return view('cabinet.order.show', $result['data']);
        }
    }
}
