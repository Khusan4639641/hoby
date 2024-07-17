<table class="table accounts-list dataTable no-footer mt-4" id="DataTables_Table_0" role="grid">
    <thead>
    <tr role="row">
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Сумма к оплате') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Долг') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Оплачено') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Статус') }}</th>
        <th class="sorting_disabled text-center" rowspan="1" colspan="1">{{ __('Дата списания') }}</th>
    </tr>
    </thead>
    <tbody>
    @php
        $date = 0;
    @endphp
    @foreach($schedules as $schedule)
        @if ($date != date('m', strtotime($schedule->payment_date)))
            @php
                $date = date('m', strtotime($schedule->payment_date));
            @endphp
            <tr role="row" class="odd bg-light">
                <td colspan="10" class="text-uppercase font-weight-bold text-info">{{ date('Y', strtotime($schedule->payment_date)) }}/{{ __('app.month_' . $date) }}</td>
            </tr>
        @endif
        <tr role="row" class="odd text-white {{ $schedule->balance == 0 && $schedule->status == 1 ? 'bg-teal' : ($schedule->balance != $schedule->total ? 'bg-light-blue' : 'bg-light-red') }}">
            <td class="text-right">{{ number_format($schedule->total, 2, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($schedule->balance, 2, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($schedule->total - $schedule->balance, 2, ',', ' ') }}</td>
            <td class="text-right">{{ $schedule->status }}</td>
            <td class="text-right">{{ $schedule->payment_date }}</td>
        </tr>
    @endforeach

    </tbody>
</table>
