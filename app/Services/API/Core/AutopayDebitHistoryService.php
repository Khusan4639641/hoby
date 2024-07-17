<?php


namespace App\Services\API\Core;


use App\Models\Buyer;

class AutopayDebitHistoryService
{
    /**
     * @param int $user_id
     * @return array
     */
    public function getOverdueContracts(int $user_id) : array {

        $debts = Buyer::find($user_id)->autopayDebitHistory()
            ->select("contract_id")
            ->where("days", ">", 30)
            ->groupBy("contract_id")
            ->get();

        $data = [];
        if (count($debts) > 0) {
            foreach ($debts as $debt) {
                $data[] = $debt->contract_id;
            }
        }

        return $data;
    }
}
