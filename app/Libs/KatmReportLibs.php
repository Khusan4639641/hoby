<?php

namespace App\Libs;

class KatmReportLibs
{

    static public function replaceKeyToTitle(array $report): array
    {
        $arTitle = [
            "katm_sir" => "KATM-SIR",
            "duplicates" => "Дубликаты",
            "name" => "Наименование заёмщика",
            "old_name" => "Старое наименование заёмщика",
            "name_change" => "Измёненное наименование заёмщика",
            "subject" => "Cубъект",
            "client_type" => "Код типа клиента",
            "inn" => "ИНН (идентификационный номер налогоплательщика)",
            "birth_date" => "Дата рождения",
            "document_type" => "Код удостоверения личности",
            "document_serial" => "Серия документа",
            "document_number" => "Номер документа",
            "document_date" => "Дата выдачи удостоверяющего документа",
            "gender" => "Пол",
            "nibbd" => "Код клиента по НИББД (национальной информационной базы банковских депозиторов)",
            "region" => "Код области прописки",
            "local_region" => "Код района прописки",
            "address" => "Адрес по прописке",
            "phone" => "Номер телефона",
            "bank_claims" => "Обращения по банкам",
            "leasing_claims" => "Обращения по лизинговым компаниям",
            "lombard_claims" => "Обращения по ломбардам",
            "mko_claims" => "Обращения по микрокредитным организациям",
            "retail_claims" => "Обращения по ритейлерам",
            "declaration" => "Уведомление",
            "bank" => "Пользователь кредитного отчёта",
            "branch" => "Код Пользователя",
            "demand_id" => "Запрос Пользователя на получение кредитного отчета",
            "date" => "Дата запроса",
            "claim_id" => "Кредитная заявка",
            "claim_date" => "Дата подачи заявки",
            "report_type" => "Тип отчёта",
        ];
        return self::recursiveReplace($report, $arTitle);
    }

    static private function recursiveReplace(array $report, array $title): array
    {
        $newReport = [];
        foreach ($report as $key => $value) {
            if (isset($title[$key])) {
                $newKey = $title[$key];
            } else {
                $newKey = $key;
            }
            if (is_array($value))
                $newReport[$newKey] = self::recursiveReplace($value, $title);
            else
                $newReport[$newKey] = $value;
        }
        return $newReport;
    }

}
