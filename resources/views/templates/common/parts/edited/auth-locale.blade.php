@php
    $locale =  App\Helpers\LocaleHelper::language();
    $locales = App\Helpers\LocaleHelper::languages();
@endphp


<div class="menu-locale dropdown ml-0 pt-1">
    <a href="/{{$locale->code}}"
       class="dropdown-toggle bg-transparent text-dark"
       id="dropdownLocaleButton"
       data-toggle="dropdown"
       aria-haspopup="true"
       aria-expanded="false">
        <img src="/assets/icons/{{ $locale->code }}.png"
             alt="{{ $locale->code }}"
             width="20">
        <span class="text-uppercase">{{ $locale->code }}</span>
    </a>
    <div class="dropdown-menu modified dropdown-menu-right bg-white shadow-sm" aria-labelledby="dropdownLocaleButton">
        @foreach($locales as $item)
            @if($item->code != $locale->code)
                <a role="button" class="dropdown-item p-0 text-dark other-locale">
                    <img src="/assets/icons/{{ $item->code }}.png" alt="{{ $item->code }}" width="20">
                    <span class="text-uppercase">{{ $item->code }}</span>
                </a>
            @endif
        @endforeach
    </div>
</div><!-- /.menu-locale -->

<style>
    .header .center .menu-user .nav-item .dropdown-menu.modified {
        padding: .5rem 1rem;
        margin-top: .5rem;
        border-radius: .5rem;
    }
</style>

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
