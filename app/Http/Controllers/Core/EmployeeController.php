<?php

namespace App\Http\Controllers\Core;

use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Models\Employee;
use App\Models\Employee as Model;
use App\Models\KycHistory;
use App\Models\KycInfo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\Core\Auth\AuthController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\Collector;

class EmployeeController extends CoreController
{

    private $config;


    /**
     * Fields validator
     *
     * @param array $data
     * @return Validator
     */
    private $validatorRules = [
        'name' => ['required', 'string', 'max:255'],
        'surname' => ['required', 'string', 'max:255'],
        'patronymic' => ['required', 'string'],
        'phone' => ['required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/'/*, 'unique:users,phone'*/],
        'avatar' => ['image'],
        'role' => ['required', 'string', 'max:255']
    ];

    protected $roles = [
        'kyc', 'finance', 'call-center', 'sales', 'owner', 'recover', 'editor',
        'debt-collect-leader', 'debt-collect-curator', 'debt-collector', 'debt-collect-accounting',
        'ed_employee',
        'debt-collect-curator-extended',
        'debt-lawyer-ext'
    ];


    /**
     * Controller constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Config
        $this->config =  Config::get('Test.preview');

        //Eager load
        $this->loadWith = ['avatar'];
    }


    /**
     * @return array|bool|false|string
     */
    public function roles(){

        $roles = Role::wherein('name', $this->roles)->get();

        $this->result['data'] = $roles;
        $this->result['status'] = 'success';

        return $this->result();
    }


    /**
     * @param int $id
     * @return bool
     */
    private function getEmployeeRole(int $id){
        $user = User::find($id);
        $roles = $user->getRoles();
        foreach ($roles as $role) {
            if ($role != 'employee') {
                return Role::whereName($role)->first();
            }
        }
        return null;
    }


    public function filter( $params = [] ) {

        $employeesID = User::whereRoleIs('employee')->pluck('id')->toArray();

        if($employeesID) $params['id'] = $employeesID;

        return parent::filter( $params );
    }

    /**
     * @param array $params
     *
     * @return array|bool|false|string
     */
    public function list(array $params = []) {
        $user = Auth::user();
        $request = request()->all();
        if ( isset( $request['api_token'] ))
            $params = $request;
        unset($params['api_token']);

        //Filter elements
        $filter = $this->filter($params);

        foreach ($filter['result'] as $index => $item) {

            if ($user->can('detail', $item)){
                $item->status = $item->status_employee;
                $item->permissions = $this->permissions($item, $user);
                if($item->avatar) {
                    //Preparing preview
                    $previewPath = str_replace($item->avatar->name, 'preview_' . $item->avatar->name, $item->avatar->path);
                    $item->avatar->preview = Storage::url($previewPath)??Storage::url($item->avatar->path);
                }

                //Получаем роль, кроме роли 'employee'
                $item->role = $this->getEmployeeRole($item->id);
            }else
                $filter['result']->forget($index);

        }

        //Collect data
        $this->result['response']['total']  = $filter['total'];
        $this->result['status']             = 'success';

        //Format data
        if ( isset( $params['list_type'] ) && $params['list_type'] == 'data_tables' ) {
            $filter['result'] = $this->formatDataTables( $filter['result'] );
        }
        //Collect data

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }

    /**
     * @param $id
     * @param array $with
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    protected function single($id, $with = []) {
        $single = parent::single($id, array_merge($this->loadWith, []));
        $single->role = $this->getEmployeeRole($id);
        if($single->avatar) {
            //Preparing preview
            $previewPath = str_replace($single->avatar->name, 'preview_' . $single->avatar->name, $single->avatar->path);
            $single->avatar->preview = Storage::url($previewPath)??Storage::url($single->avatar->path);
        }
        return $single;
    }


    /**
     * Detail employees
     *
     * @param $id
     *
     * @return array|bool|false|string
     */
    public function detail($id) {
        $user = Auth::user();
        $employee = $this->single($id);

        if($employee){
            if ($user->can('detail', $employee)) {
                $this->result['status'] = 'success';
                $this->result['data']['employee'] = $employee;
            } else {
                //Error: access denied
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 403;
                $this->message('danger', __('app.err_access_denied'));
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }



    /**
     * Add news
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function add(Request $request) {

        Log::info('add employee');
        Log::info($request);

        $user = Auth::user();

        if($user->can('add', Model::class)) {
            $moreValidatorRules = [
                'password' => ['required', 'string', 'min:8', 'confirmed']
            ];
            $validator = $this->validator($request->all(), $this->validatorRules, $moreValidatorRules);

            if ($validator->fails() ) {
                //Error: validation error
                $this->result['status'] = 'error';
                $this->result['response']['errors'] = $validator->errors();
            }else{
                if(in_array($request->role, $this->roles)) {

                    $user = User::where('phone', $request->phone)->first();

                    if($user) {
                        $user->attachRole('employee');
                        $user->attachRole($request->role);
                        $user->role_id = Role::where('name', $request->role)->first()->id;
                        $user->status_employee = 1;
                        AuthController::generateApiToken($user);
                    }else{
                        //Create employee
                        $employee = new User();
                        $employee->name = $request->name;
                        $employee->surname = $request->surname;
                        $employee->patronymic = $request->patronymic;
                        $employee->phone = $request->phone;
                        $employee->password = Hash::make($request->password);
                        $employee->status_employee = 1;
                        $employee->doc_path = 1; //  файлы на новом сервере /// ??
                        $employee->save();


                        $kycinfo = new KycInfo();
                        $kycinfo->user_id = $employee->id;
                        $kycinfo->chat_id = $request->chat_id;
                        $kycinfo->status = $kycinfo->status = $request->telegram_status=='on' ? 1: 0;
                        $kycinfo->save();
                        Log::info('new kycinfo ' .$kycinfo->id);

                        KycHistory::insertHistory($employee->id,User::KYC_STATUS_CREATE);

                        //attach role
                        $employee->attachRole('employee');
                        $employee->attachRole($request->role);
                        $employee->role_id = Role::where('name', $request->role)->first()->id;

                        //Generate api token
                        AuthController::generateApiToken($employee);

                        //Save files
                        $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];

                        if (count($request->file()) > 0) {
                            $params = [
                                'files' => $request->file(),
                                'element_id' => $employee->id,
                                'model' => 'user'
                            ];
                            FileHelper::upload($params, $filesToDelete, true);

                            if($employee->avatar){
                                //Making preview
                                $previewName = 'preview_'.$employee->avatar->name;
                                $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                                $previewPath = $storagePath.str_replace($employee->avatar->name, $previewName, $employee->avatar->path);
                                $preview = new ImageHelper($storagePath.$employee->avatar->path);
                                $preview->resize($this->config['width'], $this->config['height']);
                                $preview->save($previewPath);
                            }
                        }
                    }

                    //Success: news item created
                    $this->result['status'] = 'success';
                    $this->message( 'success', __( 'panel/employee.txt_created' ) );
                }else {
                    //Error: access denied
                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 403;
                    $this->message( 'danger', __( 'app.err_access_denied' ) );
                }
            }
        }else {
            //Error: access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }

        return $this->result();
    }



    /**
     * Modify employee
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function modify(Request $request) {
        $user = Auth::user();
        $employee = Model::find($request->id);

        Log::info('modify employee');
        Log::info($request);

        if($user->can('modify', $employee)) {

            $moreValidatorRules = [];
            if(isset($request->password))
                $moreValidatorRules = [
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                ];
            $moreValidatorRules['phone'] = ['required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/', 'unique:users,phone,' . $employee->id];
            $validator = $this->validator($request->all(), $this->validatorRules, $moreValidatorRules);

            if ($validator->fails() ) {
                //Error: validation error
                $this->result['status'] = 'error';
                $this->result['response']['errors'] = $validator->errors();
            }else{

                if(in_array($request->role, $this->roles)) {

                    //Create employee
                    $employee->name = $request->name;
                    $employee->surname = $request->surname;
                    $employee->patronymic = $request->patronymic;
                    $employee->phone = $request->phone;

                    if(isset($request->password))
                        $employee->password = Hash::make($request->password);

                    if(!$kycinfo = KycInfo::where('user_id',$employee->id)->first() ){
                        $kycinfo = new KycInfo();
                        $kycinfo->user_id = $employee->id;
                    }
                    $kycinfo->chat_id = $request->chat_id;
                    $kycinfo->status = $request->telegram_status=='on' ? 1: 0;

                    $kycinfo->save();
                    Log::info('edit kycinfo ' .$kycinfo->id);

                    $employee->save();

                    //attach role
                    $currentRole = $this->getEmployeeRole($employee->id);
                    if($request->role != $currentRole) {
                        $employee->user->detachRole($currentRole);
                        $employee->user->attachRole($request->role);
                        $employee->role_id = Role::where('name', $request->role)->first()->id;
                    }

                    //Save files
                    $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];

                    if (count($request->file()) > 0) {
                        $params = [
                            'files' => $request->file(),
                            'element_id' => $employee->id,
                            'model' => 'user'
                        ];
                        FileHelper::upload($params, $filesToDelete, true);

                        if($employee->avatar){
                            //Making preview
                            $previewName = 'preview_'.$employee->avatar->name;
                            $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                            $previewPath = $storagePath.str_replace($employee->avatar->name, $previewName, $employee->avatar->path);
                            $preview = new ImageHelper($storagePath.$employee->avatar->path);
                            $preview->resize($this->config['width'], $this->config['height']);
                            $preview->save($previewPath);
                        }
                    }

                    //Success: news item created
                    $this->result['status'] = 'success';
                    $this->message( 'success', __( 'panel/employee.txt_updated' ) );
                }else {
                    //Error: access denied
                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 403;
                    $this->message( 'danger', __( 'app.err_access_denied' ) );
                }
            }
        }else {
            //Error: access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }

        return $this->result();
    }


    /**
     * @param Model $employee
     *
     * @return array|bool|false|string
     */
    public function activate(Model $employee){
        $user    = Auth::user();
        if($employee) {
            if ($user->can('modify', $employee)) {
                $employee->status_employee = 1;
                $employee->save();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/employee.txt_activated'));
            }else {
                $this->result['status'] = 'error';
                $this->message('danger', __('app.err_access_denied'));
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }


    /**
     * @param Model $employee
     *
     * @return array|bool|false|string
     */
    public function deactivate(Model $employee){
        $user    = Auth::user();
        if($employee) {
            if ($user->can('modify', $employee)) {
                $employee->status_employee = 0;
                $employee->save();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/news.txt_deactivated'));
            }else {
                $this->result['status'] = 'error';
                $this->message('danger', __('app.err_access_denied'));
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }



    /**
     * Delete news
     *
     * @param int $id
     * @return array|bool|false|string
     */
    public function delete(int $id){

        $user    = Auth::user();
        $employee = Employee::find( $id );
        if($employee) {
            if ($user->can('delete', $employee)) {

                if( $kyc = KycInfo::where('user_id',$employee->id)->first() ) $kyc->delete();

                /*if($employee->avatar)
                    FileHelper::delete($employee->avatar['id']);*/

                $employee->status_employee = 0;
                $employee->save();

                $employee->user->detachRoles($employee->user->getRoles());
                /*$employee->delete();*/

                $this->result['status'] = 'success';
                $this->message('success', __('panel/employee.txt_deleted'));
            } else {
                $this->result['status'] = 'error';
                $this->message('danger', __('app.err_access_denied'));
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }
}
