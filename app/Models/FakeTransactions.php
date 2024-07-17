<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FakeTransactions extends Model
{
    protected $table = 'fake_transactions';

    protected $fillable = [
                            'type',
                            'date',
                            'general_company_id',
                            'amount'
                        ];
}
