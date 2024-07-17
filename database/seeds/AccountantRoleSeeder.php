<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AccountantRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::updateOrCreate([
            'name' => 'accountant',
        ], [
            'display_name' => 'Бухгалтер',
            'description' => 'Бухгалтер. Имеет доступ к страницам "Сопоставление счетов" и "Плоские файлы".'
        ]);

        Permission::insertOrIgnore([
            [
                'name' => 'V3AdminAccountsIndex',
                'display_name' => 'МФО Счета',
                'description' => 'Получение коллекции МФО счетов',
                'route_name' => 'V3AdminAccountsIndex',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'V3AdminAccountsBalances',
                'display_name' => 'Остатки балансов',
                'description' => 'Получение коллекции остатков баланса',
                'route_name' => 'V3AdminAccountsBalances',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'V3AdminAccountsCreateBalanceHistoryRecord',
                'display_name' => 'Создание записи в истории баланса',
                'description' => 'Создание записи в истории баланса с указанной суммой и датой',
                'route_name' => 'V3AdminAccountsCreateBalanceHistoryRecord',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'V3AdminAccountsUpdateBalanceHistoryRecord',
                'display_name' => 'Обновление записи в истории баланса',
                'description' => 'Обновление записи в истории баланса с указанной суммой',
                'route_name' => 'V3AdminAccountsUpdateBalanceHistoryRecord',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'V3AdminAccountsDeleteBalanceHistoryRecord',
                'display_name' => 'Удаление записи в истории баланса',
                'description' => 'Удаление записи в истории баланса по заданному id',
                'route_name' => 'V3AdminAccountsDeleteBalanceHistoryRecord',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'V3AdminAccountsCalculateAllBalances',
                'display_name' => 'Расчет остатков баланса',
                'description' => 'Расчет остатков баланса по всем МФО счетам',
                'route_name' => 'V3AdminAccountsCalculateAllBalances',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'V3AdminAccountsCalculateBalance',
                'display_name' => 'Расчет остатков конкретного баланса',
                'description' => 'Расчет остатков конкретного баланса по заданному id',
                'route_name' => 'V3AdminAccountsCalculateBalance',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'V3AdminAccountsCalculateBalancesProcessStatus',
                'display_name' => 'Статус процесса расчета остатков баланса',
                'description' => 'Получение статуса процесса расчета остатков баланса по заданному id процесса',
                'route_name' => 'V3AdminAccountsCalculateBalancesProcessStatus',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        $permissions = Permission::whereIn('name', [
            'V3AdminAccountsIndex',
            'V3AdminAccountsBalances',
            'V3AdminAccountsCreateBalanceHistoryRecord',
            'V3AdminAccountsUpdateBalanceHistoryRecord',
            'V3AdminAccountsDeleteBalanceHistoryRecord',
            'V3AdminAccountsCalculateAllBalances',
            'V3AdminAccountsCalculateBalance',
            'V3AdminAccountsCalculateBalancesProcessStatus',
            'reports.from-mko',
            'reports.mko-reports-list',
            'reports.mko-reports-sent',
            'reports.mko-reports-error'
        ])->get();

        $insertables = [];
        foreach ($permissions as $permission) {
            $insertables[] = [
                'permission_id' => $permission->id,
                'role_id' => $role->id
            ];
        }
        DB::table('permission_role')->insertOrIgnore($insertables);
    }
}
