<?php

namespace App\Models\DebtCollect;

use Illuminate\Database\Eloquent\Model;

class AddedActionHistory extends Model
{
    protected $connection = 'sqlsrv';

    protected $table = 'dbo.AddedActionHistory';
}