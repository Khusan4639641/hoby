<?php

namespace App\Services\API\V3;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DB;

class NewsService extends BaseService
{
    public static function list($filters = [])
    {
        $lang = app()->getLocale();
        $storage_path = Storage::url('');
        $data = News::select('news.id', 'news_languages.title', 'news_languages.slug', 'files.path as image_url', 'news.created_at', 'news.updated_at', 'news.date', 'news.is_mobile', 'news_languages.preview_text', 'news_languages.detail_text')
            ->addSelect(DB::raw("CONCAT('$storage_path',files.path) AS image_url"))
            ->leftJoin('news_languages', 'news_languages.news_id', '=', 'news.id')
            ->leftJoin('files', 'news_languages.id', '=', 'files.element_id')
            ->where('news.status', 1)
            ->where('files.model', 'news-language')
            ->where('files.type', 'image')
            ->where('news_languages.language_code', $lang);

        if (isset($filters['is_mobile'])) {
            $data = $data->where('news.is_mobile', $filters['is_mobile']);
        }
        if (!empty($filters['id'])) {
            $data = $data->where('news.id', $filters['id']);
            $data = $data->first();
        } else {
            $data = $data->get();
        }
        if ($data) return self::handleResponse($data);

        return self::handleError([], 'error', 404);
    }
}
