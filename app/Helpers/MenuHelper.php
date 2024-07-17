<?php

namespace App\Helpers;

use App\Models\Menu;
use App\Models\Partner;
use App\Models\Role;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Class MenuHelper
 * @package App\Helpers
 */
class MenuHelper {


    /**
     * @param string $type
     * @param string $position
     * @return Application|Factory|View
     */
    public static function render($type = 'frontend', $position = 'top'){
        $user = Auth::user();

        $view = $type.'.menu.'.$position;
        //$cache = $type.'.menu.'.$position.($user?'.user_'.$user->id:'');
        $route = Request::route()->getName();

        $menu = null; //$menu = Cache::get(app()->getLocale().'.'.$cache);
        if($menu == null) {
            $items = Menu::whereType($type)->wherePosition($position)->whereNull('parent_id')->OrderByRaw("CAST(sort as SIGNED INTEGER) ASC")->with('children')->get();

            $menu = self::build($items);

            foreach($menu as $index => $item) {
                $valid = true;

                //check permission
                if ($user->hasRole('owner')) {
                    $valid = $item->name == 'report_clients'
                        || ($item->name == 'reports' && $item->permission == 'show-admin')
                        || $item->name == 'graph'
                        || $item->name == 'graph_profit_by_contracts';
                } elseif ($user->hasRole('cco')) {
                    $valid = false;
                } elseif ($user->hasRole('recover')) {
                    $valid = $item->permission == 'recover';
                } elseif ($user->hasRole('editor')) {
                    $valid = $item->permission == 'editor';
                } else {
                    if ($item->permission && !$user->hasPermission($item->permission)) $valid = false;
                }

                if(Role::find($user->role_id)->name === 'debt-collect-curator') {
                    $valid = $item->permission === 'debt-collect-curator' || $item->permission == 'recover';
                } elseif(Role::find($user->role_id)->name === "debt-collect-curator-extended") {
                    $valid = $item->permission === "debt-collect-curator-extended" || $item->permission == 'recover';
                } elseif(Role::find($user->role_id)->name === 'debt-collect-leader') {
                    $valid = $item->permission === 'debt-collect-leader' || $item->permission == 'recover';
                } elseif(Role::find($user->role_id)->name === 'debt-lawyer-ext'
                    && ($item->name === 'debtCollectLeaderAnalytic' || $item->name === 'debtCollectLeaderAnalyticLetters')) {
                    $valid = $item->name === 'debtCollectLeaderAnalytic' || $item->name === 'debtCollectLeaderAnalyticLetters';
                }

                // if($item->permission && !$user->hasPermission($item->permission)) $valid = false;

                //check user status
                if( $item->user_status ){
                    $status = explode(',', $item->user_status);

                    if(!in_array($user->status, $status))
                        $valid = false;
                }

                if( $item->name=='reports_all' && $user->hasRole('kyc') && !$user->hasRole('admin') ){
                    $valid = false;
                }

                /* if($item->name=='graph' && !$user->hasRole('admin') ){
                    $valid = false;
                }*/
                //disabled affiliates

                if($item->denied_affiliate == 1){
                    $partner = Partner::find($user->id);
                    if($company = $partner->company){
                        if(!is_null($company->parent_id)){
                            $valid = false;
                        }
                    }
                }

                //check route exists
                if($item->route != null && !Route::has($item->route))
                    $valid = false;

                if($valid) {
                    //Caption
                    $item->caption = __($type.'/menu.' . $item->name);

                    //Route
                    if($item->route)
                        $item->link = localeRoute($item->route, $item->params).$item->hash;

                    //Menu item activity
                    if($item->route == $route) $item->active = true;
                    else $item->active = false;
                }else
                    $menu->forget($index);
            }

            //Cache::put(app()->getLocale().'.'.$cache, $menu, 900);
        }


        return view($view, compact('menu'));
    }


    /**
     * @param $items
     * @param int $level
     * @return Collection
     */
    public static function build($items, $level = 0){
        $menu = collect();

        foreach($items as $item){
            $item->level = $level;
            $menu->push($item);

            if($item->children)
                $menu = $menu->merge(self::build($item->children, $level + 1));
        }

        return $menu;
    }
}
