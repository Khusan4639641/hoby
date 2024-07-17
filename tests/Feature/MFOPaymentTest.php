<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\Contract;
use App\Services\MFO\AccountingEntryService;
use App\Services\MFO\AccountService;
use App\Services\MFO\MFOPaymentService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MFOPaymentTest extends TestCase
{
    public function test_create_wallets_and_transactions()
    {
        $contract = Contract::query()->find(122);
        $service = new MFOPaymentService();
        $result = $service->init($contract);
        $this->assertEquals(true,$result);
    }

    public function test_create_accounts_and_entries_on_activate_contract()
    {
        $account_service = new AccountService();
        $account_entry_service = new AccountingEntryService();

        $account_service_result = $account_service->init(122);
        $account_entry_service_result = $account_entry_service->init(122);
        $this->assertNull($account_service_result);
        $this->assertNull($account_entry_service_result);
    }

    public function test_create_entry_without_debt()
    {
        $service = new AccountingEntryService();
        $result = $service->createEntryWithoutDebt(122,1000);
        $this->assertNull($result);
    }

    public function test_create_entry_with_debt()
    {
        $service = new AccountingEntryService();
        $result = $service->createEntryWithDebt(122,1500);
        $this->assertNull($result);
    }

    public function test_create_entry_reserve()
    {
        $service = new AccountingEntryService();
        $result = $service->createEntryReserve(262);
        $this->assertNull($result);
    }

    public function test_contract_cancel()
    {
        $contract = Contract::query()->find(122);
        $service = new MFOPaymentService();
        $res = $service->cancelTransactionCheckSms($contract);
        print_r($res);
        $this->assertIsArray($res);
    }

    public function test_generate_nibbd_for_buyer()
    {
        $buyer = Buyer::query()->find(2);
        $result = AccountService::generateNIBBDForBuyer($buyer);
        $this->assertNull($result);
    }

    public function ascii()
    {
        $file = File::get(public_path('M0609813.002'));
        $data = explode("\r\n",$file);
        $result = [];
        foreach ($data as $key => $string){
            try {
                //START
                $string = str_replace('Í','I',$string);
                $string = str_replace('Ў','У',$string);
                $string = str_replace('Ғ','Г',$string);
                $string = str_replace('Қ','К',$string);
                $content = $string;
                $content = iconv("UTF-8", "Windows-1251", ($content));
                $divider = chr(35);
                $result_arr = explode($divider,$content);
                $replacer = $result_arr[2];
                unset($result_arr[2]);

                $data = self::something(implode($result_arr));
                $result[] = str_replace('#'.$replacer,'#'.$data,$string).PHP_EOL;
            }
            catch (\Exception $exception){
                //
                File::append(public_path('error_chars.txt'),$string.PHP_EOL);
            }
        }
        File::append(public_path('newfile.002'),implode($result));
    }

    public static function something($string)
    {
        $result = 0;
        for ($i = 0; $i < strlen($string); $i++) {
            $result += ord($string[$i]);
        }
        return $result;
    }
    public function test_generate_control_key()
    {
        //56718 000 Х 05570410 001
        $mfo_nibbd = '05570410';
        $mask = '56718';
        $currency_code = '000';
        $user_nibbd = '05570410';
        $index_number = '001';
        $service = new AccountService();
        $res = $service->calculateControlKey($mfo_nibbd.$mask.$currency_code.$user_nibbd.$index_number);
        print_r($res);
        $this->assertIsInt($res);
    }
}
