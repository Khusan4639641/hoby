<?php


namespace App\Services\Web\Panel\ContractController;


use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\LineSpacingRule;
use PhpOffice\PhpWord\SimpleType\TextAlignment;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Style\Section;

class GenerateWordFourthLetterService
{

    /**
     * @param array $validated
     * @param array $letter
     * @return array
     */
    public function prepare_variables(array $validated, array $letter): array {

        $selectedCourtRegion = "_____________________";
        if ( isset( $letter["court_regions"] )
            && !empty( $letter["court_regions"] )
        ) {
            $courtRegionId = $validated["selectedCourtRegionId"] - 1; // Так как массивы начинаются с 0
            $selectedCourtRegion = $letter["court_regions"][$courtRegionId]->name;
        }


        $general_company_name_uz = "_____________________";
        $general_company_address = "_____________________";
        $general_company_settlement_account = "____________";
        $general_company_mfo     = "____________";
        $general_company_inn     = "____________";
        if ( isset( $letter["buyer"]["contract"]["general_company"] )
            && !empty( $letter["buyer"]["contract"]["general_company"] )
        ) {
            $general_company = $letter["buyer"]["contract"]["general_company"];
            $general_company_name_uz = $general_company->name_uz;
            $general_company_address = $general_company->address;
            $general_company_settlement_account = $general_company->settlement_account;
            $general_company_mfo     = $general_company->mfo;
            $general_company_inn     = $general_company->inn;
        }

        // "+998 (90) 829-23-46" ожидание
        // "998908292346" реальность *hate*
        if (strlen($validated["phoneNumber"]) === 12) {
            $phone_998 = substr($validated["phoneNumber"], 0, 3); // "998"
            $phone_code = substr($validated["phoneNumber"], 3, 2); // "90"
            $phone_xxx = substr($validated["phoneNumber"], 5, 3); // "829"
            $phone_yy = substr($validated["phoneNumber"], 8, 2); // "23"
            $phone_zz = substr($validated["phoneNumber"], 10, 2); // "46"
            $phoneNumber = "+" . $phone_998 . " (" . $phone_code . ") " . $phone_xxx . "-" . $phone_yy . "-" . $phone_zz;
        } else {
            $phoneNumber = $validated["phoneNumber"];
        }

        $buyer_fio     = "_______________________________";
        if ( isset( $letter["buyer"]["fio"] )
            && !empty( $letter["buyer"]["fio"] )
        ) {
            $buyer_fio = $letter["buyer"]["fio"];
        }

        $buyer_registration_address = "__________________________";
        if ( isset( $letter["buyer"]["addresses"]["registration_address"]["address"] )
            && !empty( $letter["buyer"]["addresses"]["registration_address"]["address"] )
        ) {
            $buyer_registration_address = $letter["buyer"]["addresses"]["registration_address"]["address"];
        }

        $buyer_birthday = "_________";
        if ( isset( $letter["buyer"]["personals"]["birthday"] )
            && !empty( $letter["buyer"]["personals"]["birthday"] )
        ) {
            $buyer_birthday = $letter["buyer"]["personals"]["birthday"];
        }

        $buyer_passport_number = "_________";
        if ( isset( $letter["buyer"]["personals"]["passport_number"] )
            && !empty( $letter["buyer"]["personals"]["passport_number"] )
        ) {
            $buyer_passport_number = $letter["buyer"]["personals"]["passport_number"];
        }

        $buyer_pinfl = "_________";
        if ( isset( $letter["buyer"]["personals"]["pinfl"] )
            && !empty( $letter["buyer"]["personals"]["pinfl"] )
        ) {
            $buyer_pinfl = $letter["buyer"]["personals"]["pinfl"];
        }

        $buyer_phone = "_______________";
        if ( isset( $letter["buyer"]["phone"] )
            && !empty( $letter["buyer"]["phone"] )
        ) {
            $buyer_phone = $letter["buyer"]["phone"];
        }


        $contract_confirmed_at = "___________";
        if ( isset( $letter["buyer"]["contract"]["confirmed_at"] )
            && !empty( $letter["buyer"]["contract"]["confirmed_at"] )
        ) {
            $contract_confirmed_at = $letter["buyer"]["contract"]["confirmed_at"];
        }

        $contract_first_payment_date = "___________";
        if ( isset( $letter["buyer"]["contract"]["first_payment_date"] )
            && !empty( $letter["buyer"]["contract"]["first_payment_date"] )
        ) {
            $contract_first_payment_date = $letter["buyer"]["contract"]["first_payment_date"];
        }

        $contract_last_payment_date = "___________";
        if ( isset( $letter["buyer"]["contract"]["last_payment_date"] )
            && !empty( $letter["buyer"]["contract"]["last_payment_date"] )
        ) {
            $contract_last_payment_date = $letter["buyer"]["contract"]["last_payment_date"];
        }

        $contract_id = "_________";
        if ( isset( $letter["buyer"]["contract"]["id"] )
            && !empty( $letter["buyer"]["contract"]["id"] )
        ) {
            $contract_id = $letter["buyer"]["contract"]["id"];
        }

        $contract_total_string = 0;
        $contract_debt_per_month_string = 0;
        if ( isset( $letter["buyer"]["contract"]["total"] )
            && !empty( $letter["buyer"]["contract"]["total"] )
        ) {
            $contract_total = $letter["buyer"]["contract"]["total"];

            $contract_total_string = number_format(
                (float) $contract_total,
                0,
                ".",
                " "
            );

            if ( isset( $letter["buyer"]["contract"]["period"] )
                && !empty( $letter["buyer"]["contract"]["period"] )
            ) {
                $contract_period = $letter["buyer"]["contract"]["period"];
                $contract_debt_per_month_string = number_format(
                    ($contract_total / $contract_period),
                    0,
                    ".",
                    " "
                );
            }
        }


        $payments_sum_balance = 0;
        if ( isset( $letter["buyer"]["contract"]["payments_sum_balance"] )
            && !empty( $letter["buyer"]["contract"]["payments_sum_balance"] )
        ) {
            $payments_sum_balance = $letter["buyer"]["contract"]["payments_sum_balance"]; // current balance
        }

        $autopay = 0;
        if ( isset( $letter["buyer"]["contract"]["autopay"] )
            && !empty( $letter["buyer"]["contract"]["autopay"] )
        ) {
            $autopay = $letter["buyer"]["contract"]["autopay"];
        }

        $payments_sum_autopay = 0;
        if ( isset( $letter["buyer"]["contract"]["payments_sum_autopay"] )
            && !empty( $letter["buyer"]["contract"]["payments_sum_autopay"] )
        ) {
            $payments_sum_autopay = $letter["buyer"]["contract"]["payments_sum_autopay"];
        }

        $post_cost = 0;
        if ( isset( $letter["buyer"]["contract"]["post_cost"] )
            && !empty( $letter["buyer"]["contract"]["post_cost"] )
        ) {
            $post_cost = $letter["buyer"]["contract"]["post_cost"];
        }

        $total_max_autopay_post_cost = 0;
        if ( isset( $letter["buyer"]["contract"]["total_max_autopay_post_cost"] )
            && !empty( $letter["buyer"]["contract"]["total_max_autopay_post_cost"] )
        ) {
            $total_max_autopay_post_cost = $letter["buyer"]["contract"]["total_max_autopay_post_cost"];
        }

        $position = $validated["position"]; // "Бошқарма бошлиғининг ҳуқуқий масалалар бўйича ўринбосари"
        $fio      = $validated["fio"];      // "K.Вафаев"

        return [
            "selectedCourtRegion"                  => $selectedCourtRegion,
            "general_company_name_uz"              => $general_company_name_uz,
            "general_company_address"              => $general_company_address,
            "general_company_settlement_account"                  => $general_company_settlement_account,
            "general_company_mfo"                  => $general_company_mfo,
            "general_company_inn"                  => $general_company_inn,
            "phoneNumber"                          => $phoneNumber,
            "buyer_fio"                            => $buyer_fio,
            "buyer_registration_address"           => $buyer_registration_address,
            "buyer_birthday"                       => $buyer_birthday,
            "buyer_passport_number"                => $buyer_passport_number,
            "buyer_pinfl"                          => $buyer_pinfl,
            "buyer_phone"                          => $buyer_phone,
            "contract_confirmed_at"                => $contract_confirmed_at,
            "contract_first_payment_date"          => $contract_first_payment_date,
            "contract_last_payment_date"           => $contract_last_payment_date,
            "contract_id"                          => $contract_id,
            "contract_total_string"                => $contract_total_string,
            "contract_debt_per_month_string"       => $contract_debt_per_month_string,
            "payments_sum_balance"                 => $payments_sum_balance,
            "autopay"                              => $autopay,
            "payments_sum_autopay"                 => $payments_sum_autopay,
            "post_cost"                            => $post_cost,
            "total_max_autopay_post_cost"          => $total_max_autopay_post_cost,
            "position"                             => $position,
            "fio"                                  => $fio
        ];

    }

    /**
     * @param array $needed_variables
     * @return array
     */
    public function generateWord(array $needed_variables): array
    {

        $selectedCourtRegion                  = $needed_variables["selectedCourtRegion"];
        $general_company_name_uz              = $needed_variables["general_company_name_uz"];
        $general_company_address              = $needed_variables["general_company_address"];
        $general_company_settlement_account   = $needed_variables["general_company_settlement_account"];
        $general_company_mfo                  = $needed_variables["general_company_mfo"];
        $general_company_inn                  = $needed_variables["general_company_inn"];
        $phoneNumber                          = $needed_variables["phoneNumber"];
        $buyer_fio                            = $needed_variables["buyer_fio"];
        $buyer_registration_address           = $needed_variables["buyer_registration_address"];
        $buyer_birthday                       = $needed_variables["buyer_birthday"];
        $buyer_passport_number                = $needed_variables["buyer_passport_number"];
        $buyer_pinfl                          = $needed_variables["buyer_pinfl"];
        $buyer_phone                          = $needed_variables["buyer_phone"];
        $contract_confirmed_at                = $needed_variables["contract_confirmed_at"];
        $contract_first_payment_date          = $needed_variables["contract_first_payment_date"];
        $contract_last_payment_date           = $needed_variables["contract_last_payment_date"];
        $contract_id                          = $needed_variables["contract_id"];
        $contract_total_string                = $needed_variables["contract_total_string"];
        $contract_debt_per_month_string       = $needed_variables["contract_debt_per_month_string"];
        $payments_sum_balance                 = $needed_variables["payments_sum_balance"];
        $autopay                              = $needed_variables["autopay"];
        $payments_sum_autopay                 = $needed_variables["payments_sum_autopay"];
        $post_cost                            = $needed_variables["post_cost"];
        $total_max_autopay_post_cost          = $needed_variables["total_max_autopay_post_cost"];
        $position                             = $needed_variables["position"];
        $fio                                  = $needed_variables["fio"];


        $phpWord = new PhpWord();

        $dirname = uniqid('phpword', true);
        $dir = '/var/www' . Settings::getTempDir() . '/' . $dirname; // Linux
//        $dir = Settings::getTempDir() . '/' . $dirname;              // Windows-OpenServer
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        Settings::setTempDir($dir);

        Settings::setOutputEscapingEnabled(true);
        Settings::setDefaultPaper('A4');
        $phpWord->setDefaultFontName('Times New Roman'); // 'Arial'
//        $phpWord->setDefaultFontSize(12); // 10
        $settings = $phpWord->getSettings();
        $settings->setZoom(150);
        $settings->setHideGrammaticalErrors(true);
        $settings->setHideSpellingErrors(true);
        $settings->setDecimalSymbol('.');
        $settings->setThemeFontLang(new Language(Language::RU_RU));
        $phpWord->getSettings()->setAutoHyphenation(true);
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('resusNasiya');
        $properties->setCompany('resusNasiya');
        $properties->setTitle('resusNasiya');
        $properties->setDescription('resusNasiya');
        $properties->setCategory('resusNasiya');
        $properties->setLastModifiedBy('resusNasiya');
        $properties->setCreated(mktime(0, 0, 0, 1, 11, 2023));
        $properties->setModified(mktime(0, 0, 0, 1, 11, 2023));
        $properties->setSubject('resusNasiya');
        $properties->setKeywords("resusNasiya, взыскание долга, возмещение долга, installment plan debt collection, " .
            "installment debt collection, debt collection, penalty, debt"
        );

//         -------------------------------------------------------------------------------------------------------------

        /* Note: any element you append to a document must reside inside of a Section. */
        $header_section_marginTop = 800;
        $section_marginTop    = 0;
        $section_marginLeft   = 800;
        $section_marginRight  = 800;
        $section_marginBottom = 0;
        $footer_section_marginBottom = 800;

        $sections_commn_part = [
            'breakType'    => 'continuous',
            'orientation'  => Section::ORIENTATION_PORTRAIT,  // Orientation of section ( landscape | portrait ).
            'marginTop'    => $section_marginTop,    // Default: 1440. Page margin top in twip.
            'marginLeft'   => $section_marginLeft,   // Default: 1440. Page margin left in twip.
            'marginRight'  => $section_marginRight,  // Default: 1440. Page margin right in twip.
            'marginBottom' => $section_marginBottom, // Default: 1440. Page margin bottom in twip.
        ];
        // Adding the first Section into the document...
        $section_1 = $phpWord->addSection(array_merge(
            $sections_commn_part,
            [
                'vAlign'       => "center",                  // Left margin in inches, can be negative.
                'colsNum'      => 2,                         // A two column section. Number of columns.
                'colsSpace'    => 3560,                      // A two column section. Space between columns.
                'marginTop'    => $header_section_marginTop, // Default: 1440. Page margin top in twip.
            ]
        ));

        // Adding logo-court-letter image into the Section...
        $image_name = "logo-court-letter.png";
        if ( Storage::disk("images")->exists($image_name) ) {   // check if image exists
            $image_file_path = Storage::disk("images")->path($image_name);
            $image_style = [
                'width'              => 132,       // Width  in pt.
                'height'             => 65,        // Height in pt.
                'alignment'          => Jc::START, // See \PhpOffice\PhpWord\SimpleType\Jc class for the details.
            ];
            $section_1->addImage($image_file_path, $image_style);
        }

        // Adding the first TextRun into the first Section1 ...
        $section_1_textrun_1_paragraphStyle = [
            'alignment' => Jc::START,
            'textAlignment' => TextAlignment::BOTTOM,
            "lineHeight"  => 0.5,
            "spaceBefore" => 0,
            "spaceAfter"  => 0,
            "spacing"     => 10,
        ];
        $section_1_textrun_1 = $section_1->addTextRun($section_1_textrun_1_paragraphStyle);

        // Adding Text
        $section_1_textrun_1->addTextBreak(2);
        $section_1_textrun_1->addText("O'zbekiston Respublikasi, Toshkent shahri, 100070", ["size" => 8]); $section_1_textrun_1->addTextBreak();
        $section_1_textrun_1->addText("Yakkasaroy tumani, Shota Rustaveli ko'chasi 22-uy", ["size" => 8]); $section_1_textrun_1->addTextBreak();
        $section_1_textrun_1->addText("Tel: (998) 71-202-21-21, (998) 71-202-23-45"      , ["size" => 8]); $section_1_textrun_1->addTextBreak();
        $section_1_textrun_1->addText("Email: tv@chamber.uz, web-site: www.chamber.uz"   , ["size" => 8]);

        // Adding new Section
        $section_2 = $phpWord->addSection($sections_commn_part);
        $section_2->addText("TOSHKENT VILOYATI HUDUDIY BOSHQARMASI",
            ["bold" => true],
            [
                "alignment"   => Jc::START,
                "lineHeight"  => 0.3,
                "spaceBefore" => 0,
                "spaceAfter"  => 0,
                "spacing"     => 80,
            ]
        );

        // Adding TextRun for Bold Line
        $section_2_textrun_1 = $section_2->addTextRun([
            'alignment'       => Jc::START,
            'textAlignment'   => TextAlignment::CENTER,
            'spaceBefore'     => 0,     // twip
            'spaceAfter'      => 0,     // twip
            'lineHeight'      => 0.3,
        ]);
        // Adding Bold Line
        $section_2_width = ($section_2->getStyle()->getPageSizeW())/23;
        $section_2_textrun_1->addLine([
            'width'  => $section_2_width,
            'height' => 0,
            'weight' => 3
        ]);

        // Adding TextRun
        $section_2_textrun_2 = $section_2->addTextRun([
            'alignment'     => Jc::CENTER,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 00, // twip
            'spaceAfter'    => 0,   // twip
        ]);

        // Adding Text, it is centered cause of parent TextRun: $section_2_textrun_2
        $section_2_textrun_2->addText("CHAMBER OF COMMERCE AND INDUSTRY OF UZBEKISTAN - ТОРГОВО-ПРОМЫШЛЕННАЯ ПАЛАТА УЗБЕКИСТАНА",
            ["size" => 8]
        );
        $section_2_textrun_2->addTextBreak();
        $section_2_textrun_2->addText("ATIB \"IPOTEKA BANK\" MEHNAT FILIALI, H/R: 202120001005374491001, ИНН 201806983, МФО 00423",
            ["size" => 8]
        );

        // Adding 3 Lines
        $section_2_textrun_3 = $section_2->addTextRun([
            'alignment'       => Jc::START,
            'textAlignment'   => TextAlignment::CENTER,
            'spaceBefore'     => 0,     // twip
            'spaceAfter'      => 0,     // twip
            'lineHeight'      => 0.05,
        ]);
        $section_2_textrun_3->addLine(['width' => $section_2_width, 'height' => 0, 'weight' => 1, 'color' => 805632683]);
        $section_2_textrun_3->addTextBreak();
        $section_2_textrun_3->addLine(['width' => $section_2_width, 'height' => 0, 'weight' => 1, 'color' => 805632683]);
        $section_2_textrun_3->addTextBreak();
        $section_2_textrun_3->addLine(['width' => $section_2_width, 'height' => 0, 'weight' => 1, 'color' => 805632683]);
        $section_2_textrun_3->addTextBreak();

        // Adding Text
        $section_2_textrun_4 = $section_2->addTextRun([
            'alignment'     => Jc::START,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 0, // twip
            'spaceAfter'    => 0, // twip
        ]);
        $section_2_textrun_4->addText("№__________\"___\"____ _______y.");
        $section_2_textrun_4->addTextBreak();


        // New section
        $section_3 = $phpWord->addSection(array_merge(
            $sections_commn_part,
            [
                'vAlign'       => "center",
                'colsNum'      => 2,   // A two column section. Number of columns.
                'colsSpace'    => 300, // A two column section. Space between columns.
                'spaceAfter'   => 0,
            ]
        ));
        // Adding TextRun 1 in Section 3
        $section_3_textrun_1 = $section_3->addTextRun([
            'alignment'     => Jc::END,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 0, // twip
            'spaceAfter'    => 0, // twip
        ]);
        $section_3_textrun_1->addText("Ундирувчи:", ["bold" => true, "size" => 10] );
        $section_3_textrun_1->addTextBreak(4);
        $section_3_textrun_1->addText("Палата аъзоси манфаатида:", ["bold" => true, "size" => 10] );
        $section_3_textrun_1->addTextBreak(6);
        $section_3_textrun_1->addText("Қарздор:", ["bold" => true, "size" => 10] );
        $section_3->addTextBreak(3);

        // Adding TextRun 2 in Section 3
        $section_3_textrun_2 = $section_3->addTextRun([
            'alignment'     => Jc::START,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 0, // twip
            'spaceAfter'    => 0, // twip
        ]);
        $section_3_textrun_2->addText("ФИБ " . $selectedCourtRegion  // $variable 1
            . " туманлараро судига Ўзбекистон Савдо-саноат палатаси Тошкент вилоят худудий бошқармаси",
            ["bold" => true, "size" => 10]
        );
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText("Инд: 100070, Тошкент шаҳар, Ш.Руставелли 22-уй.",
            ["italic" => true, "size" => 10] );
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText(
            $general_company_name_uz,
            ["bold" => true, "size" => 10]
        );
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText(
            $general_company_address . " Банк: ОПЕРУ АКБ “Капиталбанк”",
            ["italic" => true, "size" => 10] );
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText(
            "Ҳ/р: {$general_company_settlement_account}, МФО: {$general_company_mfo}, СТИР: {$general_company_inn}",
            ["italic" => true, "size" => 10] );
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText("тел: " . $phoneNumber, ["italic" => true, "size" => 10] );
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText( $buyer_fio, ["bold" => true, "size" => 10]);
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText( $buyer_registration_address, ["italic" => true, "size" => 10] );
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText("Паспорт маълумоти: ", ["bold" => true, "size" => 10]);
        $section_3_textrun_2->addText( $buyer_birthday . " й.т. " . $buyer_passport_number,
            ["italic" => true, "size" => 10] );
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText("ЖШШИР: ", ["bold" => true, "size" => 10] );
        $section_3_textrun_2->addText( $buyer_pinfl, ["italic" => true, "size" => 10] );
        $section_3_textrun_2->addTextBreak();

        $section_3_textrun_2->addText("Тел: " . $buyer_phone, ["bold" => true, "size" => 10] );


        // New 4-th Section
        $section_4 = $phpWord->addSection($sections_commn_part);
        $section_4_textrun_1 = $section_4->addTextRun([
            'alignment'     => Jc::CENTER,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 150, // twip
            'spaceAfter'    => 100, // twip
            'lineHeight'    => 1,   // twip
        ]);
        $section_4_textrun_1->addText("А Р И З А", [ "bold" => true, "size" => 12 ]);
        $section_4_textrun_1->addTextBreak();
        $section_4_textrun_1->addText("(қарз ундириш тўғрисида)");


        // New 5-th Section
        $section_5 = $phpWord->addSection($sections_commn_part);
        // Adding TextRun 1 in Section 5
        $section_5_textrun_1 = $section_5->addTextRun([
            'alignment'     => Jc::BOTH,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 0,   // twip
            'spaceAfter'    => 0,   // twip
            'indentation'   => [
                "left"      => 0, // TWIP
                "right"     => 0,   // TWIP
                "firstLine" => 150, // TWIP
            ]
        ]);
        $section_5_textrun_1->addText(
            $general_company_name_uz
            . " томонидан товарларни муддатли тўлов асосида тақдим этишнинг оммавий офертаси ва умумий шартлари "
            . "(кейинги ўринларда -Оферта) ўзининг веб-сайтига жойлаштирилган. "
            . "Ушбу оммавий шартнома бўйича аризачининг оммавий офертасини акцептлаш мақсадида, "
            . "аризачи ҳамда " . $buyer_fio . " (кейинги ўринларда – Қарздор) ўртасида " . $contract_confirmed_at
            . "-йилда " . $contract_id . "-сонли оммавий оферта шартларини қабул қилиш тўғрисидаги акцепт (кейинги "
            . "ўринларда - Шартнома) имзоланган. " . $contract_confirmed_at . " йил кунги " . $contract_id . "-сонли "
            . "шартноманинг тўлов графигига асосан қарздор шартнома бўйича " . $contract_first_payment_date
            . " йил кунидан " . $contract_last_payment_date . " йил кунига қадар ҳар ой "
            . $contract_debt_per_month_string . " сўмдан, жами " . $contract_total_string
            . " сўм миқдоридаги тўлов суммасини тўлаш мажбуриятини олган бўлиб, бугунги кунда ушбу мажбурият "
            . "лозим даражада бажарилмай келинмоқда."
        );

        $section_5_textrun_2 = $section_5->addTextRun([
            'alignment'     => Jc::BOTH,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 0,   // twip
            'spaceAfter'    => 0,   // twip
            'indentation'   => [
                "left"      => 0, // TWIP
                "right"     => 0,   // TWIP
                "firstLine" => 150, // TWIP
            ]
        ]);
        $section_5_textrun_2->addText(
            "Жамият томонидан мазкур шартнома асосида ўз мажбурияти бажарилган. Бироқ Қарздор "
            . "шартнома асосида сотиб олинган маҳсулот учун тўловларни белгиланган муддатларда "
            . "тўламаслиги оқибатида " . $payments_sum_balance . " сўм қарздорлик ва "
            . $autopay . " сўм ундирув харажатлари буйича қарздорлик юзага келган. "
            . "Шу муносабат билан ҳозирги кунда қарздорнинг шартнома бўйича жами қарздорлиги "
            . $payments_sum_autopay . " сўмни ташкил қилади."
        );

        $section_5_textrun_3 = $section_5->addTextRun([
            'alignment'     => Jc::BOTH,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 0,   // twip
            'spaceAfter'    => 0,   // twip
            'indentation'   => [
                "left"      => 0, // TWIP
                "right"     => 0,   // TWIP
                "firstLine" => 150, // TWIP
            ]
        ]);
        $section_5_textrun_3->addText(
            "Оммавий офертанинг низоларни ҳал қилиш тартиби бўлимига асосан ушбу шартнома юзасидан келиб чиққан"
            . "низо ҳал қилиш учун ");
        $section_5_textrun_3->addText("фуқаролик ишлари бўйича Тошкент шаҳар " . $selectedCourtRegion . " ",
            [ "bold" => true ]);
        $section_5_textrun_3->addText("топширилиши белгилаб қўйилган.");

        $section_5_textrun_4 = $section_5->addTextRun([
            'alignment'     => Jc::BOTH,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 0,   // twip
            'spaceAfter'    => 0,   // twip
            'indentation'   => [
                "left"      => 0, // TWIP
                "right"     => 0,   // TWIP
                "firstLine" => 150, // TWIP
            ]
        ]);
        $section_5_textrun_4->addText("Ўзбекистон Республикаси Савдо-саноат палатаси тўғрисида”ги Қонуннинг 21-моддаси ҳамда Ўзбекистон
Республикаси “Давлат божи тўғрисида”ги Қонунининг 9-моддасининг 2-бандини қўллаб, Суддан:");

        // Adding centered Text in Section 5 between TextRun 1 and TextRun 2
        $section_5->addText("С Ў Р А Й Д И:",
            [ "bold" => true, "size" => 12 ],
            [
                "alignment"     => Jc::CENTER,
                "textAlignment" => TextAlignment::CENTER,
                "lineHeight"  => 1,
                'spaceBefore' => 150, // twip
                'spaceAfter'  => 80, // twip
            ]
        );

        // Adding TextRun 2 in Section 5
        $section_5_textrun_2 = $section_5->addTextRun([
            'alignment'     => Jc::START,
            'textAlignment' => TextAlignment::CENTER,
            'spaceBefore'   => 0, // twip
            'spaceAfter'    => 0, // twip
        ]);
        $section_5_textrun_2->addText("- Аризани давлат божисиз иш юритишга қабул қилишни;");
        $section_5_textrun_2->addTextBreak();
        $section_5_textrun_2->addText(
            "- " . $general_company_name_uz . " фойдасига жавобгар " . $buyer_fio . "дан "
            . $payments_sum_autopay . " сўм асосий қарз ва "
            . $post_cost . " сўм почта харажатини, жами "
            . $total_max_autopay_post_cost . " сўм ундиришни;"
        ); $section_5_textrun_2->addTextBreak();

        $section_5_textrun_2->addText("- Давлат божини Қарздор зиммасига юклашни.");
        $section_5_textrun_2->addTextBreak();


        // New 6-th Section
        $section_6 = $phpWord->addSection($sections_commn_part);

        // Adding TextRun 1 in Section 6
        $section_6_textrun_1 = $section_6->addTextRun([
            'alignment'       => Jc::START,
            'textAlignment'   => TextAlignment::CENTER,
            'spaceBefore'     => 0, // TWIP
            'spaceAfter'      => 0, // TWIP
            "lineHeight"      => 1,
            "indentation"     => [
                "left"       => 500, // TWIP
                "right"      => 0,   // TWIP
            ],
            "spacingLineRule" => LineSpacingRule::AT_LEAST,
        ]);

        $section_6_textrun_1->addText("1. Ундирувчининг ЎзР ССПга аъзолик шартномаси ва гувоҳномаси нусхаси;");
        $section_6_textrun_1->addTextBreak();
        $section_6_textrun_1->addText("2. Шартнома нусхаси;");
        $section_6_textrun_1->addTextBreak();
        $section_6_textrun_1->addText("3. Паспорт нусхаси;");
        $section_6_textrun_1->addTextBreak();
        $section_6_textrun_1->addText("4. Ишончнома нусхаси;");
        $section_6_textrun_1->addTextBreak();
        $section_6_textrun_1->addText("5. Почта харажатлари тўлови амалга оширилганлигини тасдиқловчи хужжат.");
        $section_6_textrun_1->addTextBreak();


        // New 7-nth Section
        $section_7 = $phpWord->addSection(array_merge(
            $sections_commn_part,
            [
                'vAlign'       => "top", // Vertical align.
                'colsNum'      => 2,     // A two column section. Number of columns.
                'colsSpace'    => 3560,   // A two column section. Space between columns.
            ]
        ));

        // Adding TextRun 1 in Section 7
        $section_7_textrun_1 = $section_7->addTextRun([
            'alignment'       => Jc::START,
            'textAlignment'   => TextAlignment::TOP,
            'spaceBefore'     => 0, // TWIP
            'spaceAfter'      => 0, // TWIP
            "lineHeight"      => 1,
            "spacingLineRule" => LineSpacingRule::AT_LEAST,
        ]);
        $section_7_textrun_1->addText($position, ["bold" => true]);

        // Adding TextRun 2 in Section 7
        $section_7_textrun_2 = $section_7->addTextRun([
            'alignment'       => Jc::END,
            'textAlignment'   => TextAlignment::TOP,
            'spaceBefore'     => 0, // TWIP
            'spaceAfter'      => 0, // TWIP
            "lineHeight"      => 1,
            "spacingLineRule" => LineSpacingRule::AT_LEAST,
        ]);
        $section_7_textrun_2->addText( $fio, ["bold" => true] );

        // New empty last section
        $section_8 = $phpWord->addSection(array_merge(
            $sections_commn_part,
            [
                'marginBottom' => $footer_section_marginBottom, // Default: 1440. Page margin bottom in twip.
            ]
        ));
        $section_8_textrun_1 = $section_8->addTextRun([
            'alignment'       => Jc::START,
            'textAlignment'   => TextAlignment::TOP,
            'spaceBefore'     => 0, // TWIP
            'spaceAfter'      => 0, // TWIP
            "lineHeight"      => 1,
            "spacingLineRule" => LineSpacingRule::AT_LEAST,
        ]);
        $section_8_textrun_1->addTextBreak();
        $section_8_textrun_1->addText("Ижрочи: Ш. Юлдошов", ["size" => 8]);
        $section_8_textrun_1->addTextBreak();
        $section_8_textrun_1->addText("Тел: +998 (95) 202-16-16", ["size" => 8]);


        return [$phpWord, $dir];
    }
}
