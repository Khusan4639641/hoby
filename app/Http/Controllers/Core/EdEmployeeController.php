<?php
namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Services\API\Core\MFOPaymentsHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class EdEmployeeController extends Controller
{
    public function getDate(Request $request){
        $request->date = count($date = explode(',',$request->date))==1 ? $request->date : $date;
        if (is_array($request->date)){ $validator = Validator::make($request->all(), ['date.*.' => 'required|date']);}
        else { $validator = Validator::make($request->all(), ['date' => 'required|date']); }
        if ($validator->fails()) {
            return $validator->validated();
        }
        $result = MFOPaymentsHistoryService::show($request);
        return $result;
    }
}
