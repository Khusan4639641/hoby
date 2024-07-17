@extends('templates.panel.app')

@section('title', __('cabinet/autopayment.title'))

@section('content')


    <div class="col-lg-12 mt-4">
        <div class="h4">{{ __('Список должников') }}</div>
    </div>

    <div class="col-lg-12">

        <table class="table payments-list mt-4">
            <thead>
            <tr>
                <th>{{__('Debit ID')}}</th>
                <th>{{__('Ф.И.О.')}}</th>
                <th class="text-center">{{__('Начисленный долг')}}</th>
                <th class="text-center">{{__('Остаток')}}</th>
                <th class="text-center">{{__('Кол-тво транзакций')}}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($debtors as $debtor)
                <tr>
                    <td>{{ $debtor->universal_debit_id }}</td>
                    <td>{{ $debtor->user->fio }}</td>
                    <td class="text-right">{{ currency_format($debtor->total_debit) }}</td>
                    <td class="text-right">{{ currency_format($debtor->current_debit) }}</td>
                    <td class="text-center">{{ $debtor->autoPaymentsTransactions->count() }}</td>
                    <td class="text-center">
                        <a class="btn btn-success"
                           href="{{ localeRoute('panel.universal.autopayment.debtor.transactions', $debtor->user_id) }}">{{ __('Транзакции') }}</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div>
            {{ $debtors->links() }}
        </div>

    </div>

@endsection()
