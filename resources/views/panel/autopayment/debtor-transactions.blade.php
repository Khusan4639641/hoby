@extends('templates.panel.app')

@section('title', __('cabinet/autopayment.title'))

@section('content')


    <div class="col-lg-12 mt-4">
        <div class="h4">{{ __('Транзакции должника') }}</div>
    </div>

    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-8">
                <label>{{ __('Ф.И.О.') }}</label>
                <div>
                    {{ $debtor->user->fio }}
                </div>
            </div>
            <div class="col-lg-2">
                <label class="text-right">{{ __('Начисленный долг') }}</label>
                <div class="text-right">
                    {{ $debtor->total_debit }}
                </div>
            </div>
            <div class="col-lg-2">
                <label class="text-right">{{ __('Остаток') }}</label>
                <div class="text-right">
                    {{ $debtor->current_debit }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-12">

        <table class="table payments-list mt-4">
            <thead>
            <tr>
                <th>{{__('ID')}}</th>
                <th>{{__('Debtor')}}</th>
                <th>{{__('Transaction ID')}}</th>
                <th>{{__('Amount')}}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($autopayments as $autopayment)
                <tr>
                    <td>{{ $autopayment->id }}</td>
                    <td>{{ $autopayment->debtor->user->fio }}</td>
                    <td>{{ $autopayment->universal_transaction_id }}</td>
                    <td>{{ currency_format($autopayment->amount) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

    </div>

@endsection()
