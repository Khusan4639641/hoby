<?php

namespace App\Enums;

class MKOInfoCodesEnum
{
    public const IC001 = "001"; // Информация об остатках на лицевых счетах и оборотах
    public const IC002 = "002"; //002 Информация о клиентах микрокредитной организации
    public const IC003 = "003"; //003 Информация по кредитным договорам
    public const IC004 = "004"; //004 Информация по обеспечению кредитных договоров
    public const IC005 = "005"; //005 Информация по депозитам, размещенным в банках
    public const IC006 = "006"; //006 Информация по кредитам и лизингу к оплате
    public const IC007 = "007"; //007 Дополнительная информация о микрокредитной организации
    public const IC008 = "008"; //008 Дополнительная информация об учредителях микрокредитной организации

    public static function toArray(): array
    {
        return array_values(self::reflection()->getConstants());
    }

    private static function reflection(): \ReflectionClass
    {
        return new \ReflectionClass(static::class);
    }
}
