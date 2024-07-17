<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\FileHelper;
use App\Helpers\UniversalHelper;
use App\Http\Controllers\Core\EmployeeController as Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $user = Auth::user();
        if($user->hasPermission('modify-employee')) {

            //Active orders
            /* $params = [
                'status__loe' => 1,
                'total_only' => 'yes'
            ]; */
            $counter['active'] = User::whereRoleIs('employee')->where('status', 1)->count() ; //$this->filter($params)['total'];

            //Credit orders
            /* $params = [
                'status' => 0,
                'total_only' => 'yes'
            ]; */
            $counter['inactive'] = User::whereRoleIs('employee')->where('status',  0)->count();

            //Credit orders
            /* $params = [
                'total_only' => 'yes'
            ]; */
            $counter['all'] = User::whereRoleIs('employee')->count(); //  $this->filter($params)['total'];

//            dd(localeRoute('historyPayment'));
            return view('panel.employee.index', compact('user', 'counter'));
        }else {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        }
    }


    /**
     * @param array $items
     * @return array
     */
    protected function formatDataTables ($items = []){

        $i = 0;
        $data = [];
        foreach ( $items as $item ) {

            if($item->avatar != null)
                $data[$i][] = '<div class="preview" style="background-image: url('.$item->avatar->preview.')"></div>';
            else
                $data[$i][] = '<div class="preview dummy"></div>';

            $data[$i][] = '<div class="title"><a href="'.localeRoute('panel.employees.edit', $item).'">'.$item->surname.' '.$item->name.' '.$item->patronymic.'</a></div>';

            $data[$i][] = '<div class="inner"><div class="role">'.@$item->role->display_name.'</div></div>';

            if($item->status == 0)
                $data[$i][] = '<button onclick="activate('.$item->id.')" class="btn btn-sm btn-success" type="button">'.__('app.btn_activate').'</button>';
            elseif($item->status == 1)
                $data[$i][] = '<button onclick="deactivate('.$item->id.')" class="btn btn-sm btn-archive" type="button">'.str_replace(' ', '&nbsp;', __('app.btn_deactivate')).'</button>';
            else
                $data[$i][] = '';

            $data[$i][] = '<button onclick="confirmDelete('.$item->id.')" type="button"
                                class="btn-delete">'.__('app.btn_delete').'</button>';
            $i++;
        }

        return parent::formatDataTables($data);
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function create()
    {
        $user = Auth::user();
        if($user->can('add', Employee::class)) {
            $roles = $this->roles()['data'];
            return view('panel.employee.create', compact('roles'));
        } else {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.employees.index'))->with('message', $this->result['response']['message']);
        }
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $result = $this->add( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = 'panel.index';
            else

                $route = 'panel.employees.create';
            return redirect(localeRoute($route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.employees.index'))->with( 'message', $result['response']['message'] );
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function show($id)
    {
        return redirect()->route('panel.employees.edit', $id);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     *
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $result = $this->detail($id);
        $result['data']['roles_list'] = $this->roles()['data'];

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.employees.index'))->with('message', $this->result['response']['message']);
        } else
            return view('panel.employee.edit', $result['data']);

    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Employee $employee
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request, Employee $employee)
    {
        $request->merge(['id' => $employee->id]);
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = localeRoute('panel.employees.index');
            else
                $route = localeRoute('panel.employees.edit', $employee);

            return redirect($route)
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.employees.index'))->with( 'message', $result['response']['message'] );
        }
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $result = $this->delete( $id );
        return redirect()->route( 'panel.employees.index' )->with( 'message', $result['response']['message'] );
    }
}
