<div class="col-lg-12 mb-5">
    <h4 class="mb-0">
        <a class="small" href="{{ localeRoute( 'panel.buyers.show', $user->id ) }}" target="_blank">
            {{ __('Посмотреть карту покупателя') }}
        </a>
    </h4>
    <h4 class="mb-0">
        <a class="small"
           href="{{ localeRoute('panel.monitoring.user', $user->id) }}">{{ __('История платежей') }}</a>
    </h4>
    <h4 class="mb-0">
        <a class="small"
           href="{{ localeRoute('panel.monitoring.cards', $user->id) }}">{{ __('Платёжные карты') }}</a>
    </h4>
</div>
