<?php

namespace App\Http\Controllers\V3;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoreController extends Controller
{
    protected $model;
    protected $defaultLocale;

    public function __construct()
    {
        $languages = LocaleHelper::languages();
        foreach ($languages as $language){
            if ($language->default) $this->defaultLocale = $language->code;
        }
    }
    /**
     * @OA\Info(
     *      version="3.0.0",
     *      description="Documentation for developoers,structure,request,response etc..",
     *      @OA\Contact(
     *      ),
     *      @OA\License(
     *          name="Go to version 1.0.0",
     *          url="/api/documentation",
     *      ),
     * )
     *
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST2,
     * )
     *
     * @OA\Tag(
     *     name="test",
     *     description="REST API test",
     *     name="Authorization",
     *     description="Authorization buyer, partner and employeer. For buyer first use method send-sms-code after receive SMS use method auth. For employeer use phone and password in method auth. For partner use partner_id and password"
     * )
     */

    protected function single($id, $with = [])
    {
        $user = Auth::user();
        $single = $this->model->whereId($id)->with($with)->first();
        if ($user && $single)
            $single->permissions = $this->permissions($single, $user);
        return $single;
    }

    protected function permissions($item, User $user)
    {
        $permissions = [];

        if ($user->can('detail', $item)) {
            $permissions[] = 'detail';
        }
        if ($user->can('modify', $item)) {
            $permissions[] = 'modify';
        }
        if ($user->can('delete', $item)) {
            $permissions[] = 'delete';
        }

        return $permissions;
    }

    public function handleResponse($data = [])
    {
        return [
            'code' => 1,
            'error' => [],
            'data' => $data
        ];
    }
}
