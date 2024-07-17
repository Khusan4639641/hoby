<?php
namespace App\Services;

use App\Http\Requests\AddFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Models\FaqInfo;
use App\Models\FaqInfoHistory;
use App\Services\API\V3\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaqService extends BaseService
{
    public function new(Request $request)
    {
        $faqInfo = FaqInfo::create([
            'user_id'     => Auth::id(),
            'sort'        => (FaqInfo::latest()->first()->id ?? 0)+1,
            'answer_ru'   => $request->answer_ru,
            'answer_uz'   => $request->answer_uz,
            'question_uz' => $request->question_uz,
            'question_ru' => $request->question_ru
        ]);
        if($request->has('sort')){
            $faqInfo = $this->update($request, $faqInfo, false);
        }
        return $faqInfo;
    }

    public function list(Request $request) {
        return FaqInfo::orderBy('sort')->paginate($request->limit ?? 10, ['*'], 'page', $request->page);
    }

    public function show(Request $request) {
        $faqInfo = FaqInfo::where('status', FaqInfo::STATUS_SHOW);
        $faqInfo->when($request->header('Content-Language') == 'ru', function ($q) {
             $q->selectRaw('question_ru as question, answer_ru as answer, sort, updated_at');
        });
        $faqInfo->when($request->header('Content-Language') == 'uz', function ($q) {
             $q->selectRaw('question_uz as question, answer_uz as answer, sort, updated_at');
        });
        return $faqInfo->orderBy('sort')->paginate($request->limit ?? 10, ['*'], 'page', $request->page);
    }

    public function history(Request $request) {
        return FaqInfo::with('history')->paginate($request->limit ?? 10, ['*'], 'page', $request->page);
    }

    public function update(Request $request, FaqInfo $faqInfo, $history = true)
    {
        if (($request->has('answer_uz') || $request->has('answer_ru')) && $history)
        {
            FaqInfoHistory::create([
                'faq_id'      => $faqInfo->id,
                'previous_ru' => $faqInfo->answer_ru,
                'previous_uz' => $faqInfo->answer_uz,
                'user_id'     => $faqInfo->user_id
            ]);
        }

        if ($request->has('sort'))
        {
            $newSort = $request->sort;
            $oldSort = $faqInfo->sort;
            $old = FaqInfo::where('sort', $newSort);
            $old->update(['sort'=>$oldSort]);
            $faqInfo->update(['sort'=>$newSort]);
        }
        $faqInfo->update($request->all() + ['user_id' => Auth::id()]);

        return $faqInfo;
    }
}
