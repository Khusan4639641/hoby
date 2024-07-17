<?php

namespace App\Http\Controllers\Web\Panel;


use App\Http\Controllers\Controller;
use App\Models\KycHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChangePhoneNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!empty($request->phone)) {
            $pattern = "/^998\d{9}$/";
            if (!preg_match($pattern, $request->phone))
                return response()->json([
                    'status' => 400,
                    'message' => __('panel/buyer.wrong_format'),
                    'data' => [
                        'phone' => $request->phone
                    ]
                ]);
        } else {
            return response()->json([
                'status' => 401,
                'message' => __('panel/buyer.phone_field_empty'),
                'data' => [
                    'phone' => $request->phone
                ]
            ]);
        }


        if (strlen($request->definition) == 0)
            return response()->json([
                'status' => 402,
                'message' => __('panel/buyer.string_field_empty'),
                'data' => [
                    'definition' => $request->definition
                ]
            ]);
        if (strlen($request->definition) > 255)
            return response()->json([
                'status' => 403,
                'message' => __('panel/buyer.string_field_large'),
                'data' => [
                    'definition' => $request->definition
                ]
            ]);
        if (!isset($request->image))
            return response()->json([
                'status' => 404,
                'message' => __('panel/buyer.file_field_empty'),
                'data' => [
                    'definition' => null
                ]
            ]);

        $check = User::where('phone', $request->phone)->first();

        if (!empty($check))
            return response()->json([
                'status' => 202,
                'message' => __('panel/buyer.phone_number_exist', ['attribute' => $request->phone]),
                'data' => [
                    'phone' => $request->phone
                ]
            ]);

        $user = User::find($request->user_id);
        $bayer = DB::table('buyer_personals')->where('user_id', $user->id)->first();

        $params = [
            'files' => $request->file(),
            'bayer_id' => $bayer->id,
            'user_id' => $user->id,
            'model' => 'buyer-personal'
        ];

        $user = User::where('id', $request->user_id)->first();
        $old_phone = $user->phone;
        $user->phone = $request->phone;
        $user->save();
//        FileHelper::upload($params, [], true);
        if (is_null($params['bayer_id'])) {
            $path = "{$params['model']}/{$params['user_id']}/image-changed-phone-number";

        } else {
            $path = "{$params['model']}/{$params['bayer_id']}/image-changed-phone-number";
        }

        $fileInfo = pathinfo($request->image->getClientOriginalName());
        $fileName = md5($request->image->getClientOriginalName() . time()) . "." . $fileInfo['extension'];
        $fullPath = $path . $fileName;

        $saved = Storage::putFileAs($path, $request->image, $fileName);

        $kycHistory = new KycHistory();
        $kycHistory->user_id = $request->user_id; // покупатель
        $kycHistory->kyc_id = Auth::user()->id; //  KYC оператор
        $kycHistory->status = User::KYC_STATUS_UPDATE; // статус покупателя новый, редактируется, изменен, модерация, верифицирован, заблокирован
        $kycHistory->title = $request->definition; // причина отказа
        $kycHistory->title = $request->definition; // причина отказа
        $kycHistory->image = $saved; // причина отказа
        $kycHistory->old_phone = $old_phone; // причина отказа
        $kycHistory->save();
        return response()->json([
            'status' => 200,
            'message' => "success",
            'data' => []
        ]);


    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
