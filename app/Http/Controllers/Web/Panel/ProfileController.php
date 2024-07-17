<?php
namespace App\Http\Controllers\Web\Panel;


use App\Models\Employee;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use \App\Http\Controllers\Core\EmployeeController;
use Illuminate\View\View;

class ProfileController
{
    /**
     * @return array|mixed|null
     */
    public static function userInfo() {
        $user = Auth::user();
        $info = Cache::get('user.info.'.$user->id);

        if($info == null) {

            //Получаем покупателя
            $employeeController = new EmployeeController();
            $employee = $employeeController->detail($user->id)['data']['employee'];


            $info = [
                'phone'                 => $employee->phone,
                'fio'                   => $employee->fio,
                'role'                  => $employee->role->display_name
            ];


            Cache::put('user.info.'.$employee->id, $info, 900);
        }

        return $info;
    }


    /**
     * Aside user card
     *
     * @return Application|Factory|View
     */
    public static function card(){
        $info = self::userInfo();
        return view('templates/panel/parts/card', compact('info'));
    }
}
