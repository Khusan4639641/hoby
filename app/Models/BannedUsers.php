<?php
namespace App\Models;
use App\Classes\Scoring\Interfaces\IBanedUsersState;
use App\Helpers\EncryptHelper;
use Illuminate\Database\Eloquent\Model;

class BannedUsers extends Model
{

    public $table = 'banned_users_list';

    public $timestamps = false;

}
