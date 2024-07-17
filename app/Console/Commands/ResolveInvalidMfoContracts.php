<?php

namespace App\Console\Commands;

use App\Models\AccountingEntry;
use App\Models\AccountParameter;
use App\Models\Contract;
use App\Services\MFO\AccountBalanceHistoryService;
use App\Services\MFO\AccountingEntryService;
use App\Services\MFO\AccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResolveInvalidMfoContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mfo-invalid-contracts:resolve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve invalid MFO contracts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->alert('RESOLVE INVALID CONTRACT AMOUNTS');
        $entries = AccountingEntry::query()
                            ->where('accounting_entries.destination_code','=','1007')
                            ->where('accounting_entries.status','=',AccountingEntry::STATUS_ACTIVE)
                            ->whereRaw(DB::raw("substr(accounting_entries.debit_account,1,5) = 91901"))
                            ->leftJoin('contracts','contracts.id','=','accounting_entries.contract_id')
                            ->whereRaw('accounting_entries.amount != contracts.total')
                            ->where('contracts.status','!=',Contract::STATUS_CANCELED)
                            ->get();
        $service = new AccountingEntryService();
        foreach ($entries as $entry){
            $contract = Contract::find($entry->contract_id);
            if($contract){
                $this->info('Contract ID: '.$contract->id);
                $service->resolveInvalidContracts($contract,$entry->amount);
                $service->resolveInvalidContractsForCbu($contract,$entry->amount);
            }
        }
        $this->resolveInvalidEntriesWithCancelledContracts();
        return 0;
    }

    private function resolveInvalidEntriesWithCancelledContracts()
    {
        $this->alert('RESOLVE INVALID ENTRIES');
        $contract_ids = DB::select("select distinct(contract_id) from accounting_entries where status = 1 except select distinct(contract_id) from accounting_entries where destination_code = '1007' and status = 1");
        foreach ($contract_ids as $item) {
            $this->changeStatusAndBalance($item->contract_id,false);
        }
        //CBU
        $contract_ids_cbu = DB::select("select distinct(contract_id) from accounting_entries_cbu where status = 1 except select distinct(contract_id) from accounting_entries_cbu where destination_code = '1007' and status = 1");
        foreach ($contract_ids_cbu as $c_item) {
            $this->changeStatusAndBalance($c_item->contract_id,true);
        }
    }

    private function changeStatusAndBalance(int $contract_id,bool $is_cbu)
    {
        try {
            $this->info('Contract ID: '.$contract_id);
            $accounting_service = new AccountingEntryService;
            $no_bind_masks = AccountParameter::query()->where('contract_bind','=',0)->pluck('mask')->toArray();
            $entries = $accounting_service->getAccountingEntryModel($is_cbu)->where('contract_id','=',$contract_id)->where('status','=',AccountingEntry::STATUS_ACTIVE)->get();
            if($entries){
                foreach ($entries as $entry) {
                    //1. Change status
                    $entry->status = AccountingEntry::STATUS_INACTIVE;
                    $entry->save();
                    //2. Change account balance
                    $debit_account = AccountService::getAccountModel($entry->debit_account,in_array(substr($entry->debit_account,0,5),$no_bind_masks) ? null : $entry->contract_id,null,$is_cbu)->first();
                    if($debit_account){
                        $this->updateHistory($debit_account->mask,$entry->amount,$debit_account->id,$entry->operation_date,$is_cbu,'debit');
                    }
                    $credit_account = AccountService::getAccountModel($entry->credit_account,in_array(substr($entry->credit_account,0,5),$no_bind_masks) ? null : $entry->contract_id,null,$is_cbu)->first();
                    if($credit_account){
                        $this->updateHistory($credit_account->mask,$entry->amount,$credit_account->id,$entry->operation_date,$is_cbu,'credit');
                    }
                    if($debit_account && $credit_account){
                        AccountService::updateBalance($credit_account,$debit_account,$entry->amount);
                    }
                }
            }
        }
        catch (\Exception $exception){
            $this->error($exception->getMessage());
        }
    }

    private function updateHistory(string $mask, float $amount, int $account_id,string $operation_date, $is_cbu = false,string $type = 'debit')
    {
        $history_service = new AccountBalanceHistoryService();
        $debit_account_param = AccountParameter::query()->where('mask',$mask)->first();
        $debit_histories = $history_service->getModel($is_cbu)->where('account_id','=',$account_id)->where('operation_date','>',$operation_date)->get();
        if($debit_histories){
            foreach ($debit_histories as $debit_history) {
                $debit_account_balance = AccountService::calculateBalanceAmount($debit_history->balance,$amount,$debit_account_param->type,$type);
                $debit_history->balance = $debit_account_balance;
                $debit_history->save();
            }
        }
    }
}
