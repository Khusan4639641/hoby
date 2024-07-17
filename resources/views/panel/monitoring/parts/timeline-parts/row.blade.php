<div
    class="float-right text-muted">{{ date('Y-m-d H:i:s', strtotime($payment->created_at)) }}</div>
{{--                            <div class="float-right text-muted">Mon, Jan 9th 2019 7:00 AM</div>--}}

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

    <h4 class="card-title"
        style="color: #8ec29a;">{{ __('Пополнение личного счёта через :payment_system', ['payment_system' => $payment->payment_system]) }}</h4>
    <p class="card-text">
        <span class="font-weight-bold">{{ __('Сумма') }}</span>: {{ number_format($payment->amount, 2, ',', ' ') }}
    </p>
    <p class="card-text">
        <span class="font-weight-bold">{{ __('Личный счёт') }}</span>: {{ number_format($account, 2, ',', ' ') }}
    </p>

@elseif ($payment->type == 'auto'
&& (
   $payment->payment_system == 'UZCARD'
   || $payment->payment_system == 'HUMO'
   || $payment->payment_system == 'ACCOUNT'
   || $payment->payment_system == 'PNFL'))

    <h4 class="card-title"
        style="color: #bc7a80;">{{ __('Автомтическо списнаие с :payment_system', ['payment_system' => $payment->payment_system]) }}</h4>
    <p class="card-text">
        <span class="font-weight-bold">{{ __('Сумма') }}</span>: {{ number_format($payment->amount, 2, ',', ' ') }}
    </p>

    @if($payment->payment_system == 'ACCOUNT')
        <p class="card-text">
            <span class="font-weight-bold">{{ __('Личный счёт') }}</span>: {{ number_format($account, 2, ',', ' ') }}
        </p>
    @endif

    @isset($contracts[$payment->contract_id])
        <p class="card-text">
            <span
                class="font-weight-bold">{{ __('Долг договора: :id', ['id' => $payment->contract_id]) }}</span>: {{ number_format($contracts[$payment->contract_id], 2, ',', ' ') }}
        </p>
    @endisset

@elseif ($payment->type == 'user_auto' && $payment->payment_system == 'ACCOUNT')

    <h4 class="card-title"
        style="color: #bc7a80;">{{ __('Списнаие с личного счёта (:payment_system)', ['payment_system' => $payment->payment_system]) }}</h4>
    <p class="card-text">
        <span class="font-weight-bold">{{ __('Сумма') }}</span>: {{ number_format($payment->amount, 2, ',', ' ') }}
    </p>
    <p class="card-text">
        <span class="font-weight-bold">{{ __('Личный счёт') }}</span>: {{ number_format($account, 2, ',', ' ') }}
    </p>
    @isset($contracts[$payment->contract_id])
        <p class="card-text">
            <span
                class="font-weight-bold">{{ __('Долг договора: :id', ['id' => $payment->contract_id]) }}</span>: {{ number_format($contracts[$payment->contract_id], 2, ',', ' ') }}
        </p>
    @endisset

@elseif (($payment->type == 'user_auto' && ($payment->payment_system == 'UZCARD' || $payment->payment_system == 'HUMO')))

    <h4 class="card-title"
        style="color: #7292d2;">{{ __('Пополнение л.с. через :payment_system и списание', ['payment_system' => $payment->payment_system]) }}</h4>
    <p class="card-text">
        <span class="font-weight-bold">{{ __('Сумма') }}</span>: {{ number_format($payment->amount, 2, ',', ' ') }}
    </p>
    @isset($contracts[$payment->contract_id])
        <p class="card-text">
            <span
                class="font-weight-bold">{{ __('Долг договора: :id', ['id' => $payment->contract_id]) }}</span>: {{ number_format($contracts[$payment->contract_id], 2, ',', ' ') }}
        </p>
    @endisset

@elseif (strtoupper($payment->payment_system) == 'DEPOSIT')

    <h4 class="card-title"
        style="color: #b4b4b4;">{{ __('Оплата :payment_system', ['payment_system' => $payment->payment_system]) }}</h4>
    <p class="card-text">
        <span class="font-weight-bold">{{ __('Сумма') }}</span>: {{ number_format($payment->amount, 2, ',', ' ') }}
    </p>
    <p class="card-text">
        <span class="font-weight-bold">{{ __('Личный счёт') }}</span>: {{ number_format($account, 2, ',', ' ') }}
    </p>

@elseif ($payment->payment_system == 'Paycoin')
    @if ($payment->type == 'fill')
        <h4 class="card-title"
            style="color: #8ec29a;">{{ __('Бонусное пополнение') }}</h4>
        <p class="card-text">
            <span class="font-weight-bold">{{ __('Сумма') }}</span>: {{ number_format($payment->amount, 2, ',', ' ') }}
        </p>
        <p class="card-text">
            <span class="font-weight-bold">{{ __('Бонусный счёт') }}</span>: {{ number_format($bonus, 2, ',', ' ') }}
        </p>
    @elseif ($payment->type == 'upay')
        <h4 class="card-title"
            style="color: #bc7a80;">{{ __('Бонусное списание') }}</h4>
        <p class="card-text">
            <span class="font-weight-bold">{{ __('Сумма') }}</span>: {{ number_format($payment->amount, 2, ',', ' ') }}
        </p>
        <p class="card-text">
            <span class="font-weight-bold">{{ __('Бонусный счёт') }}</span>: {{ number_format($bonus, 2, ',', ' ') }}
        </p>
    @elseif ($payment->type == 'refund')
        <h4 class="card-title"
            style="color: #bc7a80;">{{ __('Возврат (отмена) бонусных баллов') }}</h4>
        <p class="card-text">
            <span class="font-weight-bold">{{ __('Сумма') }}</span>: {{ number_format($payment->amount, 2, ',', ' ') }}
        </p>
        <p class="card-text">
            <span class="font-weight-bold">{{ __('Бонусный счёт') }}</span>: {{ number_format($bonus, 2, ',', ' ') }}
        </p>
    @endif
@else
    <h4 class="card-title"
        style="color: #ffae1f;">{{ __('Не идентифицирован') }}</h4>
    <p class="card-text">
        <span class="font-weight-bold">{{ __('Сумма') }}</span>: {{ number_format($payment->amount, 2, ',', ' ') }}
    </p>
@endif


