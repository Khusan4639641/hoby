<?php


namespace App\Http\Controllers\Web\Panel;

use App\Helpers\EncryptHelper;
use App\Http\Controllers\Core\FinanceController as Controller;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\Partner;
use App\Models\Payment;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class FinanceController extends Controller {

    public function index() {
        $user = Auth::user();
        if ( $user->can( 'modify', new Payment() ) ) {
            return view( 'panel.finance.index' );
        } else {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        }
    }


    protected function formatDataTables( $items ) {

        $i    = 0;
        $data = [];

        foreach ( $items as $item ) {

            /*$strProducts = '<div class="product-list">';
            foreach ($item->order->products as $product){
                $strProducts .= $product->name . "<br>";
            }
            $strProducts .= "</div>";*/
            $data[ $i ][] = "<div class='date'>{$item->order->created_at}</div>";
            $data[ $i ][] = "<div>№{$item->order->id}</div><div class='order-status'>{$item->order->status_caption}</div>";
            $data[ $i ][] = "<div>№{$item->id}</div><div class='contract-status status-{$item->status}'>{$item->status_caption}</div>";
            $data[ $i ][] = isset($item->partner->company)? $item->partner->company->name : '';
            $data[ $i ][] = $item->buyer->fio;
            $data[ $i ][] = "-";
            $data[ $i ][] = "-";
            $data[ $i ][] = $item->totalDebt;
            $data[ $i ][] = $item->order->credit;
            $data[ $i ][] = $item->order->debit;
            $data[ $i ][] = "<a class='order-link' href='" . localeRoute( 'panel.finances.order', $item->order ) . "'></a>";

            $i++;
        }

        return parent::formatDataTables( $data );
    }

    /**
     * @param $id
     *
     * @return Application|Factory|RedirectResponse|Redirector|\Illuminate\View\View
     */
    public function show( $id ) {

    }

    public function edit( $id ) {


    }

    public function order( $id ) {
        $result = $this->detailOrder($id);
        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.finances.index'))->with('message', $this->result['response']['message']);
        } else {
            $order = $result['data'];
            return view('panel.finance.order', compact('order'));
        }
    }
}
