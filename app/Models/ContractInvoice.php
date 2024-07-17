<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ContractInvoice extends Model {

    // protected $attributes = [
        // "id"                      => not null,     // id инвойса контракта                                                 // BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY (ID)
        // "user_id"                 => not null,     // id покупателя                                                        // BIGINT UNSIGNED NOT NULL
        // "contract_id"             => not null,     // id контракта                                                         // BIGINT UNSIGNED NOT NULL
        // "invoice_number"          => not null,     // номер инвойса                                                        // BIGINT UNSIGNED NOT NULL
        // // сумма задолженности, тут будут два типа: (данные берутся из таблицы collect_cost, поля fix и persent)
        // "fix_debt"                => null,         // деньги (фиксированная сумма по взысканию)                            // DECIMAL(16,2) NULL
        // "percent_debt"            => null,         // деньги (1% от всей суммы задолженности)                              // DECIMAL(16,2) NULL
        // "is_fix_type_invoice"     => 0,            // тип фиксированный или нет,  [0 или 1] (true или false)               // TINYINT(1) NOT NULL DEFAULT 0
        // "is_percent_type_invoice" => 0,            // тип 1% от всей суммы или нет, [0 или 1] (true или false)             // TINYINT(1) NOT NULL DEFAULT 0
        // "created_at"              => not null,     // время создания                                                       // TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        // "updated_at"              => not null,     // время изменения                                                      // TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    // ];

    protected $fillable = [
        'user_id',
        'contract_id',
        'invoice_number',
        'fix_debt',
        'percent_debt',
        'is_fix_type_invoice',
        'is_percent_type_invoice',
    ];

    public function getIsFixTypeInvoiceAttribute() {
        $var = (int) $this->attributes['is_fix_type_invoice'];
        return ( $var === 1 ) ? true : ( ( $var === 0 ) ? false : "is_fix_type_invoice is not boolean" ) ;
    }
    public function getIsPercentTypeInvoiceAttribute() {
        $var = (int) $this->attributes['is_percent_type_invoice'];
        return ( $var === 1 ) ? true : ( ( $var === 0 ) ? false : "is_percent_type_invoice is not boolean" ) ;
    }

    public function getCreatedAtAttribute(): string {
        return Carbon::parse( $this->attributes['created_at'] )->format( 'd.m.Y H:i:s' );
    }

    public function getUpdatedAtAttribute(): string {
        return Carbon::parse( $this->attributes['updated_at'] )->format( 'd.m.Y H:i:s' );
    }

    public function user() {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }

    public function contract() {
        return $this->belongsTo(Contract::class, 'id', 'contract_id');
    }
}
