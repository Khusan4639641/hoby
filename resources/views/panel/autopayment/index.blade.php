@extends('templates.panel.app')

@section('title', __('cabinet/autopayment.title'))

@section('content')


    <div class="col-lg-12 mb-5">
        <div class="row text-right">
            <div class="col-lg-12">
                <div>
                    {{ __('Последнее обновление') }}
                </div>
                <label>{{ \App\Facades\UniversalAutoPayment::lastRefresh() }}</label>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-lg-6">
            <div class="h4">{{ __('Новые транзакции') }}</div>
            <table class="table payments-list mt-4">
                <thead>
                <tr>
                    <th>{{__('ID')}}</th>
                    <th>{{__('Должник')}}</th>
                    <th>{{__('ID транзакции')}}</th>
                    <th class="text-center">{{__('Сумма')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($autopayments as $autopayment)
                    <tr>
                        <td>{{ $autopayment->id }}</td>
                        <td>{{ $autopayment->debtor->user->fio }}</td>
                        <td>{{ $autopayment->universal_transaction_id }}</td>
                        <td class="text-right">{{ currency_format($autopayment->amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="col-lg-6">
            <div class="h4">{{ __('Новые должники') }}</div>
            <table class="table payments-list mt-4">
                <thead>
                <tr>
                    <th>{{__('Должник')}}</th>
                    <th>{{__('Начисленный долг')}}</th>
                    <th class="text-center">{{__('Остаток')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($debtors as $debtor)
                    <tr>
                        <td>{{ $debtor->user->fio }}</td>
                        <td class="text-right">{{ currency_format($debtor->total_debit) }}</td>
                        <td class="text-right">{{ currency_format($debtor->current_debit) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>


@endsection()
