<?php

namespace App\Services\MFO;

use App\Models\Wallet;
use App\Models\WalletMfo;
use App\Models\WalletPartner;
use App\Models\WalletSolution;
use Illuminate\Database\Eloquent\Builder;

class WalletService
{
    private array $prefixes;
    private int $currency_code;

    const TYPE_BUYER = 'buyer';
    const TYPE_PARTNER = 'partner';
    const TYPE_MFO = 'mfo';
    const TYPE_SOLUTION = 'solution';

    public function __construct()
    {
        $this->prefixes = [
            'buyer' => 22616,
            'partner' => 32616,
            'solution' => 42616,
            'mfo' => 52616,
        ];
        $this->currency_code = '000';
    }

    public function getWallet(int $user_id,string $type) : Wallet
    {
        $wallet = $this->find($type,$user_id);
        if(!$wallet){
            $this->getModel($type)->create([
                'user_id' => $user_id,
                'type' => $type,
                'account' => $this->generateWallet($user_id,$type)
            ]);
            $wallet = $this->find($type,$user_id);
        }
        return $wallet;
    }

    public function find(string $type,int $user_id = null,string $account = null)
    {
        $model = $this->getModel($type);
        if($user_id){
            $model = $model->where('user_id',$user_id);
        }
        if($account){
            $model = $model->where('account',$account);
        }
        return $model->first();
    }

    public function getModel(string $type) : Builder
    {
        switch ($type){
            case 'mfo':
                $model = WalletMfo::query();
                break;
            case 'solution':
                $model = WalletSolution::query();
                break;
            case 'partner':
                $model = WalletPartner::query();
                break;
            default:
                $model = Wallet::query();
                break;
        }
        return $model;
    }

    public function getModelOld(string $type) : Builder
    {
        if($type == 'mfo'){
            $model = WalletMfo::query();
        }
        elseif($type == 'solution'){
            $model = WalletSolution::query();
        }
        elseif($type == 'partner'){
            $model = WalletPartner::query();
        }
        elseif($type == 'buyer'){
            $model = Wallet::query();
        }else{
            throw new \Exception('Invalid type provided');
        }
        return $model;
    }

    public function generateWallet($user_id,string $type = 'buyer') : string
    {
        return $this->getPrefix($type).$this->currency_code.$this->getUniqueClientNumberWithUserId($user_id).$this->getIndexNumber($type);
    }

    private function getIndexNumber(string $type) : string
    {
        $nnn = '001';
        $last_wallet = $this->getModel($type)->orderBy('id','DESC')->first();
        if($last_wallet){
            $last_wallet_nnn = substr($last_wallet->account,-3);
            if((int) $last_wallet_nnn < 999){
                $nnn = str_pad((int)$last_wallet_nnn + 1,3,'0',STR_PAD_LEFT);
            }
        }
        return $nnn;
    }

    private function getUniqueClientNumberWithUserId(int $user_id) : string
    {
        return '6'.str_pad($user_id,8,'0',STR_PAD_LEFT);
    }

    private function getPrefix($type = 'buyer') : string
    {
        return array_key_exists($type,$this->prefixes) ? $this->prefixes[$type] : '';
    }
}
