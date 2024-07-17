<?php

namespace App\Exports;


use App\Helpers\NdsStopgagHelper;
use App\Models\OrderProduct;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


// вендора с отмененными договорами
class VendorsCancelExport
{

    public static function report(Request $request)
    {
        $expDate = NdsStopgagHelper::getExpiryDate();
        $curNds = config('test.nds');

        $header = [ "Договор",
                "Спецификация",
                "Срок кредите",
                "Дата Отмены",
                "Создан",
                "Компания",
                "Торговая компания (на рус.)",
                "Бренд",
                "ИНН Поставщика",
                "Наименование Товара",
                "Количество",
                "Цена",
                "Стоимость поставки",
                "НДС",
                "Сумма НДС",
                "Стоимость с НДС",
                "ID кредита",
                "Покупатель",
                "Статус",
                "Статус акта",
                "Статус фото с клиентом",
                "Статус IMEI"];

        $sql = "Select
                companies.uniq_num as 'Договор',
                contracts.prefix_act as 'Спецификация',
                contracts.period as 'Срок кредите',
                contracts.canceled_at as 'Дата Отмены',
                contracts.created_at as 'Создан',
                companies.`name` as 'Компания',
                general_companies.name_ru as 'Торговая компания (на рус.)',
                companies.brand as 'Бренд',
                companies.inn as 'ИНН Поставщика',
                order_products.`name` as 'Наименование Товара',
                order_products.amount as 'Количество',

                ROUND(ROUND(order_products.price_discount/ 1+ IF(DATE(order_products.created_at) > '$expDate', '$curNds', 0.15), 2)/order_products.amount, 2) as 'Цена',
                ROUND(order_products.price_discount/1 + IF(DATE(order_products.created_at) > '$expDate', '$curNds', 0.15), 2) as 'Стоимость поставки',
                IF(partner_settings.nds =1, IF(DATE(order_products.created_at) > '$expDate', '". 100*$curNds . "%' , '15%'), '0%') as 'НДС',
                ROUND(order_products.price_discount/ (1 + IF(DATE(order_products.created_at) > '$expDate', '$curNds', 0.15)) * 0.15, 2)  as 'Сумма НДС',
                order_products.price_discount as 'Стоимость с НДС',
                contracts.id as 'ID кредита',
                (SELECT CONCAT(users.name, ' ', users.surname, ' ', users.patronymic) from users where id = contracts.user_id) as 'Покупатель',
                    CASE
                        WHEN contracts.`status` = 1 THEN 'В рассрочке'
                        WHEN contracts.`status` = 3 THEN 'Просрочен'
                        WHEN contracts.`status` = 4 THEN 'Просрочен'
                        WHEN contracts.`status` = 5 THEN 'Отменён'
                        WHEN contracts.`status` = 9 THEN 'Закрыт'
                    ELSE 'На модерации'
                END as 'Статус',
                if(contracts.act_status = 0, 'Акт не загружен', IF(contracts.act_status = 2, 'Акт не прошел проверку', 'Акт есть')) as 'Статус акта',
                if(contracts.client_status = 0, 'фото с клиентом не загружено', IF(contracts.client_status = 2, 'фото  с клиентом не прошло проверку', 'фото с клиентом есть')) as 'Статус фото с клиентом',
                if(contracts.imei_status = 0, 'IMEI не загружен', IF(contracts.imei_status = 2, 'IMEI не прошел проверку', 'фото IMEI есть')) as 'Статус IMEI'

                from contracts
                LEFT JOIN companies ON contracts.company_id = companies.id
                LEFT JOIN general_companies ON contracts.general_company_id = general_companies.id
                LEFT JOIN order_products ON contracts.order_id = order_products.order_id
                LEFT JOIN partner_settings ON contracts.company_id = partner_settings.company_id
                LEFT JOIN orders ON contracts.order_id = orders.id
                WHERE contracts.company_id = ".Auth::user()->company_id." AND ";

        switch ($request->type) {

            case 'custom':

                if (is_array($request->date)) {

                    $date_from = $request->date[0];
                    $date_to = $request->date[1];

                } else {

                    [$date_from,$date_to] = explode(',',$request->date);
                }

                if (!empty($date_from)) {

                    $date_from = "'".date('Y-m-d 00:00:00', strtotime($date_from ))."'";
                }

                if (!empty($date_to)) {

                    $date_to = "'".date('Y-m-d 23:59:59', strtotime($date_to ))."'";
                }

                if (!is_null($date_from) && !is_null($date_to)) {
                    $sql .= " contracts.created_at BETWEEN $date_from and $date_to "; // confirmed_at - дата подтверждения ??
                }

                break;

            case 'last_7_days': // за последние 7 дней

                $date_from = "'". date('Y-m-d', strtotime('-6 days'))."'";
                $date_to = "'".date('Y-m-d')."'";

                $sql .= " contracts.created_at BETWEEN $date_from and $date_to "; // confirmed_at - дата подтверждения ??

                break;

            case 'last_week': // за неделю

                $w = date('w');

                if ($w == 0) {

                    $dt = 6;

                } else {

                    $dt = $w - 1;
                }

                $date_from = "'".date('Y-m-d 00:00:00',strtotime('-' . $dt .' days'))."'";
                $date_to = "'".date('Y-m-d 23:59:59')."'";

                $sql .= " contracts.created_at BETWEEN $date_from AND $date_to "; // confirmed_at - дата подтверждения ??

                break;

            case 'last_month': // за месяц

                $m = date('m');
                $date_from = "'".date('Y-' . $m . '-01 00:00:00')."'";
                $date_to = "'".date('Y-m-d 23:59:59')."'";
                $sql .= " contracts.created_at BETWEEN $date_from AND $date_to "; // confirmed_at - дата подтверждения ??
                break;

            case 'last_half_year': // за полгода

                $date_from = "'".date('Y-m-d H:i:s', strtotime( '-6 months'))."'";
                $date_to ="'". date('Y-m-d 23:59:59')."'";

                $sql .= " contracts.created_at BETWEEN $date_from AND $date_to "; // confirmed_at - дата подтверждения ??

                break;

            case 'last_day': // текущий день

            default:

                $date_from = "'".date('Y-m-d 00:00:00', time())."'";
                $date_to = "'".date('Y-m-d 23:59:59', time())."'";

            $sql .= " contracts.created_at BETWEEN $date_from AND $date_to "; // confirmed_at - дата подтверждения ??
        }
        $sql .= " ORDER BY order_products.created_at DESC";
        $products = DB::select($sql);

        $data = [];
        foreach ($products as $product)
        {
            $data[] = array_values((array) $product);
        }
        return [
                 'header' => $header,
                 'values' => array_values((array)$data) ];
    }



    public static function headings()    {
        $header = [
            'Договор',
            'Спецификация',
            'Срок кредита',
            'Дата Отмены',
            'Создан',
            'Компания',
            "Торговая компания (на рус.)",  // dev_nurlan 07.04.2022
            'Бренд',
            'ИНН Поставщика',
            'Наименование Товара',
            'Количество',
            'Цена',
            'Стоимость поставки',
            'НДС',
            'Сумма НДС',
            'Стоимость с НДС',
            'ID кредита',
            'Покупатель',
            'Статус',
            'Акт',
            'IMEI',
            'Фото с клиентов'
        ];

        $header = implode(';',$header);

        return $header;

    }


}
