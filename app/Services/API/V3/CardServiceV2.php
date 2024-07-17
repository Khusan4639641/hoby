<?php

namespace App\Services\API\V3;

use App\Classes\CURL\test\CardScoringRequest;
use App\Exceptions\KeycloakAuthenticationException;
use App\Helpers\EncryptHelper;
use App\Services\testCardService;
use App\Models\{Buyer, Card, CardScoring, CardScoringLog, User};
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CardServiceV2 extends BaseService
{
    private $cardServiceProdEnvEnabled;

    public function __construct()
    {
        $this->cardServiceProdEnvEnabled = config('test.card_service_prod_env_enabled');
    }

    public function add($request)
    {
        $pan = $this->sanitisePan($request->pan);
        $expiry = $this->sanitiseExpiry($request->expiry);

        $buyer = Buyer::find($request->buyer_id ?: Auth::id());
        $phone = $buyer->getPhoneWithOutPlusAttribute();

        try {
            Log::channel('cards_v2')->info('Add main card:', [
                'Pan' => $pan,
                'Buyer ID' => $buyer->id,
                'Buyer phone' => $phone,
            ]);
            $response = (new testCardService())->add($pan, $expiry, $phone);
        } catch (KeycloakAuthenticationException | \Exception $e) {
            Log::channel('cards_v2')->info("Exception (Pan: $pan): ".$e->getMessage());
            return self::errorJson([__('test_card_service/card.an_error_occurred_please_try_again')]);
        }

        return self::handleServiceResponse($response);
    }

    public function confirm($request, $userStatus = 12)
    {
        $pan = $this->sanitisePan($request->pan);
        $code = $request->code;

        $buyerId = $request->buyer_id ?: Auth::id();

        try {
            /* Make an attempt to confirm the card */
            Log::channel('cards_v2')->info('Confirm main card:', [
                'Pan' => $pan,
                'Buyer ID' => $buyerId,
            ]);
            $cardConfirmResponse = (new testCardService())->confirm($pan, $code);
            if ($cardConfirmResponse['status'] == 'error') return self::handleServiceResponse($cardConfirmResponse);
            $cardToken = $cardConfirmResponse['token'];
        } catch (KeycloakAuthenticationException | \Exception $e) {
            Log::channel('cards_v2')->info("Exception (Pan: $pan): ".$e->getMessage());
            return self::errorJson([__('test_card_service/card.an_error_occurred_please_try_again')]);
        }

        try {
            /* Check card balance and delete it if check doesn't pass */
            Log::channel('cards_v2')->info('Get card balance:', [
                'Pan' => $pan,
                'Buyer ID' => $buyerId,
                'Card token' => $cardToken,
            ]);
            $cardBalanceResponse = (new testCardService())->getCardBalance($cardToken);
            if ($cardBalanceResponse['status'] == 'error') {
                Log::channel('cards_v2')->info("Failed to retrieve card balance (Pan: $pan)", $cardBalanceResponse);
                $this->deleteCardByTokenAndLogIfFailed($cardToken);
                return self::handleServiceResponse($cardBalanceResponse);
            }

            if ($cardBalanceResponse['balance'] <= 100 * 100) {
                $this->deleteCardByTokenAndLogIfFailed($cardToken);
                return self::errorJson([__('test_card_service/card.insufficient_funds_on_card')]);
            }

            /* Check card write-off possibility and delete it if check doesn't pass */
            Log::channel('cards_v2')->info('Write-off check:', [
                'Pan' => $pan,
                'Buyer ID' => $buyerId,
                'Card token' => $cardToken,
            ]);
            $writeOffResponse = (new testCardService())->writeOffCheck($cardToken, $buyerId);
            if ($writeOffResponse['status'] == 'error') {
                Log::channel('cards_v2')->info("Failed to write-off from card (Pan: $pan)", $writeOffResponse);
                $this->deleteCardByTokenAndLogIfFailed($cardToken);
                return self::errorJson([__('test_card_service/card.failed_to_write_off_from_card')]);
            }

            /* Card Scoring */
            if ($this->cardServiceProdEnvEnabled) {
                Log::channel('cards_v2')->info('Card scoring:', [
                    'Pan' => $pan,
                    'Buyer ID' => $buyerId,
                    'Card token' => $cardToken,
                ]);
                $cardScoringRequest = (new CardScoringRequest($cardToken))->execute();
                if ($cardScoringRequest->isSuccessful()) {
                    $cardScoringResponse = $cardScoringRequest->response();
                    $lastMonthsIncomeIsValid = $cardScoringResponse->checkLastMonthsIncome();
                    Log::channel('cards_v2')->info("Card scoring (Pan: $pan):", $cardScoringResponse->json());
                    $this->saveCardScoringResult($cardScoringRequest, $buyerId, $pan, $lastMonthsIncomeIsValid);
                    if (!$lastMonthsIncomeIsValid) {
                        Log::channel('cards_v2')->info("Card scoring (Pan: $pan) failed");
                        $this->deleteCardByTokenAndLogIfFailed($cardToken);
                        return self::errorJson([__('test_card_service/card.monthly_receipts_not_sufficient')]);
                    }
                    Log::channel('cards_v2')->info("Card scoring (Pan: $pan) is successful");
                } else {
                    Log::channel('cards_v2')->info("Card scoring (Pan: $pan) failed (Bad response)");
                    $this->deleteCardByTokenAndLogIfFailed($cardToken);
                    return self::errorJson([__('test_card_service/card.failed_to_score_the_card')]);
                }
            }

            /* Get card info and store it in DB */
            Log::channel('cards_v2')->info('Get card info:', [
                'Pan' => $pan,
                'Buyer ID' => $buyerId,
                'Card token' => $cardToken,
            ]);
            $cardInfoResponse = (new testCardService())->getCardInfo($cardToken);
            if ($cardInfoResponse['status'] == 'error') {
                Log::channel('cards_v2')->info("Failed to retrieve card info (Pan: $pan)", $cardInfoResponse);
                $this->deleteCardByTokenAndLogIfFailed($cardToken);
                return self::handleServiceResponse($cardInfoResponse);
            }
            $this->saveCard($cardInfoResponse['card'], $pan, $cardToken, $buyerId, $code, true);

            /* Change user status on success if required */
            if ($userStatus) {
                $user = User::find($buyerId);
                User::changeStatus($user, $userStatus);
            }

        } catch (KeycloakAuthenticationException | \Exception $e) {
            $this->deleteCardByTokenAndLogIfFailed($cardToken);
            Log::channel('cards_v2')->info("Exception (Pan: $pan): ".$e->getMessage());
            return self::errorJson([__('test_card_service/card.an_error_occurred_please_try_again')]);
        }

        return self::successJson(__('test_card_service/card.card_has_been_added'));
    }

    public function addSecondary($request)
    {
        $pan = $this->sanitisePan($request->pan);
        $expiry = $this->sanitiseExpiry($request->expiry);

        $buyer = Buyer::find($request->buyer_id ?: Auth::id());
        $phone = $buyer->getPhoneWithOutPlusAttribute();

        Log::channel('cards_v2')->info('Add secondary card:', [
            'Pan' => $pan,
            'Buyer ID' => $buyer->id,
            'Buyer phone' => $phone,
        ]);

        try {
            $response = (new testCardService())->add($pan, $expiry, $phone);
        } catch (KeycloakAuthenticationException | \Exception $e) {
            Log::channel('cards_v2')->info("Exception (Pan: $pan): ".$e->getMessage());
            return self::errorJson([__('test_card_service/card.an_error_occurred_please_try_again')]);
        }

        return self::handleServiceResponse($response);
    }

    public function confirmSecondary($request)
    {
        $pan = $this->sanitisePan($request->pan);
        $code = $request->code;

        $buyerId = $request->buyer_id ?: Auth::id();

        try {
            /* Make an attempt to confirm the secondary card */
            Log::channel('cards_v2')->info('Confirm secondary card:', [
                'Pan' => $pan,
                'Buyer ID' => $buyerId,
            ]);
            $cardConfirmResponse = (new testCardService())->confirm($pan, $code);
            if ($cardConfirmResponse['status'] == 'error') return self::handleServiceResponse($cardConfirmResponse);
            $cardToken = $cardConfirmResponse['token'];
        } catch (KeycloakAuthenticationException | \Exception $e) {
            Log::channel('cards_v2')->info("Exception (Pan: $pan): ".$e->getMessage());
            return self::errorJson([__('test_card_service/card.an_error_occurred_please_try_again')]);
        }

        try {
            /* Get card info and store it in DB */
            Log::channel('cards_v2')->info('Get secondary card info:', [
                'Pan' => $pan,
                'Buyer ID' => $buyerId,
                'Card token' => $cardToken,
            ]);
            $cardInfoResponse = (new testCardService())->getCardInfo($cardToken);
            if ($cardInfoResponse['status'] == 'error') {
                Log::channel('cards_v2')->info("Failed to retrieve card info (Pan: $pan)", $cardInfoResponse);
                $this->deleteCardByTokenAndLogIfFailed($cardToken);
                return self::handleServiceResponse($cardInfoResponse);
            }
            $this->saveCard($cardInfoResponse['card'], $pan, $cardToken, $buyerId, $code);

        } catch (KeycloakAuthenticationException | \Exception $e) {
            $this->deleteCardByTokenAndLogIfFailed($cardToken);
            Log::channel('cards_v2')->info("Exception (Pan: $pan): ".$e->getMessage());
            return self::errorJson([__('test_card_service/card.an_error_occurred_please_try_again')]);
        }

        return self::successJson(__('test_card_service/card.card_has_been_added'));
    }

    private function sanitisePan($pan): string
    {
        return str_replace(' ', '', $pan);
    }

    private function sanitiseExpiry($expiry): string
    {
        $expiry = str_replace('/', '', $expiry);

        return Str::substr($expiry, 2, 2) . Str::substr($expiry, 0, 2);
    }

    private function deleteCardByTokenAndLogIfFailed(string $cardToken): void
    {
        try {
            $cardDeleteResponse = (new testCardService())->delete($cardToken);
            if ($cardDeleteResponse['status'] == 'error') {
                Log::channel('cards_v2_failed_delete')->info("Couldn't delete card by token", [
                    "Token" => $cardToken,
                    "Message" => $cardDeleteResponse['message'] ?? '',
                ]);
            }
        } catch (KeycloakAuthenticationException | \Exception $e) {

            /* TODO: In case of a systematic problem with deleting cards by token, automatize this process */

            Log::channel('cards_v2_failed_delete')->info("Couldn't delete card by token", [
                "Token" => $cardToken,
                "Message" => $e->getMessage()
            ]);
        }
    }

    private function saveCardScoringResult($cardScoringRequest, $buyerId, $pan, $monthlyReceiptsSumIsValid): void
    {
        $cardScoring = new CardScoring();
        $cardScoring->user_id = $buyerId;
        $cardScoring->user_card_id = 0;
        $cardScoring->period_start = Carbon::now()->subMonths(config('test.scoring_max_month'))->format('Y-m-01');
        $cardScoring->period_end = Carbon::now()->format('Y-m-d');
        $cardScoring->status = 1;
        $cardScoring->save();

        $cardScoringLog = new CardScoringLog();
        $cardScoringLog->user_id = $buyerId;
        $cardScoringLog->card_scoring_id = $cardScoring->id;
        $cardScoringLog->card_hash = md5($pan);
        $cardScoringLog->status = (int)$monthlyReceiptsSumIsValid;
        $cardScoringLog->scoring = 0;
        $cardScoringLog->ball = 0;
        $cardScoringLog->scoring_count = 1;
        $cardScoringLog->request = $cardScoringRequest->requestText();
        $cardScoringLog->response = $cardScoringRequest->response()->text();
        $cardScoringLog->response_type = CardScoringLog::RESPONSE_test;
        $cardScoringLog->save();
    }

    private function saveCard(array $testCard, string $pan, string $cardToken, int $buyerId, string $code, bool $isMain = false): object
    {
        return Card::create([
            'user_id' => $buyerId,
            'card_name' => $testCard['holderFullName'],
            'card_number' => EncryptHelper::encryptData($pan),
            'card_valid_date' => EncryptHelper::encryptData($testCard['expiry']),
            'phone' => $testCard['phoneNumber'],
            'sms_code' => $code,
            'token' => $cardToken,
            'token_payment' => $cardToken,
            'type' => EncryptHelper::encryptData($testCard['processingType']),
            'processing_type' => $testCard['processingType'],
            'guid' => md5($pan),
            'status' => Card::CARD_ACTIVE,
            'hidden' => 1,
            'is_main' => $isMain ? 1 : 0,
            'is_processing_active' => 1,
            'bean' => $testCard['bean'],
        ]);
    }

    public static function handleServiceResponse($response): JsonResponse
    {
        return $response['status'] == 'success' ? self::successJson() : self::errorJson([$response['message']]);
    }
}
