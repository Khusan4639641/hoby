<?php

namespace App\Enums;

class UzTaxesEnum
{
    public const GRANTING_LICENSE_PSIC = '10319005001000000'; // Услуги по предоставлению лицензии на право пользования ПО
    public const SERVICE_UNIT_SUM = '1501330'; // Единица измерения - услуга (сум)

    public const SERVICE_NAME = 'Вознаграждение за право использования ПО';
    public const SERVICE_SPIC = '10305008002000000'; // Оплата за право использования ПО
    public const SERVICE_PACKAGE_CODE = '1514295';
    public const SERVICE_UNITS = 25;
    public const SERVICE_TIN = '308349548'; // ИНН
    public const SERVICE_VAT = 0; // НДС
    public const SERVICE_VAT_PERCENT = 0; // Процент НДС
}
