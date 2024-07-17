<?php

namespace App\Http\Controllers\V3\DebtCollect\Leader;

use App\Exports\LettersReportExport;
use App\Http\Controllers\Controller;

use App\Http\Requests\DebtCollect\Leader\AnalyticController\AnalyticLettersRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Exports\DebtCollectContractResultsExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Letter;

use App\Http\Resources\V3\DebtCollect\Leader\AnalyticController\AnalyticLettersResourceCollection;

class AnalyticController extends Controller
{
    public function export(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        return Excel::download(new DebtCollectContractResultsExport($month), "{$month}.xlsx");
    }

    public function exportLettersReport(AnalyticLettersRequest $request)
    {
        $letters_report_result = $this->getLettersReport($request);

        return Excel::download(new LettersReportExport($letters_report_result->get()), "letters_export.xlsx");
    }

    public function letters(AnalyticLettersRequest $request): AnalyticLettersResourceCollection
    {
        $letters_report_result = $this->getLettersReport($request);

        return new AnalyticLettersResourceCollection($letters_report_result->paginate(15));
    }

    public function letterSenders(): JsonResponse
    {
        $senders = User::select("users.id as sender_id")
            ->selectRaw("CONCAT_WS(' ', `users`.`name`, `users`.`surname`, `users`.`patronymic`) as sender_fio")
            ->whereIn("users.id", function ($query) {
                $query->select("letters.sender_id")
                    ->distinct()
                    ->from("letters")
                    ->orderByDesc("letters.id")
                ;
            })
            ->orderByDesc("users.id")
        ;

        return response()->json([
            "data" => $senders->get()->toArray()
        ]);
    }

    private function getLettersReport($request)
    {
        $letters_report_result = Letter::with([
            "sender:id,name,surname,patronymic",
            "debtor:id,name,surname,patronymic",
            "region:external_id,name",
            "area:external_id,name"
        ])
            ->select("sender_id", "buyer_id", "contract_id", "region", "area", "created_at")
            ->has("sender")
            ->has("debtor")
            ->has("region")
            ->has("area")
            ->whereStatus(1)
            ->orderByDesc("created_at")
        ;

        if ($request->filled("date_from")) {
            $letters_report_result->where(
                'created_at',
                '>=',
                Carbon::createFromFormat('Y-m-d', $request->validated()["date_from"])->startOfDay()
            );
        }
        if($request->filled("date_to")) {
            $letters_report_result->where(
                'created_at',
                '<=',
                Carbon::createFromFormat('Y-m-d', $request->validated()["date_to"])->endOfDay()
            );
        }

        if ( $request->filled("senders") ) {
            $letters_report_result
                ->whereIn("sender_id", (array) $request->validated()["senders"])
            ;
        }

        return $letters_report_result;
    }
}
