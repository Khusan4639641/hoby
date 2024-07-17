<?php

namespace App\Http\Controllers\Partners;

use App\Aggregators\DebtCollectActionsAggregators\ActionsAggregator;
use App\Http\Controllers\V3\CoreController;
use App\Models\Buyer;
use App\Models\Company;
use App\Models\Contract;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\Partners\BuyerService;
use Illuminate\Http\Request;

class BuyerController extends CoreController
{
    protected BuyerService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new BuyerService();
    }

    /**
     * @OA\GET(
     *      path="/buyers/list",
     *      tags={"Buyer"},
     *      summary="Метод для проверки статус пользователя",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="phone",
     *          description="Phone",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      "id": 372711,
     *                      "email": null,
     *                      "name": "KADIROV",
     *                      "surname": "SAIDORIF",
     *                      "patronymic": "",
     *                      "phone": "+998900626204",
     *                      "status": 12,
     *                      "doc_path": 1,
     *                      "vip": 0,
     *                      "created_by": null,
     *                      "status_caption": "Rad etildi: Aloqa uchun shaxslarni ko`rsating",
     *                      "permissions": {
     *                          "detail",
     *                          "modify"
     *                      },
     *                      "debs": 0,
     *                      "vip_allowed": 1,
     *                      "black_list": null,
     *                      "settings": {
     *                          "id": 317021,
     *                          "user_id": 372711,
     *                          "limit": 3000000,
     *                          "personal_account": 0
     *                      },
     *                      "personals": {
     *                          "id": 364906,
     *                          "user_id": 372711,
     *                          "passport_type": 6
     *                      }
     *                  }
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                      "status": "error",
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "Parol noto`g`ri "
     *                          }
     *                      },
     *                      "data": {}
     *                  }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated"
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Forbidden"
     *                     }
     *                 )
     *              ),
     *          )
     *     )
     */
    public function list(Request $request)
    {
        $this->service->validatePhone($request);
        return $this->service->list($request);
    }

    /**
     * @OA\Get(
     *      path="/buyer/phones-count",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод проверяет наличие телефона",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="buyer_id",
     *          description="Buyer ID",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"status":"success","error":{},"data":{"phones_count":0}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                      "status": "error",
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "buyer id maydoni to`ldirilgan bo`lishi shart."
     *                          }
     *                      },
     *                      "data": {}
     *                  }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated"
     *                     }
     *                 )
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Forbidden"
     *                     }
     *                 )
     *              )
     *          )
     *     )
     */
    public function phonesCount(Request $request)
    {
        $this->service->validateBuyer($request);
        return $this->service->phonesCount($request->buyer_id,$request->category_id ?? 0);
    }

    /**
     * @OA\Get(
     *      path="partner/detail",
     *      tags={"Partner"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод возвращает Детальную информацию о партнере, а также список продовцов",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                       "partner_name": "OOO «MEDIAPARK GROUP»",
     *                       "partner_address": "г.Ташкент Чиланзарский р-н ул. Ц квартал Катартал дом 28",
     *                       "partner_phone": "998712033333",
     *                       "seller_list": {
     *                           {
     *                           "id": 235429,
     *                           "name": "Bobir",
     *                           "surname": "Omonov",
     *                           "patronymic": "Xusan o'g'li",
     *                           "phone": "+1510321023"
     *                           },
     *                           {
     *                           "id": 253174,
     *                           "name": "Dilshod",
     *                           "surname": "Narziyev",
     *                           "patronymic": "Erkin o'g'li",
     *                           "phone": "+1651153"
     *                           }
     *                        }
     *                   }
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated"
     *                     }
     *                 )
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Forbidden"
     *                     }
     *                 )
     *              )
     *          )
     *     )
     */
    public function getPartnerDetailInformation(){
      $this->service->getPartnerDetailInformation();
    }

    /**
     * @OA\Post(
     *      path="/buyers/verify",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод проверка на black_list",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="phone",
     *          description="Phone",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                  "buyer_id": 215088,
     *                  "message": "заблокирован",
     *                  "result": 8
     *                  }
     *                  }
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                      "status": "error",
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "Покупатель не найден!"
     *                          }
     *                      },
     *                      "data": {}
     *                  }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated"
     *                     }
     *                 )
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Forbidden"
     *                     }
     *                 )
     *              )
     *          )
     *     )
     */
    public function verify(Request $request)
    {
        $this->service->validatePhone($request);
        return $this->service->verify($request->get('phone'));
    }

    public function actionsByBuyer(Request $request, Buyer $buyer)
    {
        $company = Company::find(\Auth::user()->company_id);
        if (!$company){
            return BaseService::errorJson([__('app.err_not_found')], 'error', 404);
        }

        if ($company->parent_id){
            $contract_ids = Contract::whereIn('company_id', array_merge(Company::find($company->parent_id)->childrens()->get()->pluck('id')->toArray(),[$company->parent_id]))
                ->whereIn('status', [3,4])
                ->whereUserId($buyer->id)
                ->get()
                ->pluck('id')->toArray();
        } else {
            $contract_ids = Contract::whereCompanyId($company->id)->whereUserId($buyer->id)->get()->pluck('id');
        }
        $buyer->load('settings');
        $buyer->actions = array_values(collect(ActionsAggregator::getByPartnerContracts($contract_ids))->sortByDesc('created_at')->toArray());
        return response()->json($buyer);
    }
}
