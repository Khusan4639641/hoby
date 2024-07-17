<?php


namespace App\Http\Controllers\Web\Panel;


use \App\Http\Controllers\Core\KatmController as Controller;
use Illuminate\Support\Facades\Auth;

class KatmController extends Controller
{
    public function creditReport()
    {
        $additionalInformation = [
            [
                'title' => 'Все виды задолженности',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
            [
                'title' => 'Договора без единой просрочек',
                'opened' => [
                    'amount' => 1,
                    'sum' => 599000000,
                ],
                'closed' => [
                    'amount' => 3,
                    'sum' => 250000000
                ]
            ],
            [
                'title' => 'Просроченные проценты',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
            [
                'title' => 'Пересмотренные',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
            [
                'title' => 'Просрочки до 30 дней',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
            [
                'title' => 'Просрочки, переходящие на следующий месяц',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
            [
                'title' => 'Просрочки от 30 до 60 дней',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
            [
                'title' => 'Просрочки от 60 до 90 дней',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
            [
                'title' => 'Просрочки от 90 дней и более',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
            [
                'title' => 'Судебные',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
            [
                'title' => 'Списанные',
                'opened' => [
                    'amount' => 2,
                    'sum' => 1600000
                ],
                'closed' => [
                    'amount' => 4,
                    'sum' => 450000
                ]
            ],
        ];

        $buyer = 'physical'; // legal

        return view('panel.katm.credit-report', compact('buyer', 'additionalInformation'));
    }
}
