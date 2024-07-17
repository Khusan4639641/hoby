<?php

namespace App\Models\DebtCollect;

use App\Helpers\FileHelper;
use App\Models\Buyer;
use App\Models\File;
use App\Models\V3\District;
use App\Scopes\DebtCollectorScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtCollectDebtorAction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'content',
        'debtor_id'
    ];

    public function files()
    {
        return $this->hasMany(File::class, 'element_id')->where('model', 'debt-collect-debtor-action');
    }

}
