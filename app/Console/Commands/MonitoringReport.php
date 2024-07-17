<?php

namespace App\Console\Commands;

use App\Classes\Informer\ConsoleMessage;
use App\Classes\Informer\DailyTelegramMessage;
use App\Classes\Informer\Interfaces\IOLineMessage;
use App\Classes\Informer\Interfaces\MessageData;
use App\Classes\Informer\WarningTelegramMessage;
use App\Helpers\TelegramInformer;
use App\Facades\Monitoring as MonitoringFacade;
use App\Models\Contract;
use App\Models\Monitoring;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MonitoringReport extends Command implements IOLineMessage
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:report {--telegram} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View monitoring report';

    private $accountsCountMessage = 'Списания не соответствующие пополнению';
    private $contractsCountMessage = 'Контракты в которых не соответствуют списания';
    private $bonusAccountsCountMessage = 'Списания не соответствующие пополнению по бонусам';

    private $activeContractsExpiredDaysMessage = 'Просроченные дни по актмвным контрактам';
    private $partialDebtorsContractsExpiredDaysMessage = 'Просроченные дни по контрактам с частичной задолженностью';
    private $fullDebtorsContractsExpiredDaysMessage = 'Просроченные дни по контрактам с полной задолженностью';
    private $allContractsExpiredDaysMessage = 'Просроченные дни по всем контрактам';

    private $activeContractsCountMessage = 'Количество активных контрактов';
    private $partialDebtorsContractsCountMessage = 'Количество контрактов с частичной задолженностью';
    private $fullDebtorsContractsCountMessage = 'Количество контрактов с полной задолженностью';

    private $telegramToken;
    private $telegramChat;
    private $currentDate;
    private $yesterdayDate;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->telegramToken = env('MONITORING_REPORT_TELEGRAM_BOT_TOKEN');
        $this->telegramChat = env('MONITORING_REPORT_CHAT_ID');
        $this->currentDate = Carbon::now()->subHours(1);
        $this->yesterdayDate = Carbon::now()->subHours(1)->subDay(1);
        parent::__construct();
    }

    /**
     * Add param to monitoring storage if not exists value.
     * @param string $key
     * @param int $value
     * @return void
     */
    private function addParamIfNotExist(string $key, int $value)
    {
        $foundValue = Monitoring::getParam($key);
        if ($foundValue != $value) {
            Monitoring::addParam($key, $value);
        }
    }

    /**
     * Make warning report message to telegram.
     * @param int $currentValue
     * @param string $monitoringKey
     * @param string $message
     * @param bool $isForceSend
     * @return void
     */
    private function warningReport(int $currentValue, string $monitoringKey, string $message, bool $isForceSend)
    {
        $prevValue = Monitoring::getParam($monitoringKey);
        $this->addParamIfNotExist($monitoringKey, $currentValue);
        $informer = new TelegramInformer($this->telegramToken, $this->telegramChat);
        $telegramMessage = new WarningTelegramMessage($informer, $message, $prevValue, $currentValue);
        if ($isForceSend || $prevValue != $currentValue) {
            $telegramMessage->send();
        }
    }

    /**
     * Make text part of telegram report message.
     * @param int $currentValue
     * @param string $monitoringKey
     * @param string $message
     * @param bool $isForceSend
     * @return MessageData
     */
    private function dailyLineReport(int $currentValue, string $monitoringKey, string $message): MessageData
    {
        $prevValue = Monitoring::getDailyParam($monitoringKey, $this->yesterdayDate->format('Y-m-d'));
        $informer = new TelegramInformer($this->telegramToken, $this->telegramChat);
        return new WarningTelegramMessage($informer, $message, $prevValue, $currentValue);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isTelegram = $this->option('telegram');
        $isForce = $this->option('type') == 'force';
        $isDaily = $this->option('type') == 'daily';

        $accountsCount = MonitoringFacade::getAccountsCollection()->count();
        $contractsCount = MonitoringFacade::getContractsCollection()->count();
        $bonusAccountsCount = MonitoringFacade::getBonusAccountsCollection()->count();

        $activeContractsExpiredDays = MonitoringFacade::getExpiredDays(Contract::STATUS_ACTIVE);
        $partialDebtorsContractsExpiredDays = MonitoringFacade::getExpiredDays(Contract::STATUS_OVERDUE_30_DAYS);
        $fullDebtorsContractsExpiredDays = MonitoringFacade::getExpiredDays(Contract::STATUS_OVERDUE_60_DAYS);
        $allContractsExpiredDays = MonitoringFacade::getExpiredDays();

        $activeContractsCount = MonitoringFacade::getContractsCount(Contract::STATUS_ACTIVE);
        $partialDebtorsContractsCount = MonitoringFacade::getContractsCount(Contract::STATUS_OVERDUE_30_DAYS);
        $fullDebtorsContractsContractsCount = MonitoringFacade::getContractsCount(Contract::STATUS_OVERDUE_60_DAYS);

        $this->addParamIfNotExist(Monitoring::CONTRACTS_ACTIVE_EXPIRED_DAYS, $activeContractsExpiredDays);
        $this->addParamIfNotExist(Monitoring::CONTRACTS_PARTIAL_DEBTORS_EXPIRED_DAYS, $partialDebtorsContractsExpiredDays);
        $this->addParamIfNotExist(Monitoring::CONTRACTS_FULL_DEBTORS_EXPIRED_DAYS, $fullDebtorsContractsExpiredDays);
        $this->addParamIfNotExist(Monitoring::CONTRACTS_ALL_EXPIRED_DAYS, $allContractsExpiredDays);

        $this->addParamIfNotExist(Monitoring::CONTRACTS_STATUS_ACTIVE, $activeContractsCount);
        $this->addParamIfNotExist(Monitoring::CONTRACTS_STATUS_PARTIAL_DEBTOR, $partialDebtorsContractsCount);
        $this->addParamIfNotExist(Monitoring::CONTRACTS_STATUS_FULL_DEBTOR, $fullDebtorsContractsContractsCount);

        if ($isTelegram) {

            $this->warningReport($accountsCount, Monitoring::ACCOUNTS_DEBIT_AND_CREDIT_DIFFERENT, $this->accountsCountMessage, $isForce);
            $this->warningReport($contractsCount, Monitoring::CONTRACTS_DEBIT_DIFFERENT, $this->contractsCountMessage, $isForce);
            $this->warningReport($bonusAccountsCount, Monitoring::BONUS_ACCOUNTS_DEBIT_AND_CREDIT_DIFFERENT, $this->bonusAccountsCountMessage, $isForce);

            if ($isDaily) {
                $financeInformer = new TelegramInformer($this->telegramToken, $this->telegramChat);
                $financeTelegramMessage = new DailyTelegramMessage($financeInformer, $this->currentDate, 'Финансы');
                $financeTelegramMessage->addReport($this->dailyLineReport($accountsCount, Monitoring::ACCOUNTS_DEBIT_AND_CREDIT_DIFFERENT, $this->accountsCountMessage));
                $financeTelegramMessage->addReport($this->dailyLineReport($contractsCount, Monitoring::CONTRACTS_DEBIT_DIFFERENT, $this->contractsCountMessage));
                $financeTelegramMessage->addReport($this->dailyLineReport($bonusAccountsCount, Monitoring::BONUS_ACCOUNTS_DEBIT_AND_CREDIT_DIFFERENT, $this->bonusAccountsCountMessage));
                $financeTelegramMessage->send();

                $debitsInformer = new TelegramInformer($this->telegramToken, $this->telegramChat);
                $debitsTelegramMessage = new DailyTelegramMessage($debitsInformer, $this->currentDate, 'Просроченные дни');
                $debitsTelegramMessage->addReport($this->dailyLineReport($activeContractsExpiredDays, Monitoring::CONTRACTS_ACTIVE_EXPIRED_DAYS, $this->activeContractsExpiredDaysMessage));
                $debitsTelegramMessage->addReport($this->dailyLineReport($partialDebtorsContractsExpiredDays, Monitoring::CONTRACTS_PARTIAL_DEBTORS_EXPIRED_DAYS, $this->partialDebtorsContractsExpiredDaysMessage));
                $debitsTelegramMessage->addReport($this->dailyLineReport($fullDebtorsContractsExpiredDays, Monitoring::CONTRACTS_FULL_DEBTORS_EXPIRED_DAYS, $this->fullDebtorsContractsExpiredDaysMessage));
                $debitsTelegramMessage->addReport($this->dailyLineReport($allContractsExpiredDays, Monitoring::CONTRACTS_ALL_EXPIRED_DAYS, $this->allContractsExpiredDaysMessage));
                $debitsTelegramMessage->send();

                $statusesInformer = new TelegramInformer($this->telegramToken, $this->telegramChat);
                $statusesTelegramMessage = new DailyTelegramMessage($statusesInformer, $this->currentDate, 'Кол-во контрактов');
                $statusesTelegramMessage->addReport($this->dailyLineReport($activeContractsCount, Monitoring::CONTRACTS_STATUS_ACTIVE, $this->activeContractsCountMessage));
                $statusesTelegramMessage->addReport($this->dailyLineReport($partialDebtorsContractsCount, Monitoring::CONTRACTS_STATUS_PARTIAL_DEBTOR, $this->partialDebtorsContractsCountMessage));
                $statusesTelegramMessage->addReport($this->dailyLineReport($fullDebtorsContractsContractsCount, Monitoring::CONTRACTS_STATUS_FULL_DEBTOR, $this->fullDebtorsContractsCountMessage));
                $statusesTelegramMessage->send();
            }
        }

        $consoleMessage = new ConsoleMessage($this);

        $consoleMessage->addTitle('Отчётность по финаннсам');
        $consoleMessage->addValueMessage($this->accountsCountMessage, $accountsCount);
        $consoleMessage->addValueMessage($this->contractsCountMessage, $contractsCount);
        $consoleMessage->addValueMessage($this->bonusAccountsCountMessage, $bonusAccountsCount);

        $consoleMessage->addTitle('Отчётность по просроченным дням контрактов');
        $consoleMessage->addValueMessage($this->activeContractsExpiredDaysMessage, $activeContractsExpiredDays);
        $consoleMessage->addValueMessage($this->partialDebtorsContractsExpiredDaysMessage, $partialDebtorsContractsExpiredDays);
        $consoleMessage->addValueMessage($this->fullDebtorsContractsExpiredDaysMessage, $fullDebtorsContractsExpiredDays);
        $consoleMessage->addValueMessage($this->allContractsExpiredDaysMessage, $allContractsExpiredDays);

        $consoleMessage->addTitle('Отчётность по количеству контрактов');
        $consoleMessage->addValueMessage($this->activeContractsCountMessage, $activeContractsCount);
        $consoleMessage->addValueMessage($this->partialDebtorsContractsCountMessage, $partialDebtorsContractsCount);
        $consoleMessage->addValueMessage($this->fullDebtorsContractsCountMessage, $fullDebtorsContractsContractsCount);

        $consoleMessage->send();

        return 0;
    }

    public function send()
    {
    }
}
