<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\FileHelper;

use Illuminate\Database\Eloquent\SoftDeletes;

class CollectorTransaction extends Model
{

    use SoftDeletes;
    
    protected $fillable = [
        'collector_contract_id', 'type', 'content'
    ];

    public function getContentAttribute($content)
    {
        if($this->type !== 'photo') {
            return $content;
        }

        return FileHelper::url($content);
    }
}
