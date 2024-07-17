<?php

namespace App\Services\API\V3;

use App\Models\Company;
use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SlidesService extends BaseService
{
    public static function list(Request $request)
    {
        $data = Slide::with('image')->orderBy('id', 'DESC')->get();
        foreach ($data as $index => $item) {
            if ($item->image) {
                $previewPath = str_replace($item->name, 'preview_' . $item->name, $item->image->path);
                $item->image->preview = Storage::url($previewPath);
            }
        }
        $result = [];
        $result['total']  = count($data);
        $result['data'] = $data;
        return self::handleResponse($result);
    }

    public static function detail($id)
    {
        $data = Slide::with('image')->find($id);
        if (!$data) {
            return self::handleError(['Not found']);
        }
        if ($data->image) {
            $previewPath = str_replace($data->name, 'preview_' . $data->name, $data->image->path);
            $data->image->preview = Storage::url($previewPath);
        }
        return self::handleResponse($data);
    }
}
