<?php

namespace App\Console\Commands;

use App\Helpers\EncryptHelper;
use App\Models\ContractRoyxatExport;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractsRoyxatExportCommand extends Command
{
    protected $signature = 'contracts:royxat-export';

    protected $description = 'Export contracts to Royxat service';

    public function handle()
    {
        try {
            $client = new Client([
                'base_uri' => config('test.royxat.url'),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Token' => config('test.royxat.token')
                ]
            ]);

            DB::table('contracts as c')
                ->selectRaw("convert(c.id, char) as credit_id,
                                       case
                                           when c.status = 3 or c.status = 4
                                               then 'problem'
                                           when c.status = 9
                                               then 'closed'
                                           when c.status = 5
                                               then 'closed'
                                           end             as credit_status,
                                       c.expired_days      as overdue_days")
                ->whereRaw('exists(select * from contract_royxat_exports cre where c.id = cre.contract_id)')
                ->orderBy('c.id')
                ->chunk(400, function ($royxat_debtors) use ($client) {
                    try {
                        $client->request('POST', 'credits/mass/update', [
                            'json' => [
                                'credits' => $royxat_debtors
                            ]
                        ]);
                    } catch (BadResponseException $exception) {
                        Log::channel('royxat')->error(__METHOD__, [
                            'code' => $exception->getCode(),
                            'body' => $exception->getResponse()->getBody()->getContents()
                        ]);

                        exit;
                    }
                });

            DB::table('contracts as c')
                ->selectRaw("convert(c.id, char)                               as credit_id,
                                    convert(c.total, integer)                         as amount,
                                    c.period                                          as duration,
                                    concat(u.name, ' ', u.surname, ' ', u.patronymic) as name,
                                    bp.passport_number                                as passport,
                                    bp.inn                                            as tin,
                                    case
                                        when c.status = 3 or c.status = 4
                                            then 'problem'
                                        end                                           as credit_status,
                                    date_format(c.confirmed_at, '%Y-%m-%d')           as contract_date,
                                    c.expired_days                                    as overdue_days,
                                    u.birth_date                                      as birth_date,
                                    bp.pinfl                                          as pinfl")
                ->join('users as u', 'c.user_id', '=', 'u.id')
                ->join('buyer_personals as bp', 'bp.user_id', '=', 'u.id')
                ->whereRaw('c.status = 3
                         and c.expired_days > 60
                         and bp.pinfl is not null
                         and bp.inn is not null
                         and not exists(select * from contract_royxat_exports cre where c.id = cre.contract_id)')
                ->orderBy('c.id')
                ->chunk(300, function ($credits) use ($client) {
                    $insert_debtors = [];

                    foreach ($credits as $credit) {
                        $credit->passport = EncryptHelper::decryptData($credit->passport);
                        $credit->tin = EncryptHelper::decryptData($credit->tin);
                        $credit->pinfl = EncryptHelper::decryptData($credit->pinfl);
                        array_push($insert_debtors, [
                            'contract_id' => $credit->credit_id,
                            'status' => 3,
                            'expired_days' => $credit->overdue_days
                        ]);
                    }

                    try {
                        $client->request('POST', 'credits/mass/create', [
                            'json' => [
                                'credits' => $credits
                            ]
                        ]);

                        ContractRoyxatExport::insert($insert_debtors);
                    } catch (BadResponseException $exception) {
                        Log::channel('royxat')->error(__METHOD__, [
                            'code' => $exception->getCode(),
                            'body' => $exception->getResponse()->getBody()->getContents()
                        ]);

                        exit;
                    }
                });
        } catch (\Exception $exception) {
            Log::channel('royxat')->error(__METHOD__, [
                'code' => $exception->getCode(),
                'body' => $exception->getMessage()
            ]);

            exit;
        }
    }
}
