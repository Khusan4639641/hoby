<?php

namespace App\Models;

use App\Helpers\EncryptHelper;
use Illuminate\Database\Eloquent\Model;

class CardPnfl extends Model {

    protected $table = 'cards_pnfl';


    const CARD_ACTIVE = 1;
    const CARD_INACTIVE = 0;
    const CARD_DELETED = 2;


    public function pnflContract() {
        return $this->hasOne(CardPnflContract::class, 'user_id', 'user_id');
    }

    public function pnflContractFull() {
        return $this->hasOne(CardPnflContract::class, 'user_id', 'user_id')->whereNotNull('contract_id')->select('id','user_id','contract_id','clientId');
    }
}
