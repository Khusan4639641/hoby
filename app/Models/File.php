<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model {

    const TYPE_ACT = 'act';
    const TYPE_CANCEL_ACT = 'cancel_act';
    const TYPE_CONTRACT_PDF = 'contract_pdf';
    const TYPE_CLIENT_PHOTO = 'client_photo';
    const TYPE_IMAGE = 'image';
    const TYPE_IMEI = 'imei';
    const TYPE_LOGO = 'logo';
    const TYPE_PASSPORT = 'passport';
    const TYPE_PASSPORT_ADDRESS = 'passport_address';
    const TYPE_PASSPORT_FIRST_PAGE = 'passport_first_page';
    const TYPE_PASSPORT_SELFIE = 'passport_selfie';
    const TYPE_PASSPORT_WITH_ADDRESS = 'passport_with_address';
    const TYPE_REPORT = 'report';
    const TYPE_JSON = 'json';

    const MODEL_BUYER = "buyer";
    const MODEL_KATM_RECEIVED_REPORT = "katm-received-report";

//    Изображение подписи (image file)
    const TYPE_SIGNATURE = 'signature';
//    Подписанный контракт (*.html file)
    const TYPE_SIGNED_CONTRACT = 'signed_contract';

    public const TYPE_INVOICE = "invoice";
    public const TYPE_INVOICE_EXECUTE = "invoice-execute";
    public const TYPE_FOURTH_EXECUTE = "fourth-execute";

    public const MODEL_CONTRACTS_RECOVERY = "contracts-recovery";


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'element_id',
        'model',
        'type',
        'language_code',
        'path',
        'doc_path',
        'name',
        'user_id'
    ];

    public function getPreviewAttribute() {

        $previewPath = str_replace($this->attributes['name'], 'preview_' . $this->attributes['name'], $this->attributes['path']);
        if($this->attributes['doc_path'] == 0) {
            $previewPath = Storage::exists($previewPath) ? Storage::url($previewPath) : Storage::url($this->attributes['path']);
        } elseif ($this->attributes['doc_path'] == 1) {
            $previewPath = config("test.sftp_file_server_domain") . 'storage/' . $this->attributes['path'];
        }
        return $previewPath;
    }
    public function getGlobalPreviewAttribute() {
        return env('SFTP_FILE_SERVER_DOMAIN') . 'storage/' . $this->attributes['path'];
    }
}
