<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountBalanceHistory;
use App\Models\AccountCBU;
use App\Models\AccountingEntry;
use App\Models\AccountingEntryCBU;
use App\Models\AccountParameter;
use App\Models\Contract;
use App\Services\MFO\AccountBalanceHistoryService;
use App\Services\MFO\AccountingEntryService;
use App\Services\MFO\AccountService;
use App\Services\MFO\MFOPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MFOAccountEntryModifier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account-entries:modify {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Modify accounts and entries when contract activated';

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
        $date = $this->option('date');
        if(!strtotime($date)){
            $this->error('Date option not provided (--date=YYYY-MM-DD)');
            return 0;
        }
        $this->alert('ACCOUNT & ENTRIES MODIFY ('.$date.')');

        $contracts = Contract::query()
            ->select('*','confirmed_at AS conf_at')
            ->with('order')
            ->whereIn('status',[1,3,4,9])
            ->where('general_company_id','=',3)
            ->whereDate('confirmed_at','=',$date)
            ->whereNull('cancel_reason')
            ->get();
        $success = 0;
        $fails = 0;
        $totals = count($contracts);
        $this->info('FOUND: '.$totals);
        if($totals > 0){
            $account_service = new AccountService();
            $account_entry_service = new AccountingEntryService();

            foreach ($contracts as  $key => $contract) {
                try {
                    if(!$this->isAccountsOpened($contract->id)){
                        $account_service->init($contract->id);
                        Log::channel('mfo_account')->info('MFOAccountEntryModifier->Account->INIT: '.$contract->id);
                    }
                    if(!$this->isEntriesInitialized($contract->id)){
                        $account_entry_service->init($contract->id,$contract->conf_at);
                        Log::channel('mfo_account')->info('MFOAccountEntryModifier->Entry->INIT: '.$contract->id);
                        $this->changeBalanceHistories($contract,false);
                        $this->changeBalanceHistories($contract,true);
                    }
                    $success++;
                }catch (\Exception $exception){
                    Log::channel('mfo_account_errors')->info('MFOAccountEntryModifier->error: '.$exception->getMessage());
                    $fails ++;
                }
            }
        }
        $this->info('TOTAL: '.$totals.' SUCCESS: '.$success.' FAILS: '.$fails);
        return 0;
    }

    private function isAccountsOpened(int $contract_id) : bool
    {
        $account_parameters = AccountParameter::query()->where('contract_bind','=',1)->get();
        if($account_parameters){
            $accounts = Account::query()->where('contract_id','=',$contract_id)->count();
            $accounts_cbu = AccountCBU::query()->where('contract_id','=',$contract_id)->count();
            if(count($account_parameters) == $accounts && count($account_parameters) == $accounts_cbu){
                return true;
            }
        }
        return false;
    }

    private function isEntriesInitialized(int $contract_id) : bool
    {
        $entries = AccountingEntry::query()
                            ->where('contract_id','=',$contract_id)
                            ->where('status','=',AccountingEntry::STATUS_ACTIVE)
                            ->where('destination_code','=','1007')
                            ->count();
        $entries_cbu = AccountingEntryCBU::query()
                                    ->where('contract_id','=',$contract_id)
                                    ->where('status','=',AccountingEntry::STATUS_ACTIVE)
                                    ->where('destination_code','=','1007')
                                    ->count();
        if($entries && $entries_cbu){
            return true;
        }
        return false;
    }

    private function changeBalanceHistories(Contract $contract,$is_cbu = false)
    {
        $ac_service = new AccountingEntryService;
        $model = $ac_service->getAccountingEntryModel($is_cbu);
        $entries = $model->where('contract_id','=',$contract->id)
            ->where('status','=',AccountingEntry::STATUS_ACTIVE)
            ->where('destination_code','=','1007')
            ->get();
        if($entries){
            $history_service = new AccountBalanceHistoryService();
            $no_bind_masks = AccountParameter::query()->where('contract_bind','=',0)->pluck('mask')->toArray();
            foreach ($entries as $entry) {
                $debit_account = AccountService::getAccountModel($entry->debit_account,in_array(substr($entry->debit_account,0,5),$no_bind_masks) ? null : $contract->id)->first();
                if($debit_account){
                    $debit_account_param = AccountParameter::query()->where('mask',$debit_account->getMask())->first();
                    $debit_histories = $history_service->getModel($is_cbu)->where('account_id','=',$debit_account->id)->where('operation_date','>',$contract->conf_at)->get();
                    if($debit_histories){
                        foreach ($debit_histories as $debit_history) {
                            $debit_account_balance = AccountService::calculateBalanceAmount($debit_history->balance,$entry->amount,$debit_account_param->type);
                            $debit_history->balance = $debit_account_balance;
                            $debit_history->save();
                        }
                    }
                }

                $credit_account = AccountService::getAccountModel($entry->credit_account,in_array(substr($entry->credit_account,0,5),$no_bind_masks) ? null : $contract->id)->first();
                if($credit_account){
                    $credit_account_param = AccountParameter::query()->where('mask',$credit_account->getMask())->first();
                    $credit_histories = $history_service->getModel($is_cbu)->where('account_id','=',$credit_account->id)->where('operation_date','>',$contract->conf_at)->get();
                    if($credit_histories){
                        foreach ($credit_histories as $credit_history) {
                            $credit_account_balance = AccountService::calculateBalanceAmount($credit_history->balance,$entry->amount,$credit_account_param->type,'credit');
                            $credit_history->balance = $credit_account_balance;
                            $credit_history->save();
                        }
                    }
                }

            }
        }
    }
}
