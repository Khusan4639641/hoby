<?php

namespace App\Http\Controllers\Core;

use Illuminate\Http\Request;

class EmailController extends CoreController {

    /**
     *
     * @return bool
     */

    public function sendEmail(Request $request){


        $headers  = "Content-type: text/html; charset=utf-8 \r\n";

        $message = '<b>Дата:</b> ' . date('Y-m-d H:i')  . '<br>';
        $message .= '<b>Наименование организации: </b>' . $request->company . '<br>';
        $message .= '<b>Реализуемые товары или услуги: </b>' . $request->type  . '<br>';
        $message .= '<b>Номер телефона: </b>' . $request->phone  . '<br>';
        $message .= '<b>Имя: </b>' . $request->name  . '<br>';

        $mail = 'seller@test.uz' ;

        if(@mail($mail,'test Business New client',$message,$headers)){
            return ['status'=>'success'];
        }

        return ['status'=>'error'];
    }

}
