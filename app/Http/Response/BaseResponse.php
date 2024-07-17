<?php

namespace App\Http\Response;

use Illuminate\Http\JsonResponse;

class BaseResponse extends JsonResponse
{
    public static function error($data = null, $code = null, $message = [], $error = []): JsonResponse
    {
        $code = $code ?? 500;
        $result['status'] = false;
        $result['response'] =
            [
                'code'    => $code,
                'message' => $message,
                'error'   => $error ,
            ];
        $result['data'] = $data;


        return new JsonResponse([
            $result
        ], $code);
    }

    public static function success($data = null, $code = null, $message = []): JsonResponse
    {
        $code = $code ?? 200;
        $result['status'] = true;
        $result['response'] =
            [
                'code'    => $code,
                'message' => $message,
            ];
        $result['data'] = $data;

        return new JsonResponse([
            $result
        ], $code);
    }

}
