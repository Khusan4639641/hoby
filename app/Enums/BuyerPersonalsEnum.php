<?php


namespace App\Enums;


class BuyerPersonalsEnum
{
    public const BAD_SELFIE_WITH_PASSPORT       = 1;      /** Плохое селфи с паспортом */
    public const BAD_PASSPORT_FIRST_PAGE        = 2;      /** Плохое фото паспорта */
    public const BAD_PASSPORT_REGISTRATION_PAGE = 3;      /** Плохое фото прописки */
    public const BAD_SELFIE_WITH_ID_CARD        = 26;     /** Плохое селфи с ID-картой */
    public const BAD_ID_CARD_FIRST_PAGE         = 27;     /** Плохое фото ID-карты (1-ая страница) */
    public const BAD_ID_CARD_SECOND_PAGE        = 28;     /** Плохое фото ID-карты (2-ая страница) */
    public const BAD_ID_CARD_REGISTRATION_PAGE  = 29;     /** Плохое фото прописки ID-карты */
    public const LACKING_DOCUMENTS_MARRIAGE     = 30;     /** Недостающий документ (Свидетельство о бракосочетании/ЗАГС) */
    public const LACKING_DOCUMENTS_BANK         = 31;     /** Недостающий документ (Справка с банка о принадлежности карты владельцу/клиенту) */

    public const PASSPORT_SELFIE        = "passport_selfie";           /** Селфи с паспортом */
    public const PASSPORT_FIRST_PAGE    = "passport_first_page";       /** Фото паспорта */
    public const PASSPORT_WITH_ADDRESS  = "passport_with_address";     /** Фото прописки */
    public const ID_SELFIE              = "id_selfie";                 /** Селфи с ID-картой */
    public const ID_FIRST_PAGE          = "id_first_page";             /** Фото ID-карты (1-ая страница) */
    public const ID_SECOND_PAGE         = "id_second_page";            /** Фото ID-карты (2-ая страница) */
    public const ID_WITH_ADDRESS        = "id_with_address";           /** Фото прописки ID-карты */
    public const MARRIAGE_CERTIFICATE   = "marriage_certificate";      /** Свидетельство о бракосочетании / ЗАГС */
    public const CARD_IS_BUYERSS_CERTIFICATE = "card_is_buyerss_certificate"; /** Справка с банка о принадлежности карты владельцу/клиенту */

    public const BUYER_PERSONALS_ENUM = [
        "REASONS" => [
            self::BAD_SELFIE_WITH_PASSPORT,         // 0
            self::BAD_PASSPORT_FIRST_PAGE,          // 1
            self::BAD_PASSPORT_REGISTRATION_PAGE,   // 2
            self::BAD_SELFIE_WITH_ID_CARD,          // 3
            self::BAD_ID_CARD_FIRST_PAGE,           // 4
            self::BAD_ID_CARD_SECOND_PAGE,          // 5
            self::BAD_ID_CARD_REGISTRATION_PAGE,    // 6
            self::LACKING_DOCUMENTS_MARRIAGE,       // 7 (new 22.12.2022)
            self::LACKING_DOCUMENTS_BANK,           // 8 (new 22.12.2022)
        ],
        "TYPES" => [
            self::PASSPORT_SELFIE,              // 0
            self::PASSPORT_FIRST_PAGE,          // 1
            self::PASSPORT_WITH_ADDRESS,        // 2
            self::ID_SELFIE,                    // 3
            self::ID_FIRST_PAGE,                // 4
            self::ID_SECOND_PAGE,               // 5
            self::ID_WITH_ADDRESS,              // 6
            self::MARRIAGE_CERTIFICATE,         // 7 (new 22.12.2022)
            self::CARD_IS_BUYERSS_CERTIFICATE,  // 8 (new 22.12.2022)
        ],
        "TYPES_REASONS" => [
            self::PASSPORT_SELFIE       => self::BAD_SELFIE_WITH_PASSPORT,        // 0
            self::PASSPORT_FIRST_PAGE   => self::BAD_PASSPORT_FIRST_PAGE,         // 1
            self::PASSPORT_WITH_ADDRESS => self::BAD_PASSPORT_REGISTRATION_PAGE,  // 2
            self::ID_SELFIE             => self::BAD_SELFIE_WITH_ID_CARD,         // 3
            self::ID_FIRST_PAGE         => self::BAD_ID_CARD_FIRST_PAGE,          // 4
            self::ID_SECOND_PAGE        => self::BAD_ID_CARD_SECOND_PAGE,         // 5
            self::ID_WITH_ADDRESS       => self::BAD_ID_CARD_REGISTRATION_PAGE,   // 6
            self::MARRIAGE_CERTIFICATE  => self::LACKING_DOCUMENTS_MARRIAGE,      // 7 (new 22.12.2022)
            self::CARD_IS_BUYERSS_CERTIFICATE => self::LACKING_DOCUMENTS_BANK,    // 8 (new 22.12.2022)
        ],
        "REASONS_TYPES" => [
            self::BAD_SELFIE_WITH_PASSPORT       => self::PASSPORT_SELFIE,              // 0
            self::BAD_PASSPORT_FIRST_PAGE        => self::PASSPORT_FIRST_PAGE,          // 1
            self::BAD_PASSPORT_REGISTRATION_PAGE => self::PASSPORT_WITH_ADDRESS,        // 2
            self::BAD_SELFIE_WITH_ID_CARD        => self::ID_SELFIE,                    // 3
            self::BAD_ID_CARD_FIRST_PAGE         => self::ID_FIRST_PAGE,                // 4
            self::BAD_ID_CARD_SECOND_PAGE        => self::ID_SECOND_PAGE,               // 5
            self::BAD_ID_CARD_REGISTRATION_PAGE  => self::ID_WITH_ADDRESS,              // 6
            self::LACKING_DOCUMENTS_MARRIAGE     => self::MARRIAGE_CERTIFICATE,         // 7 (new 22.12.2022)
            self::LACKING_DOCUMENTS_BANK         => self::CARD_IS_BUYERSS_CERTIFICATE,  // 8 (new 22.12.2022)
        ],
        "ID" => [
            self::ID_SELFIE,       // 0
            self::ID_FIRST_PAGE,   // 1
            self::ID_SECOND_PAGE,  // 2
            self::ID_WITH_ADDRESS, // 3
        ],
        "BIO_PASSPORT" => [
            self::PASSPORT_SELFIE,        // 0
            self::PASSPORT_FIRST_PAGE,    // 1
            self::PASSPORT_WITH_ADDRESS,  // 2
        ],
        "LACKING_DOCUMENTS" => [
            self::MARRIAGE_CERTIFICATE,         // 0
            self::CARD_IS_BUYERSS_CERTIFICATE,  // 1
        ],
    ];

    /** В БД колонку `Files.model` пишется строка обозначающая Модель Eloquent ORM,
     *  и в нашем случае мы пишем "buyer-personal" для фото документов потверждающих личность
     */
    public const BUYER_PERSONALS_FILES_MODEL      = "buyer-personal";
}
