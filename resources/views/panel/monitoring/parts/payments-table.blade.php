<table class="table accounts-list dataTable no-footer mt-4" id="DataTables_Table_0" role="grid">
    <thead>
    <tr role="row">
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Договор') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Карта') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Транзакция') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Платёжная система') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Тип') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Статус') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Дата') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Сумма') }}</th>
    </tr>
    </thead>
    <tbody>
    @php
        $date = 0;
    @endphp
    @foreach($payments as $payment)
        @if ($date != date('m', strtotime($payment->created_at)))
            @php
                $date = date('m', strtotime($payment->created_at));
            @endphp
            <tr role="row" class="odd bg-light">
                <td colspan="10" class="text-uppercase font-weight-bold text-info">{{ __('app.month_' . $date) }}</td>
            </tr>
        @endif
        @include('panel.monitoring.parts.table-rows.payments-rows', ['payment' => $payment])
    @endforeach

    </tbody>
</table>
