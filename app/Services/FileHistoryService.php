<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FileHistoryService
{
    const STATUS_UPLOAD   = 0;
    const STATUS_CANCELED = 1;
    const STATUS_SUCCESS  = 2;

    protected static $fileStatus = [
        'client_photo' => [ '3' => self::STATUS_UPLOAD, '2' => self::STATUS_CANCELED, '1' => self::STATUS_SUCCESS ],
        'act'          => [ '1' => self::STATUS_UPLOAD, '2' => self::STATUS_CANCELED, '3' => self::STATUS_SUCCESS ],
        'imei'         => [ '3' => self::STATUS_UPLOAD, '2' => self::STATUS_CANCELED, '1' => self::STATUS_SUCCESS ]
    ];

    public static function add($file_id, $status = 0 ) {
        FileHistory::create([
            'file_id' => $file_id,
            'status'  => $status,
            'user_id' => Auth::user()->id
        ]);
    }

    public static function changeStatus($contract_id, $status = 0, $type ) {
        if($file = File::where([
                        ['element_id', $contract_id],
                        ['type'      , $type]
                    ])->orderBy('id', 'DESC')->first()) {
            if ($status = self::checkStatus($type, $status)) {
                self::add($file->id, $status);
            }
        }
    }

    public static function checkStatus($statusType, $value) {
        foreach (self::$fileStatus[$statusType] as $key => $val) {
            if($key == $value) {
                return $val;
            }
        }
    }

    public static function show($contract_id) {
        return File::select(
            DB::raw('CONCAT(\''.config('test.sftp_file_server_domain').'\',"storage/",`path`) AS url'),
            DB::raw('CONCAT_WS(" ",users.surname, users.name) AS fullname'),
            DB::raw('files_history.status AS status_file'),
            DB::raw('files_history.created_at AS created_date'),
            'files_history.*',
            'users.*',
            'files.*')->where('files.element_id',$contract_id)
            ->join("files_history", "files_history.file_id", "=", "files.id")
            ->join("users", "files_history.user_id", "=", "users.id")
            ->orderBy('created_date','DESC')
            ->orderBy('files_history.id','DESC')
            ->get();
    }
}
