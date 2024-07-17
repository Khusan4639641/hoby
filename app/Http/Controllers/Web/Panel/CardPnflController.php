<?php

namespace App\Http\Controllers\Web\Panel;

use App\Http\Controllers\Core\CardPnflController as Controller;
use App\Models\CardPnfl;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CardPnflController extends Controller
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
    protected function formatDataTables($items = [])
    {

        $i = 0;
        $data = [];
        foreach ($items as $item) {

            $data[$i][] = $item->fullName;
            $data[$i][] = $item->pan;
            $data[$i][] = '<button onclick="addCardsPhone(' . $item->card_phone . ')" class="btn btn-sm btn-archive" type="button">' . $item->card_phone . '</button>';

            if ($item->balance == 2) {
                $data[$i][] = '<p class="text-danger">----</p>';
            } else {
                $data[$i][] = number_format($item->balance / 100, 2, '.', ' ');
            }

            if ($item->sms_info == 0) {
                $data[$i][] = 'ON';
            } else {
                $data[$i][] = 'OFF';
            }

            if ($item->status == 0) {
                $data[$i][] = 'да';
            } elseif (in_array($item->status, [100, 101])) {  // 101 доступен или нет? проверить
                $data[$i][] = '-';
            } else {
                $data[$i][] = '<p class="text-danger">----</p>';  // если карта у нас есть в бд, а у них нет в возвращаемом массиве
            }

            if ($item->state == 0) {
                $data[$i][] = __('app.btn_not_active');
                $data[$i][] = '<button onclick="activatePnflCard(' . $item->id . ')" class="btn btn-sm btn-archive" type="button">' . __('app.btn_activate') . '</button>';
                /*$data[$i][] = '<button onclick="confirmDeletePnflCard('.$item->id.')" type="button"
                                class="btn-delete">'.__('app.btn_delete').'</button>';*/
            } elseif ($item->state == 2) {
                /*$data[$i][] = __('app.btn_deleted');*/
                $data[$i][] = '';
                $data[$i][] = '';
            } else {
                $data[$i][] = __('app.btn_active');
                $data[$i][] = '<button onclick="deactivatePnflCard(' . $item->id . ')" class="btn btn-sm btn-archive" type="button">' . str_replace(' ', '&nbsp;', __('app.btn_deactivate')) . '</button>';
                /*$data[$i][] ='';*/
            }

            $i++;
        }

        return parent::formatDataTables($data);
    }

}
