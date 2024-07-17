<?php


namespace App\Services\API\Core;


use App\Http\Requests\Core\PartnerController\BlockRequest;
use App\Models\BlockingHistory;
use App\Models\BlockingReasons;
use App\Models\Buyer;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class BlockHistoryService
{
    /**
     * @param int $user_id
     * @return array
     */
    public static function add(BlockRequest $request) {
        $partner = Company::find($request->partner_id);
        $block_reasons = $request->block_reasons_id;
        if(count($block_reasons)>0) {
            foreach ($block_reasons as $block_reason) {
                BlockingHistory::create([
                    'company_id' => $request->partner_id,
                    'type'       => '0',
                    'user_id'    => $partner->user->id ?? NULL,
                    'manager_id' => $partner->manager_id ?? NULL,
                    'reason_id'  => $block_reason,
                    'comment'    => BlockingReasons::find($block_reason)->position === 'bottom' ? $request->block_reason : NULL
                ]);
            }
        }
    }

    public static function show($company_id): array {
        $return = [];
        if($histories =  BlockingHistory::where('company_id', $company_id)) {
            foreach ($histories->get() as $history) {
                $user = User::find($history->user_id);
                $manager = User::find($history->manager_id);
                $return[] = [
                    'admin' => $user ? $user->name." ".$user->surname: "Admin not found",
                    'type' => $history->type === 0 ? "Блокировка" : "Разблокировка",
                    'manager' => $history->manager_id ? $manager->name." ".$manager->surname: "Не найдено",
                    'comment' => $history->comment ?? BlockingReasons::find($history->reason_id)->name,
                    'data' => ($history->created_at) ? $history->created_at->format('Y-m-d') : "Not found!"
                ];
            }
        }
        return $return;
    }

    public static function showReasons() {
        return BlockingReasons::orderBy('position','DESC')->get();
    }

    public static function unBlock(Request $request) {
        $partner = Company::find($request->partner_id);
        BlockingHistory::create([
            'company_id' => $request->partner_id,
            'type'       => '1',
            'user_id'    => $partner->user->id ?? NULL,
            'manager_id' => $partner->manager_id ?? NULL,
            'reason_id'  => NULL,
            'comment'    => 'Недостатки устранены'
        ]);
    }
}
