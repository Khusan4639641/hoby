<?php

namespace App\Http\Controllers\Web\Panel;

use App\Http\Controllers\Core\RecordController as Controller;
use App\Models\Record;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecordController extends Controller
{


    /**
     * @param array $items
     * @return array
     */
    protected function formatDataTables ($items = []){

        $i = 0;
        $data = [];
        foreach ( $items as $item ) {

            $contractLink ='<a href="'.localeRoute('panel.contracts.show', $item->contract_id).'">' . $item->contract_id . '</a>';
            $data[$i][] = $item->id;
            $data[$i][] = '<div class="contract_id">'.$contractLink.'</div>';
            $data[$i][] = "<div class='fio'>" . $item->kyc->fio . "</div>";
            $data[$i][] = $item->text;
            $data[$i][] = '<div class="created_at">' . $item->created_at . '</div>';

            $i ++;
        }

        return parent::formatDataTables($data);
    }

}
