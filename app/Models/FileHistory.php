<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Self_;

class FileHistory extends Model
{
    protected $table = "files_history";

    protected $fillable = [
                'id',
                'file_id',
                'status',
                'user_id',
                'created_at',
            ];
}
