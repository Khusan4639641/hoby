<?php

namespace App\Http\Controllers\Web\Panel;

use App\Http\Controllers\Core\CardController as Controller;
use App\Models\Card;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CardController extends Controller
{

    /**
     * @return Application|Factory|View
     */
    /*public function index() {
        return view( 'panel.cards.index' );
    }*/


    /**
     * @param array $items
     * @return array
     */
    protected function formatDataTables ($items = []){

        $i = 0;
        $data = [];
        foreach ( $items as $item ) {


            $data[$i][] = $item->card_name;
            $data[$i][] = $item->card_number;
            $data[$i][] = $item->phone;
            $data[$i][] = $item->balance;
            $data[$i][] = $item->type;

            if($item->sms_info == 0){
                $data[$i][] = 'ON';
            }else{
                $data[$i][] = 'OFF';
            }

            if($item->status == 0){
                $data[$i][] = __('app.btn_not_active');
                $data[$i][] = '<button onclick="activate('.$item->id.')" class="btn btn-sm btn-archive" type="button">'.__('app.btn_activate').'</button>';
//                $data[$i][] = '<button onclick="confirmDelete('.$item->id.')" type="button"
//                                class="btn-delete">'.__('app.btn_delete').'</button>';
            }elseif($item->status == 2){
                $data[$i][] = __('app.btn_deleted');
                $data[$i][] = '';
                $data[$i][] ='';
            }else{
                $data[$i][] = __('app.btn_active');
                $data[$i][] = '<button onclick="deactivate('.$item->id.')" class="btn btn-sm btn-archive" type="button">'.str_replace(' ', '&nbsp;', __('app.btn_deactivate')).'</button>';
                $data[$i][] ='';
            }


            $i ++;
        }

        return parent::formatDataTables($data);
    }

}
