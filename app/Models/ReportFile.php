<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ReportFile extends Model
{
    protected $table = 'report_files';

    const STATUS_CREATED = 0;
    const STATUS_COMPLETE = 1;
    const STATUS_FAILED = 2;

    protected $fillable = [
        'user_id', 'report', 'period', 'start_date', 'end_date', 'status',
        //'file'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at'
    ];

    public static function getReportStates()
    {
        return [
            self::STATUS_CREATED => 'В процессе',
            self::STATUS_COMPLETE => 'Завершен',
            self::STATUS_FAILED => 'Ошибка'
        ];
    }

    public static function getReportPeriods()
    {
        return [
            'last_day' => 'Текущий день',
            'last_week' => 'Последняя неделя',
            'last_month' => 'Последний месяц',
            'last_half_year' => 'Последние полгода',
            'custom' => 'Произвольный период'
        ];
    }

    public static function getReportTypes()
    {
        return [
            'orders' => [
                'title' => 'Бухгалтерия',
                'exportModel' => 'App\Exports\OrdersExport',
                'filename' => 'orders_{}.csv'
            ],
            'ordersCancel' => [
                'title' => 'Бухгалтерия c отмененными',
                'exportModel' => 'App\Exports\OrdersCancelExport',
                'filename' => 'orders_cancel_{}.csv'
            ],
            'ordersCancelNew' => [
                'title' => 'Бухгалтерия c отмененными (новая)',
                'exportModel' => 'App\Exports\OrdersCancelNewExport',
                'filename' => 'orders_cancel_new_{}.csv'
            ],
            'payments' => [
                'title' => 'Списания',
                'exportModel' => 'App\Exports\PaymentsExport',
                'filename' => 'payments_{}.csv'
            ],
            'history' => [
                'title' => 'Пополнения',
                'exportModel' => 'App\Exports\HistoryExport',
                'filename' => 'history_{}.csv'
            ],
            'transactions' => [
                'title' => 'Транзакции',
                'exportModel' => 'App\Exports\TransactionsExport',
                'filename' => 'transactions_{}.csv'
            ],
            'paymentFill' => [
                'title' => 'Пополнения/Списания',
                'exportModel' => 'App\Exports\PaymentFillExport',
                'filename' => 'payment_fill_{}.csv'
            ],
            'contracts' => [
                'title' => 'Договора',
                'exportModel' => 'App\Exports\ContractsExport',
                'filename' => 'contracts_{}.csv'
            ],
            'delays' => [
                'title' => 'Просрочка',
                'exportModel' => 'App\Exports\DelayKycExport',
                'filename' => 'delays_{}.csv'
            ],
            'delaysEx' => [
                'title' => 'Просрочка расширенная',
                'exportModel' => 'App\Exports\DelayExExport',
                'filename' => 'delaysEx_{}.csv'
            ],
            'paymentDate' => [
                'title' => 'Дата погашения',
                'exportModel' => 'App\Exports\PaymentDateExport',
                'filename' => 'paymentDate_{}.csv'
            ],
            'vendorsFull' => [
                'title' => 'Общий отчет по продажам',
                'exportModel' => 'App\Exports\VendorsFullExport',
                'filename' => 'vendorFull_{}.csv'
            ],
            'vendorsFillial' => [
                'title' => 'Детальный отчет по продажам',
                'exportModel' => 'App\Exports\VendorsFillialExport',
                'filename' => 'vendorFillial_{}.csv'
            ],
            'bonus' => [
                'title' => 'Начисление бонусов (контракты)',
                'exportModel' => 'App\Exports\BonusExport',
                'filename' => 'bonus_{}.csv'
            ],
            'bonusClients' => [
                'title' => 'Начисление бонусов (клиенты)',
                'exportModel' => 'App\Exports\BonusClientsExport',
                'filename' => 'bonus-clients_{}.csv'
            ],
            'detailedContracts' => [
                'title' => 'Детальные договора',
                'exportModel' => 'App\Exports\DetailedContractsExport',
                'filename' => 'detailed-contracts_{}.csv'
            ],
            'debtors' => [
                'title' => 'Задолжники больше 60 дней',
                'exportModel' => 'App\Classes\Reports\Exports\DebtorsExport',
                'filename' => 'debtors_{}.csv',
            ]
        ];
    }

    public function reportFile() {
        return $this->hasOne(File::class, 'element_id')->where('model', 'report-file')->where('type', 'report');
    }
}
