@extends('templates.panel.app')

@section('title', $user->name . ' ' . $user->surname . ' ' . $user->patronymic)

@push('css')
    <style>
        .bg-teal {
            background-color: #8ec29a !important;
        }

        .bg-light-blue {
            background-color: #8E91BBFF !important;
        }

        .bg-light-red {
            background-color: #bc7a80 !important;
        }

        .text-title-light {
            color: #a4a4a4;
        }
    </style>
@endpush

@push('js')
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
@endpush

@section('content')


    <div class="row">

        @include('panel.monitoring.parts.links')

        @if($isTotalHaveDifferent || $user->different != 0 || count($notExistsDeposits) > 0)
            <div class="col-lg-12">
                <div class="alert alert-danger">
                    <h4>{{ __('Внимание!') }}</h4>
                    <ul class="mb-0">
                        @if($isTotalHaveDifferent)
                            <li>{{ __('Не соответствующие списания (по контракту, по плану, по транзакциям)') }}</li>
                        @endif
                        @if($user->different != 0)
                            <li>{{ __('Списания не соответствующие пополнению') }}</li>
                        @endif
                        @if(count($notExistsDeposits) > 0)
                            @foreach($notExistsDeposits as $contractID => $deposits)
                                <li>{{ __('В контракте под номером :contract отсутствует транзакция депозита', ['contract' => $contractID]) }}</li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>
        @endif

        <div class="col-lg-4">
            <h2 class="m-4">{{ __('Информация') }}</h2>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th>{{ __('Количество договоров') }}:</th>
                    <td class="text-right"> {{ $user->contracts_count }}</td>
                </tr>
                <tr>
                    <th>{{ __('Количество активных договоров') }}:</th>
                    <td class="text-right"> {{ $user->active_contracts_count }}</td>
                </tr>
                <tr>
                    <th>{{ __('Личный счёт') }}:</th>
                    <td class="text-right"> {{ number_format($user->personal_account, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Сумма депозитов') }}:</th>
                    <td class="text-right"> {{ number_format($user->deposit_sum, 2, ',', ' ') }}</td>
                </tr>
                </tbody>
            </table>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th>{{ __('Личный счёт бонусов') }}:</th>
                    <td class="text-right"> {{ number_format($user->bonuses, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Сумма бонусов') }}:</th>
                    <td class="text-right"> {{ number_format($user->bonuses_amount, 2, ',', ' ') }}</td>
                </tr>
                </tbody>
            </table>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th>{{ __('Лимит') }}:</th>
                    <td class="text-right"> {{ number_format($user->limit, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Баланс лимитов') }}:</th>
                    <td class="text-right"> {{ number_format($user->limit_balance, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Лимитов израсходовано') }}:</th>
                    <td class="text-right"> {{ number_format($user->limit - $user->limit_balance, 2, ',', ' ') }}</td>
                </tr>
                </tbody>
            </table>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th>{{ __('Общая сумма пополнений на личный счёт') }}:</th>
                    <td class="text-right"> {{ number_format($user->replenishments, 2, ',', ' ') }}</td>
                </tr>
                <tr class="text-title-light">
                    <th class="pl-4">{{ __('С платёжной системы') }}:</th>
                    <td class="text-right"> {{ number_format($user->payments_from_payment_system, 2, ',', ' ') }}</td>
                </tr>
                <tr class="text-title-light">
                    <th class="pl-4">{{ __('С платёжных карт') }}:</th>
                    <td class="text-right"> {{ number_format($user->payments_from_card, 2, ',', ' ') }}</td>
                </tr>
                <tr class="text-title-light">
                    <th class="pl-4">{{ __('С autopay') }}:</th>
                    <td class="text-right"> {{ number_format($user->payments_from_autopay, 2, ',', ' ') }}</td>
                </tr>
                </tbody>
            </table>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th>{{ __('Общая сумма списаний') }}:</th>
                    <td class="text-right"> {{ number_format($totalPaymentsDebit, 2, ',', ' ') }}</td>
                </tr>
                <tr class="text-title-light">
                    <th class="pl-4">{{ __('Сумма списаний ручным способом') }}:</th>
                    <td class="text-right"> {{ number_format($user->manual_debit, 2, ',', ' ') }}</td>
                </tr>
                <tr class="text-title-light">
                    <th class="pl-4">{{ __('Сумма списаний автоматическим способом') }}:</th>
                    <td class="text-right"> {{ number_format($user->auto_debit, 2, ',', ' ') }}</td>
                </tr>
                <tr class="text-title-light">
                    <th class="pl-5">{{ __('С л/с') }}:</th>
                    <td class="text-right"> {{ number_format($user->auto_debit_from_account, 2, ',', ' ') }}</td>
                </tr>
                <tr class="text-title-light">
                    <th class="pl-5">{{ __('С платёжных карт') }}:</th>
                    <td class="text-right"> {{ number_format($user->auto_debit_from_cards, 2, ',', ' ') }}</td>
                </tr>
                <tr class="text-title-light">
                    <th class="pl-5">{{ __('По PNFL') }}:</th>
                    <td class="text-right"> {{ number_format($user->auto_debit_from_pnfl, 2, ',', ' ') }}</td>
                </tr>
                </tbody>
            </table>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th>{{ __('Списание по контракту') }}:</th>
                    <td class="text-right {{ $isTotalHaveDifferent ? 'text-danger' : '' }}">{{ number_format($totalContractDebit, 2, ',', ' ') }}</td>
                </tr>

                <tr>
                    <th>{{ __('Суммма списаний по плану') }}:</th>
                    <td class="text-right {{ $isTotalHaveDifferent ? 'text-danger' : '' }}">{{ number_format($totalSchedulesDebit, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Сумма списаний по транзакциям') }}:</th>
                    <td class="text-right {{ $isTotalHaveDifferent ? 'text-danger' : '' }}"> {{ number_format($totalPaymentsDebit, 2, ',', ' ') }}</td>
                </tr>
                </tbody>
            </table>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th>{{ __('Списания не соответствующие пополнению') }}:</th>
                    <td class="text-right {{ $user->different != 0 ? 'text-danger' : '' }}"> {{ number_format($user->different, 2, ',', ' ') }}</td>
                </tr>
                </tbody>
            </table>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th>{{ __('Не соответствия по бонусам') }}:</th>
                    <td class="text-right {{ $user->bonuses - $user->bonuses_amount != 0 ? 'text-danger' : '' }}"> {{ number_format($user->bonuses - $user->bonuses_amount, 2, ',', ' ') }}</td>
                </tr>
                </tbody>
            </table>

        </div>
        <div class="col-lg-8">
            <h2 class="m-4">{{ __('История платежей') }}</h2>

            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#all">{{ __('Все проводки') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#timeline">{{ __('Таймлайн') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#bonus">{{ __('Бонусы') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#payments">{{ __('Платежи') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#debit">{{ __('Списания') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab"
                       href="#contracts">{{ __('Договора (Количество: :count)', ['count' => $contracts->count()]) }}</a>
                </li>

            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <div class="tab-pane container active" id="all">
                    <h4 class="m-2 mt-4 text-success font-weight-bold">{{ __('Все транзакции клиента') }}</h4>
                    @include('panel.monitoring.parts.payments-table', ['payments' => $payments])
                </div>
                <div class="tab-pane container fade" id="timeline">
                    <h4 class="m-2 mt-4 text-success font-weight-bold">{{ __('Таймлайн') }}</h4>

                    <ul class="nav nav-tabs mt-4">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab"
                               href="#payments-transactions">{{ __('Платёжные транзакции') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab"
                               href="#bonuses-transactions">{{ __('Бонусные транзакции') }}</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane container active"
                             id="payments-transactions">
                            @include('panel.monitoring.parts.timeline', ['timelinePayments' => $timelineOnlyPayments])
                        </div>
                        <div class="tab-pane container fade"
                             id="bonuses-transactions">
                            @include('panel.monitoring.parts.timeline', ['timelinePayments' => $timelineOnlyBonuses])
                        </div>
                    </div>
                </div>
                <div class="tab-pane container fade" id="bonus">
                    <h4 class="m-2 mt-4 text-success font-weight-bold">{{ __('Бонусы') }}</h4>
                    <div class="row">
                        <div class="col-lg-6">

                            <table class="table accounts-list dataTable no-footer mt-4">
                                <tbody>
                                <tr>
                                    <th>{{ __('Счёт бонусов (zcoin)') }}:</th>
                                    <td class="text-right">{{ number_format($user->bonuses, 2, ',', ' ') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Сумма начисленных бонусов') }}:</th>
                                    <td class="text-right">{{ number_format($paymentsBonuses->where('type', 'fill')->sum('amount'), 2, ',', ' ') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Сумма возвращенных бонусов') }}:</th>
                                    <td class="text-right">{{ number_format($paymentsBonuses->where('type', 'refund')->sum('amount'), 2, ',', ' ') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Сумма потраченных бонусов') }}:</th>
                                    <td class="text-right">{{ number_format($paymentsBonuses->where('type', 'upay')->sum('amount'), 2, ',', ' ') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Сумма всех бонусов') }}:</th>
                                    <td class="text-right">{{ number_format($paymentsBonuses->sum('amount'), 2, ',', ' ') }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @include('panel.monitoring.parts.bonuses-table', ['bonuses' => $paymentsBonuses])
                </div>
                <div class="tab-pane container fade" id="payments">
                    <h4 class="m-2 mt-4 text-success font-weight-bold">{{ __('Транзакции не по договорам') }}</h4>
                    @include('panel.monitoring.parts.payments-table', ['payments' => $paymentsReplenishments])
                </div>
                <div class="tab-pane container fade" id="debit">
                    <h4 class="m-2 mt-4 text-success font-weight-bold">{{ __('Транзакции по договорам') }}</h4>
                    @include('panel.monitoring.parts.payments-table', ['payments' => $paymentsDebit])
                </div>
                <div class="tab-pane container fade" id="contracts">

                    <ul class="nav nav-tabs mt-4">
                        @foreach($contracts as $i => $contract)
                            <li class="nav-item">
                                <a class="nav-link {{ $i == 0 ? 'active' : '' }}" data-toggle="tab"
                                   href="#contract{{ $contract->id }}">{{ __('Договор: :number', ['number' => $contract->id]) }}</a>
                            </li>
                        @endforeach
                    </ul>


                    <div class="tab-content">
                        @foreach($contracts as $i => $contract)
                            <div class="tab-pane container {{ $i == 0 ? 'active' : 'fade' }}"
                                 id="contract{{ $contract->id }}">

                                <h4 class="m-2 mt-4 text-success font-weight-bold">{{ __('Общие сведения по договору: :number', ['number' => $contract->id]) }}</h4>

                                <div class="row">
                                    <div class="col-lg-6">

                                        <table class="table accounts-list dataTable no-footer mt-4">
                                            <tbody>
                                            <tr>
                                                <td colspan="2"><a
                                                        href="{{ localeRoute('panel.contracts.show', $contract->id) }}">
                                                        {{ __('Посмотреть договор') }}</a></td>
                                            </tr>

                                            <tr>
                                                <th>{{ __('Депозит') }}:</th>
                                                <td class="text-right {{ isset($notExistsDeposits[$contract->id]) ? 'text-danger' : '' }}">{{ number_format($contract->deposit, 2, ',', ' ') }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Сумма к оплате') }}:</th>
                                                <td class="text-right">{{ number_format($contract->total, 2, ',', ' ') }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Долг') }}:</th>
                                                <td class="text-right">{{ number_format($contract->balance, 2, ',', ' ') }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Оплачено') }}:</th>
                                                <td class="text-right">{{ number_format($contract->total - $contract->balance, 2, ',', ' ') }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Статус') }}:</th>
                                                <td class="text-right">{{ $contract->status }}</td>
                                            </tr>
                                            </tbody>
                                        </table>

                                        @php
                                            $contractDebit = $contract->total - $contract->balance;
                                            $schedulesDebit = $contract->schedule->sum('total') - $contract->schedule->sum('balance');
                                            $paymentsDebit = $contract->payments;
                                            $isHaveDifferent = ((string) $contractDebit != (string) $schedulesDebit)
                                            || ((string) $contractDebit != (string) $paymentsDebit)
                                            || ((string) $schedulesDebit != (string) $paymentsDebit);
                                        @endphp
                                        <table class="table accounts-list dataTable no-footer">
                                            <tbody>
                                            <tr>
                                                <th>{{ __('Списания по контракту') }}:</th>
                                                <td class="text-right {{ $isHaveDifferent ? 'text-danger' : '' }}">{{ number_format($contractDebit, 2, ',', ' ') }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Сумма списаний по плану') }}:</th>
                                                <td class="text-right {{ $isHaveDifferent ? 'text-danger' : '' }}">{{ number_format($schedulesDebit, 2, ',', ' ') }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Сумма списаний по транзакциям') }}:</th>
                                                <td class="text-right {{ $isHaveDifferent ? 'text-danger' : '' }}">{{ number_format($paymentsDebit, 2, ',', ' ') }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-lg-6">
                                        {{--                                        <table class="table accounts-list dataTable no-footer mt-4">--}}
                                        {{--                                            <tbody>--}}
                                        {{--                                            <tr>--}}
                                        {{--                                                <th>{{ __('Общая сумма списаний') }}:</th>--}}
                                        {{--                                                <td class="text-right"> {{ number_format(0, 2, ',', ' ') }}</td>--}}
                                        {{--                                            </tr>--}}
                                        {{--                                            <tr class="text-title-light">--}}
                                        {{--                                                <th class="pl-4">{{ __('Сумма списаний ручным способом') }}:</th>--}}
                                        {{--                                                <td class="text-right"> {{ number_format(0, 2, ',', ' ') }}</td>--}}
                                        {{--                                            </tr>--}}
                                        {{--                                            <tr class="text-title-light">--}}
                                        {{--                                                <th class="pl-4">{{ __('Сумма списаний автоматическим способом') }}:</th>--}}
                                        {{--                                                <td class="text-right"> {{ number_format(0, 2, ',', ' ') }}</td>--}}
                                        {{--                                            </tr>--}}
                                        {{--                                            <tr class="text-title-light">--}}
                                        {{--                                                <th class="pl-5">{{ __('С л/с') }}:</th>--}}
                                        {{--                                                <td class="text-right"> {{ number_format(0, 2, ',', ' ') }}</td>--}}
                                        {{--                                            </tr>--}}
                                        {{--                                            <tr class="text-title-light">--}}
                                        {{--                                                <th class="pl-5">{{ __('С платёжных карт') }}:</th>--}}
                                        {{--                                                <td class="text-right"> {{ number_format(0, 2, ',', ' ') }}</td>--}}
                                        {{--                                            </tr>--}}
                                        {{--                                            <tr class="text-title-light">--}}
                                        {{--                                                <th class="pl-5">{{ __('По PNFL') }}:</th>--}}
                                        {{--                                                <td class="text-right"> {{ number_format(0, 2, ',', ' ') }}</td>--}}
                                        {{--                                            </tr>--}}
                                        {{--                                            </tbody>--}}
                                        </table>
                                    </div>
                                </div>

                                @isset($paymentsByContracts[$contract->id])

                                    <h4 class="m-2 mt-4 text-success font-weight-bold">{{ __('Транзакции по договору') }}</h4>

                                    @include('panel.monitoring.parts.payments-table', ['payments' => $paymentsByContracts[$contract->id]])
                                @endisset

                                <h4 class="m-2 mt-4 text-success font-weight-bold">{{ __('План по договору') }}</h4>

                                @include('panel.monitoring.parts.schedules-table', ['schedules' => $contract->schedule])

                            </div>
                        @endforeach
                    </div>
                </div>
            </div>


        </div>
    </div>






@endsection()
