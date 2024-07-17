<?php

namespace App\Http\Controllers\Admin\MFO;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MFO\CreateAccountBalanceHistoryRecordRequest;
use App\Http\Requests\Admin\MFO\UpdateAccountBalanceHistoryRecordRequest;
use App\Http\Resources\Admin\MFO\AccountBalance1CCollection;
use App\Http\Resources\Admin\MFO\AccountBalance1CResource;
use App\Http\Resources\Admin\MFO\AccountResource;
use App\Jobs\MFO\CalculateAccountBalances1C;
use App\Models\AccountBalanceHistory1C;
use App\Models\MFOAccount;
use App\Services\API\V3\BaseService;
use App\Services\MFO\Account1CService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Psr\SimpleCache\InvalidArgumentException;

class AccountController extends Controller
{
    /**
     * @OA\Get (
     *     path="/api/v3/admin/accounts",
     *     summary="Collection of mfo accounts",
     *     tags={"Accounts"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="number", type="string"),
     *                 @OA\Property(property="account_1c_numbers", type="array", @OA\Items(type="string"))
     *             ))
     *         )
     *     )
     * )
     *
     *
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $mfoAccounts = MFOAccount::with(['accounts1C'])
            ->get();

        return BaseService::successJson(AccountResource::collection($mfoAccounts));
    }

    /**
     * @OA\Get (
     *     path="/api/v3/admin/accounts/balances",
     *     summary="Collection of mfo accounts balances with pagination",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter (
     *          name="mfo_account_number",
     *          in="query",
     *          description="Filter by mfo account number"
     *     ),
     *
     *     @OA\Parameter (
     *          name="per_page",
     *          in="query",
     *          description="Number of items per page"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="data", type="object", properties={
     *                 @OA\Property(property="balances", type="array", @OA\Items(properties={
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="operation_date", type="string"),
     *                     @OA\Property(property="earliest_balance", type="integer"),
     *                     @OA\Property(property="current_balance", type="integer"),
     *                     @OA\Property(property="mfo_account", type="object", properties={
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="number", type="string"),
     *                     }),
     *                 })),
     *                 @OA\Property(property="pagination", type="object", properties={
     *                     @OA\Property(property="total", type="integer"),
     *                     @OA\Property(property="count", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="first_page", type="string"),
     *                     @OA\Property(property="last_page", type="string"),
     *                     @OA\Property(property="previous_page", type="string", nullable=true),
     *                     @OA\Property(property="next_page", type="string", nullable=true),
     *                 }),
     *             }),
     *         ),
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function balances(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mfo_account_number' => ['filled', 'string', 'max:20'],
            'per_page' => ['filled', 'integer', 'between:10,50']
        ]);

        $balances = AccountBalanceHistory1C::with(['mfoAccount'])
            ->when(isset($validator->valid()['mfo_account_number']), function ($query) use ($validator) {
                $query->whereHas('mfoAccount', function ($query) use ($validator) {
                    $query->where('number', 'LIKE', "{$validator->valid()['mfo_account_number']}%");
                });
            })
            ->join(
                DB::raw(
                    "(SELECT mfo_account_id, MIN(operation_date) as earliest_date
                    FROM account_balance_histories_1c GROUP BY mfo_account_id) as abh_unique"
                ),
                function ($join) {
                    $join->on('account_balance_histories_1c.mfo_account_id', '=', 'abh_unique.mfo_account_id')
                        ->on('account_balance_histories_1c.operation_date', '=', 'abh_unique.earliest_date');
                }
            )
            ->paginate(intval($validator->valid()['per_page'] ?? 10))
            ->withQueryString();

        return BaseService::successJson(new AccountBalance1CCollection($balances));
    }

    /**
     * @OA\Post (
     *     path="/api/v3/admin/accounts/balances/calculate",
     *     summary="Calculate all mfo accounts balances starting from the specified date",
     *     tags={"Accounts"},
     *
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent (
     *              required={"start_date"},
     *              @OA\Property(property="start_date", type="string", example="2023-02-28"),
     *          )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="data", type="object", properties={
     *                  @OA\Property (property="message", type="string"),
     *                  @OA\Property (property="process_id", type="string"),
     *             }),
     *         )
     *     )
     * )
     *
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateAllBalances(Request $request, Account1CService $account1CService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:' . now()->startOfMonth()->subDay()],
        ]);

        if ($validator->fails()) {
            return BaseService::errorJson($validator->errors()->messages(), 'error', 422);
        }

        if ($account1CService->isCalculatingBalancesAlreadyInProgress()) {
            return BaseService::errorJson([trans('mfo_account.calculating-balances-already-in-progress')]);
        }

        $startDate = Carbon::createFromFormat('Y-m-d', $request->input('start_date'));
        $processId = uniqid();
        $mfoAccounts = MFOAccount::whereIn('id', function (Builder $builder) {
            $builder->select('mfo_account_id')
                ->from('account_balance_histories_1c')
                ->groupBy('mfo_account_id');
        })
            ->get();

        CalculateAccountBalances1C::dispatch($processId, $startDate, $mfoAccounts)
            ->onConnection('redis_mko_report');

        return BaseService::successJson([
            'message' => trans('mfo_account.calculating-balances-process-started'),
            'process_id' => $processId
        ]);
    }

    /**
     * @OA\Post (
     *     path="/api/v3/admin/accounts/balances/calculate/{id}",
     *     summary="Calculate balance starting from the given balance history record",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter (ref="#/components/parameters/balance_history_record_id"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="data", type="object", properties={
     *                  @OA\Property (property="message", type="string"),
     *                  @OA\Property (property="process_id", type="string"),
     *             }),
     *         )
     *     )
     * )
     *
     * @param $id
     * @return JsonResponse
     */
    public function calculateBalance(Request $request, Account1CService $account1CService, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:' . now()->startOfMonth()->subDay()],
        ]);

        if ($validator->fails()) {
            return BaseService::errorJson($validator->errors()->messages(), 'error', 422);
        }

        if ($account1CService->isCalculatingBalancesAlreadyInProgress()) {
            return BaseService::errorJson([trans('mfo_account.calculating-balances-already-in-progress')]);
        }

        $balanceHistoryRecord = AccountBalanceHistory1C::find($id);
        $startDate = Carbon::createFromFormat('Y-m-d', $request->input('start_date'));

        if (!$balanceHistoryRecord) {
            return BaseService::errorJson([trans('mfo_account.balance-history-record-not-found')], 'error', 404);
        }

        if (
            AccountBalanceHistory1C::where('operation_date', '<', $balanceHistoryRecord->operation_date)
                ->where('mfo_account_id', '=', $balanceHistoryRecord->mfo_account_id)
                ->exists()
        ) {
            return BaseService::errorJson([trans('mfo_account.balance-history-record-is-not-earliest')]);
        }

        $processId = uniqid();
        CalculateAccountBalances1C::dispatch($processId, $startDate, collect([MFOAccount::find($balanceHistoryRecord->mfo_account_id)]))
            ->onConnection('redis_mko_report');

        return BaseService::successJson([
            'message' => trans('mfo_account.calculating-balances-process-started'),
            'process_id' => $processId,
        ]);
    }

    /**
     * @OA\Get (
     *     path="/api/v3/admin/accounts/balances/calculate/status/{process_id}",
     *     summary="Retrieve status of the process of calculating balances",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter (
     *          name="process_id",
     *          in="path",
     *          required=true,
     *          description="Process ID",
     *     ),
     *
     *     @OA\Response (
     *          response=200,
     *          description="OK",
     *          @OA\JsonContent (
     *              @OA\Property (property="status", type="string", example="success"),
     *              @OA\Property (property="error", type="array", @OA\Items(type="string")),
     *              @OA\Property (property="data", type="object", properties={
     *                  @OA\Property (property="status", type="string", example="in_progress"),
     *                  @OA\Property (property="percentage", type="numeric", example="31.7"),
     *                  @OA\Property (property="estimated_remaining_time", type="string", example="01:47"),
     *              })
     *          )
     *     )
     * )
     *
     *
     * @param $processId
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function calculateBalancesProcessStatus($processId): JsonResponse
    {
        $status = Cache::driver('redis')->get(CalculateAccountBalances1C::PREFIX . $processId);

        if (!$status) {
            return BaseService::errorJson([trans('mfo_account.calculating-balances-process-status-not-found')], 'error', 404);
        }

        return BaseService::successJson($status);
    }

    /**
     * @OA\Post (
     *     path="/api/v3/admin/accounts/balances",
     *     summary="Create balance history record for specific mfo account",
     *     tags={"Accounts"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mfo_account_id", "operation_date", "balance"},
     *             @OA\Property(property="mfo_account_id", type="integer", example=175),
     *             @OA\Property(property="operation_date", type="string", format="date", example="2023-02-28"),
     *             @OA\Property(property="balance", type="integer", example=12500099)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=3198),
     *                 @OA\Property(property="operation_date", type="string", format="date-time", example="2023-05-26 23:59:59"),
     *                 @OA\Property(property="earliest_balance", type="integer", example=12500099),
     *                 @OA\Property(property="current_balance", type="integer", example=12500099),
     *                 @OA\Property(property="mfo_account", type="object",
     *                     @OA\Property(property="id", type="integer", example=175),
     *                     @OA\Property(property="number", type="string", example="56218000905570410001")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     *
     *
     * @param CreateAccountBalanceHistoryRecordRequest $request
     * @return JsonResponse
     */
    public function createBalanceHistoryRecord(CreateAccountBalanceHistoryRecordRequest $request): JsonResponse
    {
        $operationDate = Carbon::createFromFormat('Y-m-d', $request->validated()['operation_date']);
        if (
            AccountBalanceHistory1C::whereBetween('operation_date', [
                $operationDate->startOfDay()->format('Y-m-d H:i:s'),
                $operationDate->endOfDay()->format('Y-m-d H:i:s')
        ])
            ->where('mfo_account_id', '=', $request->validated()['mfo_account_id'])
            ->exists()) {
            return BaseService::errorJson([trans('mfo_account.balance-history-record-already-exists')]);
        }

        $balanceHistoryRecord = new AccountBalanceHistory1C();
        $balanceHistoryRecord->mfo_account_id = $request->validated()['mfo_account_id'];
        $balanceHistoryRecord->operation_date = $operationDate->setTime(23, 59, 59)->format('Y-m-d H:i:s');
        $balanceHistoryRecord->balance = floatval($request->validated()['balance'] / 100);
        $balanceHistoryRecord->save();
        $balanceHistoryRecord->load('mfoAccount');

        return BaseService::successJson(new AccountBalance1CResource($balanceHistoryRecord), 'success', 201);
    }

    /**
     *
     * @OA\Parameter (
     *     name="balance history record id",
     *     parameter="balance_history_record_id",
     *     in="path",
     *     required=true
     * )
     *
     * @OA\Put (
     *     path="/api/v3/admin/accounts/balances/{id}",
     *     summary="Update specific balance history record",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter (ref="#/components/parameters/balance_history_record_id"),
     *
     *     @OA\RequestBody (
     *          required=true,
     *          @OA\JsonContent (
     *              required={"balance"},
     *              @OA\Property (property="balance", type="integer")
     *          )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=3198),
     *                 @OA\Property(property="operation_date", type="string", format="date-time", example="2023-05-26 23:59:59"),
     *                 @OA\Property(property="earliest_balance", type="integer", example=12500099),
     *                 @OA\Property(property="current_balance", type="integer", example=12500099),
     *                 @OA\Property(property="mfo_account", type="object",
     *                     @OA\Property(property="id", type="integer", example=175),
     *                     @OA\Property(property="number", type="string", example="56218000905570410001")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     *
     *
     * @param UpdateAccountBalanceHistoryRecordRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function updateBalanceHistoryRecord(UpdateAccountBalanceHistoryRecordRequest $request, $id): JsonResponse
    {
        $balanceHistoryRecord = AccountBalanceHistory1C::find($id);
        if (!$balanceHistoryRecord) {
            return BaseService::errorJson([trans('mfo_account.balance-history-record-not-found')], 'error', 404);
        }

        if (
            AccountBalanceHistory1C::where('operation_date', '<', $balanceHistoryRecord->operation_date)
                ->where('mfo_account_id', '=', $balanceHistoryRecord->mfo_account_id)
                ->exists()
        ) {
            return BaseService::errorJson([trans('mfo_account.balance-history-record-is-not-earliest')]);
        }

        $balanceHistoryRecord->balance = $request->validated()['earliest_balance'] / 100;
        $balanceHistoryRecord->save();
        $balanceHistoryRecord->load('mfoAccount');

        return BaseService::successJson(new AccountBalance1CResource($balanceHistoryRecord));
    }

    /**
     * @OA\Delete (
     *     path="/api/v3/admin/accounts/balances/{id}",
     *     summary="Delete specific balance history record",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter (ref="#/components/parameters/balance_history_record_id"),
     *
     *     @OA\Response(
     *          response=204,
     *          description="No Content"
     *     )
     * )
     *
     * @throws \Exception
     */
    public function deleteBalanceHistoryRecord($id): JsonResponse
    {
        $balanceHistoryRecord = AccountBalanceHistory1C::find($id);
        if (!$balanceHistoryRecord) {
            return BaseService::errorJson([trans('mfo_account.balance-history-record-not-found')], 'error', 404);
        }

        if (
            AccountBalanceHistory1C::where('operation_date', '<', $balanceHistoryRecord->operation_date)
                ->where('mfo_account_id', '=', $balanceHistoryRecord->mfo_account_id)
                ->exists()
        ) {
            return BaseService::errorJson([trans('mfo_account.balance-history-record-is-not-earliest')]);
        }

        $balanceHistoryRecord->delete();

        return BaseService::successJson([], 'success', 204);
    }
}
