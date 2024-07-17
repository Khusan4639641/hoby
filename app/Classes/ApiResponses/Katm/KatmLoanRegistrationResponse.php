<?php

namespace App\Classes\ApiResponses\Katm;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Exceptions\KatmException;

class KatmLoanRegistrationResponse extends BaseResponse
{

//    /**
//     * @throws KatmException
//     */
//    public function claimID(): string
//    {
//        $data = $this->json();
//        if (!isset($data['response'])) {
//            throw new KatmException("Элемент response не найден", "", [], $data);
//        }
//        if (!isset($data['response']['claim_id'])) {
//            throw new KatmException("Элемент response->claim_id не найден", "", [], $data);
//        }
//        return $data['response']['claim_id'];
//    }

}
