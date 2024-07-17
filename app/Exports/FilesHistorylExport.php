<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// вендора с отмененными договорами
class FilesHistorylExport
{

    public static function report(Request $request)
    {
        $sql = "Select id, name,
                        surname, patronymic,
                        phone, birth_date,
                        verify_message, status,
                        created_at, created_by,
                        updated_at, verified_by,
                        kyc_status, kyc_id,
                        is_saller,  device_os,
            (select CONCAT(name, ' ', surname) from users k where k.id = u.kyc_id ) as kyc_fio from users u where ";

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
                    $sql .= " created_at BETWEEN $date_from and $date_to "; // confirmed_at - дата подтверждения ??
                }

                break;

            case 'last_7_days': // за последние 7 дней

                $date_from = "'". date('Y-m-d', strtotime('-6 days'))."'";
                $date_to = "'".date('Y-m-d')."'";

                $sql .= " created_at BETWEEN $date_from and $date_to "; // confirmed_at - дата подтверждения ??

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

                $sql .= " created_at BETWEEN $date_from AND $date_to "; // confirmed_at - дата подтверждения ??

                break;

            case 'last_month': // за месяц

                $m = date('m');
                $date_from = "'".date('Y-' . $m . '-01 00:00:00')."'";
                $date_to = "'".date('Y-m-d 23:59:59')."'";
                $sql .= " created_at BETWEEN $date_from AND $date_to "; // confirmed_at - дата подтверждения ??
                break;

            case 'last_half_year': // за полгода

                $date_from = "'".date('Y-m-d H:i:s', strtotime( '-6 months'))."'";
                $date_to ="'". date('Y-m-d 23:59:59')."'";

                $sql .= " created_at BETWEEN $date_from AND $date_to "; // confirmed_at - дата подтверждения ??

                break;

            case 'last_day': // текущий день

            default:

                $date_from = "'".date('Y-m-d 00:00:00', time())."'";
                $date_to = "'".date('Y-m-d 23:59:59', time())."'";

                $sql .= " created_at BETWEEN $date_from AND $date_to "; // confirmed_at - дата подтверждения ??
        }
        $sql .= " and kyc_id is not null";
        $result = DB::select($sql);
        return $result;
    }



    public static function headings()    {
        $header = [
            'ID',
            'NAME',
            'SURNAME',
            'PATRONYMIC',
            'PHONE',
            'BIRTH_DATE',
            "VERIFY_MESSAGE",
            'STATUS',
            'CREATED_DATE',
            'CREATED_BY',
            'UPDATED_DATE',
            'VERIFIED_BY',
            'KYC_STATUS',
            "KYC_ID",
            'IS_SALLER',
            'DEVICE_OS',
            'KYC_FIO',
        ];

        return $header;
    }
}
