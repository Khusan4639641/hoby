<?php

namespace App\Http\Controllers\Core;

use App\Classes\Exceptions\MLException;
use App\Facades\BuyerLimit;
use App\Facades\GradeScoring;
use App\Helpers\EncryptHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scoring\BaseLimitRequest;
use App\Http\Requests\Scoring\ExtendedLimitRequest;
use App\Http\Requests\Scoring\MiniLimitRequest;
use App\Logging\ByUser\LoggerByUser;
use App\Models\Buyer;
use App\Models\MyIDJob;
use App\Models\ScoringResult;
use App\Models\ScoringResultMini;
use App\Services\Scoring\ScoringErrorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Exceptions\InvalidFormatException;

class MLController extends Controller
{

    public function miniLimit(int $buyerID, MiniLimitRequest $request)
    {
        Log::channel('scoring_steps_logs')->info("Запрос от ML на mini");

        $data = $request->get('data');
        $error = $request->get('error');
        Log::channel('scoring_steps_logs')->info("Принятые параметры: ", $data);

        $scoring = ScoringResultMini::find($data['scoring_request_id']);

        if ($scoring && $error != null){
            $scoring->total_state = ScoringResult::STATE_FAILED_RESPONSE;
            $scoring->save();
            Log::channel('scoring_steps_logs')->info("Пришла ошибка от ML в массиве error; Total state выставлен в статус 4");
        }

        /*$scoring->total_state = ScoringResult::STATE_FAILED_RESPONSE;
        $scoring->save();*/

        try {
            $buyer = Buyer::findOrFail($buyerID);

            $logger = new LoggerByUser($buyer, 'scoring', 'mini');

            $logger->info("Параметры запроса", $data);

            GradeScoring::setLogger($logger);

            if(!$buyer->personals){
                return response()
                    ->json(ScoringErrorService::catchingException($scoring, $logger, new HttpException(404, "Не найдено персональных данных пользователя"), $request), 201);
            }

            GradeScoring::saveUserDataByScoringV2(
                $scoring,
                $data['claim_id']
            );

            BuyerLimit::setMiniLimit($buyer);

            $scoring->finalSuccess();
        } catch (MLException $e) {
            $scoring->checkApproveFailed();
            return response()
                ->json(ScoringErrorService::catchingMLBaseException($scoring, $logger, $e), 201);
        } catch (\Throwable $e) {
            $scoring->checkApproveFailed();
            return response()
                ->json(ScoringErrorService::catchingException($scoring, $logger, $e, $request), 201);
        }
        return response()->json([], 201);
    }

    public function baseLimit(int $buyerID, MiniLimitRequest $request)
    {
        Log::channel('scoring_steps_logs')->info("Запрос от ML на base");

        $data = $request->get('data');
        $error = $request->get('error');
        Log::channel('scoring_steps_logs')->info("Принятые параметры: ", $data);
        $scoring = ScoringResultMini::find($data['scoring_request_id']);

        if ($scoring && $error != null){
            $scoring->total_state = ScoringResult::STATE_FAILED_RESPONSE;
            $scoring->save();
            Log::channel('scoring_steps_logs')->info("Пришла ошибка от ML в массиве error; Total state выставлен в статус 4");
        }

        /*$scoring->total_state = ScoringResult::STATE_FAILED_RESPONSE;
        $scoring->save();*/

        try {

            $buyer = Buyer::findOrFail($buyerID);

            $logger = new LoggerByUser($buyer, 'scoring', 'full');

            $logger->info("Параметры запроса", $data);

            GradeScoring::setLogger($logger);

            if(!$buyer->personals){
                return response()
                    ->json(ScoringErrorService::catchingException($scoring, $logger, new HttpException(404, "Не найдено персональных данных пользователя"), $request), 201);
            }


            GradeScoring::saveUserDataByScoringV2(
                $scoring,
                $data['claim_id']
            );


            //$scoring_type_2 = ScoringResult::where('user_id', $buyer->id)->where('type', ScoringResult::TYPE_MINI)->where('total_state', 1)->get();
            if($data['approved'] && $buyer->settings->mini_limit == 0){
                Log::channel('scoring_steps_logs')->info("Message: Выставлен мини_лимит пользователю;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                BuyerLimit::setMiniLimit($buyer);
            }



            if($data['approved']){
                Log::channel('scoring_steps_logs')->info("Message: Запрос на ML extended пользователю;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                $scoringResult = $buyer->scoringResult->last();

                if(!$scoringResult){
                    $scoringResult = new ScoringResult();
                    $scoringResult->initiator_id = Auth::id();
                    $scoringResult->is_katm_auto = true;
                    $scoringResult->buyer()->associate($buyer);
                    $scoringResult->save();
                }

                GradeScoring::requestToMLByExtendedLimit($scoringResult);
            }

            //$scoring->checkApproveSuccess();
            $scoring->finalSuccess();
            $scoring->error_message = null;
            $scoring->error_code = null;
            $scoring->save();
        } catch (MLException $e) {
            $scoring->checkApproveFailed();
            Log::channel('scoring_steps_logs')->info("Ошибка: {$e->getMessage()};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            return response()
                ->json(ScoringErrorService::catchingMLBaseException($scoring, $logger, $e), 201);
        } catch (\Throwable $e) {
            $scoring->checkApproveFailed();
            Log::channel('scoring_steps_logs')->info("Ошибка: {$e->getMessage()};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            return response()
                ->json(ScoringErrorService::catchingException($scoring, $logger, $e, $request), 201);
        }

        return response()->json([], 201);
    }

    public function extendedLimit(int $buyerID, ExtendedLimitRequest $request)
    {
        Log::channel('scoring_steps_logs')->info("Запрос от ML на extended");
        $data = $request->get('data');
        $error = $request->get('error');
        Log::channel('scoring_steps_logs')->info("Принятые параметры: ", $data);
        $scoring = ScoringResult::find($data['scoring_request_id']);

        $buyer = Buyer::findOrFail($buyerID);

        if ($scoring && $error != null){
            $scoring->total_state = ScoringResult::STATE_FAILED_RESPONSE;
            $scoring->save();
            Log::channel('scoring_steps_logs')->info("Пришла ошибка от ML в массиве error; Total state выставлен в статус 4");
        }

        try {

            $logger = new LoggerByUser($buyer, 'scoring', 'full');

            $logger->info("Параметры запроса", $data);

            GradeScoring::setLogger($logger);

            GradeScoring::saveLimitByScoring(
                $scoring,
                $data['limit'],
            );

            $buyer->status = Buyer::KYC_STATUS_VERIFY;
            $buyer->save();

            Log::channel('scoring_steps_logs')->info("Message: Выставление основного лимита пользователю {$buyerID} в размере {$data['limit']};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            BuyerLimit::setLimit($scoring->buyer, $data['limit']);
            $scoring->dropInitiator();
            $scoring->finalSuccess();
            $scoring->dropErrorMessage();
        } catch (MLException $e) {
            $scoring->checkApproveFailed();
            Log::channel('scoring_steps_logs')->info("Ошибка: {$e->getMessage()};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            return response()
                ->json(ScoringErrorService::catchingMLBaseException($scoring, $logger, $e), 201);
        } catch (\Throwable $e) {
            $scoring->checkApproveFailed();
            Log::channel('scoring_steps_logs')->info("Ошибка: {$e->getMessage()};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            return response()
                ->json(ScoringErrorService::catchingException($scoring, $logger, $e, $request), 201);
        }
        return response()->json([], 201);
    }

}
