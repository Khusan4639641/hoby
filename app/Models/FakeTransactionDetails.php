<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FakeTransactionDetails extends Model
{
    protected $table = 'fake_transaction_details';

    protected $fillable = [
                            'type',
                            'date',
                            'general_company_id',
                            'user_id',
                            'fake_transaction_id'
                        ];
}
