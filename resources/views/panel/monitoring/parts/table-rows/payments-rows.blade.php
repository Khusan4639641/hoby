<tr role="row" class="odd {{ $payment->status != 1 ? 'bg-dark text-light' : '' }}">
    <td class="text-center">
        @if ($payment->contract_id)
            <a href="{{ localeRoute('panel.contracts.show', $payment->contract_id) }}"
               target="_blank">№{{ $payment->contract_id }}</a>
        @endif
    </td>
    <td class="text-center">{{ $payment->card_id ? __('Есть') : '' }}</td>
    <td class="text-center">{{ $payment->transaction_id ? __('Есть') : '' }}</td>
    <td class="text-center">{{ $payment->payment_system }}</td>
    <td class="text-center">{{ $payment->type }}</td>
    <td class="text-center">{{ $payment->status }}</td>
    <td class="text-right">{{ $payment->created_at }}</td>

    @if ($payment->type == 'user'
        && (
            $payment->payment_system == 'UZCARD'
            || $payment->payment_system == 'HUMO'
            || $payment->payment_system == 'MYUZCARD'
            || $payment->payment_system == 'OCLICK'
            || $payment->payment_system == 'PAYME'
            || $payment->payment_system == 'PAYNET'
            || $payment->payment_system == 'UPAY'
            || $payment->payment_system == 'BANK'
            || $payment->payment_system == 'APELSIN'
            || $payment->payment_system == 'Autopay'
            ))
        <td class="text-center"><span style="background-color: #8ec29a;"
                                      class="border rounded p-1 pl-2 pr-2 text-white">{{ __('Пополнение') }}</span></td>
        {{--        <td class="text-right"><span class="border rounded p-1 pl-2 pr-2 text-info">+ {{ number_format($payment->amount, 2, ',', ' ') }}</span></td>--}}
        <td class="text-right"><span
                class="p-1 pl-2 pr-2 text-info">+ {{ number_format($payment->amount, 2, ',', ' ') }}</span></td>

    @elseif ($payment->type == 'auto'
    && (
        $payment->payment_system == 'UZCARD'
        || $payment->payment_system == 'HUMO'
        || $payment->payment_system == 'ACCOUNT'
        || $payment->payment_system == 'PNFL'))
        <td class="text-center">
            <span style="background-color: #bc7a80;"
                  class="border rounded p-1 pl-2 pr-2 text-white">{{ __('А.списание') }}</span>
        </td>
        <td class="text-right">
            <span class="p-1 pl-2 pr-2"
                  style="color: #bc7a80;">- {{ number_format($payment->amount, 2, ',', ' ') }}</span>
        </td>

    @elseif ($payment->type == 'user_auto' && $payment->payment_system == 'ACCOUNT')
        <td class="text-center">
            <span style="background-color: #bc7a80;"
                  class="border rounded p-1 pl-2 pr-2 text-white">{{ __('Р.списание') }}</span>
        </td>
        <td class="text-right">
            <span class="p-1 pl-2 pr-2"
                  style="color: #bc7a80;">- {{ number_format($payment->amount, 2, ',', ' ') }}</span>
        </td>

    @elseif (($payment->type == 'user_auto' && ($payment->payment_system == 'UZCARD' || $payment->payment_system == 'HUMO')))
        <td class="text-center">
            <span style="background-color: #7292d2;"
                  class="border rounded p-1 pl-2 pr-2 text-white">{{ __('П./Р.с.') }}</span>
        </td>
        <td class="text-right">
            <span class="p-1 pl-2 pr-2"
                  style="color: #7292d2;">+/- {{ number_format($payment->amount, 2, ',', ' ') }}</span>
        </td>

    @elseif (strtoupper($payment->payment_system) == 'DEPOSIT')

        <td class="text-center">
            <span style="background-color: #b4b4b4;"
                  class="border rounded p-1 pl-2 pr-2 text-white">{{ __('Депозит') }}</span>
        </td>
        <td class="text-right">
            <span class="border rounded p-1 pl-2 pr-2">{{ number_format($payment->amount, 2, ',', ' ') }}</span>
        </td>

    @elseif ($payment->payment_system == 'Paycoin')
        @if ($payment->type == 'fill')
            <td class="text-center">
            <span style="background-color: #8ec29a;"
                  class="border rounded p-1 pl-2 pr-2 text-white">{{ __('Бонус.пополнение') }}</span>
            </td>
            <td class="text-right">
            <span class="p-1 pl-2 pr-2 text-info">{{ number_format($payment->amount, 2, ',', ' ') }}</span>
            </td>
        @elseif ($payment->type == 'upay')
            <td class="text-center">
            <span style="background-color: #bc7a80;"
                  class="border rounded p-1 pl-2 pr-2 text-white">{{ __('Бонус.списание') }}</span>
            </td>
            <td class="text-right">
            <span class="p-1 pl-2 pr-2"
                  style="color: #bc7a80;">{{ number_format($payment->amount, 2, ',', ' ') }}</span>
            </td>
        @elseif ($payment->type == 'refund')
            <td class="text-center">
            <span style="background-color: #bc7a80;"
                  class="border rounded p-1 pl-2 pr-2 text-white">{{ __('Бонус.отмена') }}</span>
            </td>
            <td class="text-right">
            <span class="p-1 pl-2 pr-2"
                  style="color: #bc7a80;">{{ number_format($payment->amount, 2, ',', ' ') }}</span>
            </td>
        @endif
    @else
        <td class="text-center">
            <span style="background-color: #ffae1f;"
                  class="border rounded p-1 pl-2 pr-2 text-white">{{ __('Не идентифицирован') }}</span>
        </td>
        <td class="text-right">
            <span class="border rounded p-1 pl-2 pr-2">{{ number_format($payment->amount, 2, ',', ' ') }}</span>
        </td>
    @endif

</tr>
