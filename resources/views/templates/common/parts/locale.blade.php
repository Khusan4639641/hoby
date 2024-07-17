@php
    $locale =  App\Helpers\LocaleHelper::language();
    $locales = App\Helpers\LocaleHelper::languages();
@endphp


<div class="menu-locale dropdown">
    <a href="/{{$locale->code}}" class="dropdown-toggle" id="dropdownLocaleButton" data-toggle="dropdown"
       aria-haspopup="true" aria-expanded="false">
        <img src="/assets/icons/{{ $locale->code }}.png" alt="{{ $locale->code }}" width="20">
        {{--        {{mb_strtoupper($locale->code)}}--}}
    </a>
    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownLocaleButton">
        @foreach($locales as $item)
            @if($item->code != $locale->code)
                <a role="button" class="dropdown-item other-locale" href="/{{$item->code}}">
                    <img src="/assets/icons/{{ $item->code }}.png" alt="{{ $item->code }}" width="20">
                    {{--                    {{mb_strtoupper($item->code)}}--}}
                </a>
            @endif
        @endforeach
    </div>
</div><!-- /.menu-locale -->

<script>
$('.other-locale').on('click', function (event) {
    event.preventDefault();

    const pathname = window.location.pathname;
    const isUZ = pathname.includes('uz');
    const changeToRU = pathname.replace('uz', 'ru');
    const changeToUZ = pathname.replace('ru', 'uz');


    window.location.href = isUZ ? changeToRU : changeToUZ;

});
</script>
