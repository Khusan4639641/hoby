<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerSetting extends Model {

    public function getUseNdsAttribute() {
        return $this->nds ? __('app.yes') : __('app.no');
    }
}
