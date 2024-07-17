<?php

namespace App\Http\Controllers\Core;

// Requests
use App\Helpers\FileHelper;
use App\Http\Requests\Core\LetterController\LetterFillingDataRequest;
use App\Http\Resources\Core\LetterController\LetterFillingDataResource;

// Helpers
use App\Helpers\LetterHelper;

// Models
use App\Models\Contract;
use App\Models\CourtRegion;
use App\Models\Letter as Model;
use App\Models\NotarySetting;
use App\Models\PostalArea;
use App\Models\PostalRegion;

// Laravel Helpers & Facades
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;


class LetterController extends CoreController
{
    private $config;

    /**
     * Fields validator
     *
     * @param array $data
     * @return Validator
     */
    private $validatorRules = [
        'contract_id' => ['required', 'integer'],
        //'address' => ['required', 'string', 'max:255'],
        'postal_region' => ['required', 'integer'],
        'postal_area' => ['required', 'integer'],
        'letter_type' => ['required', 'string',
            'in:' . Model::LETTER_TYPE_RESIDENCY . ',' . Model::LETTER_TYPE_RESIDENCY_2
        ]
    ];

    /**
     * PostalRegionController constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Config
        $this->config =  Config::get('test.preview');
    }

    /**
     * Send letter
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function send(Request $request) {

        $user = Auth::user();

        //if ($user->can('add', Model::class)) {
        if (true) {

            $validator = $this->validator($request->all(), $this->validatorRules);

            if ($validator->fails()) {

                // Error: validation error
                $this->result['status'] = 'error';
                $this->result['response']['errors'] = $validator->errors();

            } else {
                $contract = Contract::with([
                    'buyer.addressRegistration',
                    'debts',
                    'generalCompany',
                    'recover'
                ])->find($request->contract_id);

                if (!$contract || !$buyer = $contract->buyer) {

                    $this->result['status'] = 'error';
                    $this->message( 'danger', __('letters.err_contract_or_buyer_not_found'));

                    return $this->result();
                }

                // Update buyer registration address with postal region and area
                $buyer->addressResidential()->updateOrCreate(['user_id' => $buyer->id], [
                    'postal_region' => $request->postal_region,
                    'postal_area' => $request->postal_area,
                ]);

                $buyerFIO = $buyer->fio;
                $buyer_address = $buyer->addressRegistration->address;

                $help_phone = callCenterNumber(3);

                $debts_amount = 0;
                if ( $activePayments = $contract->activePayments ) {
                    $today = Carbon::today()->endOfDay();
                    foreach ( $activePayments as $schedule) {
                        $payment_date = Carbon::parse($schedule->payment_date);
                        $diffInDays = $payment_date->diffInDays($today, false);

                        if ( $diffInDays > 0 ) {
                            $debts_amount += (float) $schedule->balance;
                        }
                    }
                }
                $contract->debts_amount = $debts_amount;

                $letter_to_residency = $contract->letters()
                    ->whereType(Model::LETTER_TYPE_RESIDENCY)
                    ->whereNotNull('amounts')
                    ->orderByDesc("letters.id")
                    ->orderByDesc("letters.created_at")
                    ->first()
                ;
                if ( $letter_to_residency ) {
                    $amounts = $letter_to_residency->amounts;
                    if ( !empty($amounts) ) {
//                        $amounts['total_max_amount']   = $amounts['total_max_amount'] ?? 0;
                        $amounts['total_max_autopay_post_cost'] = $amounts['total_max_autopay_post_cost'] ?? 0;
                        $amounts['total_max_percent_fix_max']   = $amounts['total_max_percent_fix_max'] ?? 0;
                    }
                }
                else {
                    $amounts['payments_sum_balance'] = (float) $contract->activePayments->sum('balance');
//                    if ($this->contract->generalCompany->is_tpp) {    // Путь суда
                        $amounts['autopay']   = ($amounts['payments_sum_balance'] * 100)/97 - $amounts['payments_sum_balance']; // это равно: ($payments_sum_balance / 0,97) - $payments_sum_balance
                        $amounts['post_cost'] = (float) NotarySetting::where("template_number", "fourth")->first()->fee; // Нам главное нужно чтобы тут было 15 000 сум (константа):
                        $amounts['total_max_autopay_post_cost'] = ($amounts['payments_sum_balance'] + $amounts['autopay'] + $amounts['post_cost']);
//                        $amounts['total_max_amount'] = ($amounts['payments_sum_balance'] + $amounts['autopay'] + $amounts['post_cost']);
//                    }
//                    else {                                            // Путь нотариуса
                        $amounts['percent'] = (float) ($amounts['payments_sum_balance'] / 100);
                        $amounts['fix_max'] = (float) NotarySetting::max('fee');
                        $amounts['total_max_percent_fix_max'] = ($amounts['payments_sum_balance'] + $amounts['percent'] + $amounts['fix_max']);
//                        $amounts['total_max_amount'] = ($amounts['payments_sum_balance'] + $amounts['percent'] + $amounts['fix_max']);
//                    }
                }

                $contract->expired_days_uz = $contract->expired_days;
                $contract->expired_days_ru = self::getDaysText($contract->expired_days);

                $contract->real_expired_days_minus_one_uz = 0;
                $contract->real_expired_days_minus_one_ru = 0;
                if ( $contract->expired_at ) {
                    $contract->real_expired_days_minus_one = (Carbon::parse($contract->expired_at)->diffInDays() - 1);
                    $contract->real_expired_days_minus_one_ru = self::getDaysText($contract->real_expired_days_minus_one);
                }

                if ( $request->letter_type === Model::LETTER_TYPE_RESIDENCY ) {
                    $pdf = \PDF::loadView(
                        'panel.contract.letters.pretension_pdf',
                        compact('contract', 'help_phone', 'amounts')
                    );
                } elseif ( $request->letter_type === Model::LETTER_TYPE_RESIDENCY_2 ) {
                    $pdf = \PDF::loadView(
                        'panel.contract.letters.pretension_pdf_2',
                        compact('contract', 'help_phone', 'amounts')
                    );
                }

                $pdf->setOptions([
                    'tempDir' => storage_path('logs/dompdf/'),
                    'fontDir' => storage_path('fonts/'),
                    'fontCache' => storage_path('fonts/'),
                    'logOutputFile' => storage_path('logs/log.htm'),
                    'isFontSubsettingEnabled' => true,
                    'isRemoteEnabled' => true
                ]);


                $pdf_stream = base64_encode($pdf->output());

                $letter_response = LetterHelper::createLetter(
                    $buyerFIO,
                    $buyer_address,
                    $request->postal_region,
                    $request->postal_area,
                    $pdf_stream
                );

                if ($letter_response['status'] === 'success') {

                    // Create letter
                    $letter = new Model();
                    $letter->contract_id = $request->contract_id;
                    $letter->buyer_id = $buyer->id;
                    $letter->receiver = $buyerFIO;
                    $letter->address = $buyer_address;
                    $letter->region = $request->postal_region;
                    $letter->area = $request->postal_area;
                    $letter->response = $letter_response['data'];

                    $letter->sender_id = Auth::id();
                    $letter->type = $request->letter_type;

                    // Cохраняем JSON поле `letters`.`amount` только при отправке Письма Домой 2 через Гибрид Почту
                    if ( $request->letter_type === Model::LETTER_TYPE_RESIDENCY ) {
                        $letter->amounts = $amounts;
                    }

                    $letter->save();

                    $pdf_file_contents = $pdf->output();

                    $file_data = [
                        "type"          => $request->letter_type,  // string
                        "model"         => "letter",               // string
                        "element_id"    => $letter->id,            // int
                        "user_id"       => $letter->sender_id,     // int
                        "extension"     => "pdf"
                    ];

                    $file_path = FileHelper::saveLetterFile($pdf_file_contents, $file_data);

                    if ( !$file_path ) {
                        Log::channel("letters")->error('Error. Letter PDF file not saved.');
                        $this->result['response']['code'] = 500;
                        $this->message( 'danger', __( 'app.err_save_letter' ) );
                        return $this->result();
                    }
                    $letter->file_link = FileHelper::url($file_path);
                    $letter->save();

                    // Success: Letter sent
                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/letters.letter_sent'));

                } else {
                    Log::channel("letters")
                        ->error('Error. ' . __('panel/letters.err_letter_not_sent') . '. Letter PDF file not sent.');

                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 404;
                    $this->message( 'danger', __('panel/letters.err_letter_not_sent'));
                    $this->message( 'danger', $letter_response['info']);
                }
            }

        } else {

            // Error: Access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __('app.err_access_denied'));
        }

        return $this->result();
    }

    public function letterFillingData(LetterFillingDataRequest $request)
    {
        $this->contract = Contract::with([
            "autopay_history" => function ($query) {
                $query->where('status', 0)->latest('created_at')->first();
            },
            "activePayments",
            "buyer.addressRegistration",
            "buyer.addressResidential",
            "buyer.addressWorkplace",
            "buyer.personals.passport_first_page",
//            "buyer.personals.passport_second_page", // Такой связи не существует
            "buyer.personals.passport_with_address",
            "debts",
            "generalCompany",
            "letters",
            "recover",
            "schedule",
        ])->find($request->contract_id);

        $this->sftpFileServerDomain = Config::get('test.sftp_file_server_domain') . 'storage/';
        $this->contentLanguage = $request->header('content-language');
        $this->notary_setting = $this->contract->collcost->notary ?? NotarySetting::find($request->notary_id);
        $this->court_regions = CourtRegion::select("id", "name")->where("is_visible", 1)->getOrdered();

        return new LetterFillingDataResource($this);
    }

    public function postalRegionsAndAreas()
    {
        $result['postal_regions'] = PostalRegion::all();
        $result['postal_areas'] = PostalArea::all();

        return $result;
    }

    public static function getDaysText($days) {
        if ($days === 0)                                      { return "0 дней"; }
        if ( ( ($days % 10) === 1) )                          { return $days . " день"; }
        if ( in_array( ($days % 10), [2, 3, 4], true ) ) { return $days . " дня"; }
        return $days . " дней";
    }

}
