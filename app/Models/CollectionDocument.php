<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionDocument extends Model
{
    protected $table = 'collection_documents';

    protected $fillable = [
        'contract_id',
        'user_id',
        'type',
        'file_link',
    ];

}
