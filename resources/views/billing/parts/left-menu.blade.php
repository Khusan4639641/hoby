<ul class="list-group list-group-flush mt-3">
    <a
        role="list"
        href="{{localeRoute('billing.orders.create')}}"
        class="btn-plus-order text-decoration-none text-dark {{ localeRoute('billing.orders.create') === url()->current() ? 'active' : '' }}"
{{--        data-toggle="tooltip"--}}
{{--        data-placement="right"--}}
        title="{{__('billing/order.btn_create_order')}}"
    >
                    <span class="mr-2">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12 9.016V14.984M9.016 12H14.984M18.332 21.332H5.66797C4.01097 21.332 2.66797 19.989 2.66797 18.332V5.66797C2.66797 4.01097 4.01097 2.66797 5.66797 2.66797H18.332C19.989 2.66797 21.332 4.01097 21.332 5.66797V18.332C21.332 19.989 19.989 21.332 18.332 21.332Z"
                            stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
        <span class="left-menu-label">
            {{__('billing/order.btn_create_order')}}
        </span>
    </a>

    {{--    <a role="list"--}}
    {{--       class="list-inline-item border-0 btn-plus-order text-dark text-decoration-none {{ localeRoute('billing.index') === url()->current() ? 'active' : '' }} mr-0"--}}
    {{--       href="{{localeRoute('billing.index')}}">--}}
    {{--                    <span class="mr-2">--}}
    {{--                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">--}}
    {{--                            <path--}}
    {{--                                d="M5.33301 19.8412V15.3342C5.33301 14.2292 6.22801 13.3342 7.33301 13.3342H16.666C17.771 13.3342 18.666 14.2292 18.666 15.3342V19.8412M14.9634 7.12316C14.9634 8.75958 13.6368 10.0862 12.0004 10.0862C10.364 10.0862 9.03738 8.75958 9.03738 7.12316C9.03738 5.48674 10.364 4.16016 12.0004 4.16016C13.6368 4.16016 14.9634 5.48674 14.9634 7.12316Z"--}}
    {{--                                stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round"--}}
    {{--                                stroke-linejoin="round" />--}}
    {{--                        </svg>--}}
    {{--                    </span>--}}
    {{--        <span>Профиль</span>--}}
    {{--    </a>--}}
    <a role="list"
       class="list-inline-item border-0 btn-plus-order text-dark text-decoration-none mr-0 {{ localeRoute('billing.orders.index') === url()->current() ? 'active' : '' }}"
       href="{{ localeRoute('billing.orders.index') }}"
{{--       data-toggle="tooltip"--}}
{{--       data-placement="right"--}}
       title="{{ __('billing/menu.orders') }}"
    >
        <span class="mr-2">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M6.66699 8.00017H17.333M6.66699 12.0002H17.333M6.66699 16.0002H17.333M18.333 21.4932H5.66699C4.00999 21.4932 2.66699 20.1502 2.66699 18.4932V5.82617C2.66699 4.16917 4.00999 2.82617 5.66699 2.82617H18.334C19.991 2.82617 21.334 4.16917 21.334 5.82617V18.4932C21.333 20.1502 19.99 21.4932 18.333 21.4932Z"
                stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <span class="left-menu-label">{{ __('billing/menu.orders') }}</span>
    </a>

    <a role="list"
       class="list-inline-item border-0 btn-plus-order text-dark text-decoration-none mr-0 {{ localeRoute('billing.statistics.index') === url()->current() ? 'active' : '' }}"
       href="{{ localeRoute('billing.statistics.index') }}"
{{--       data-toggle="tooltip"--}}
{{--       data-placement="right"--}}
       title="{{ __('billing/menu.statistics') }}"
    >
        <span class="mr-2">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M4 4.00195V17.428C4 18.848 5.151 19.999 6.571 19.999H20M8 16V10.668M12 16.001V8.00195M16 16V12.002M20 16V5.33595"
                stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <span class="left-menu-label">{{ __('billing/menu.statistics') }}</span>
    </a>

    <a role="list"
       class="list-inline-item
           border-0
           btn-plus-order
           text-dark
           text-decoration-none
           mr-0
            {{ localeRoute('billing.buyers.create') === url()->current() ? 'active' : '' }}"
       href="{{ localeRoute('billing.buyers.create') }}"
{{--       data-toggle="tooltip"--}}
{{--       data-placement="right"--}}
       title="{{ __('billing/menu.register_client') }}"
    >
        <span class="mr-2">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M2.35059 19.8607V15.3537C2.35059 14.2487 3.24559 13.3537 4.35059 13.3537H13.6836C14.7886 13.3537 15.6836 14.2487 15.6836 15.3537V19.8607M18.6656 4.14062V10.1076M15.6826 7.12363L21.6496 7.12463M11.9806 7.14269C11.9806 8.77911 10.654 10.1057 9.01759 10.1057C7.38117 10.1057 6.05459 8.77911 6.05459 7.14269C6.05459 5.50627 7.38117 4.17969 9.01759 4.17969C10.654 4.17969 11.9806 5.50627 11.9806 7.14269Z"
                stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <span class="left-menu-label">{{ __('billing/menu.register_client') }}</span>
    </a>

    <a role="list"
       class="list-inline-item
           border-0
           btn-plus-order
           text-dark
           text-decoration-none
           mr-0
            {{ localeRoute('billing.user.status') === url()->current() ? 'active' : '' }}"
       href="{{ localeRoute('billing.user.status') }}"
{{--       data-toggle="tooltip"--}}
{{--       data-placement="right"--}}
       title="{{ __('billing/menu.user_status') }}"
    >
        <span class="mr-2">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M2.66699 19.8412V15.3342C2.66699 14.2292 3.56199 13.3342 4.66699 13.3342H14C15.105 13.3342 16 14.2292 16 15.3342V19.8412M14.667 4.16016C16.304 4.16016 17.63 5.48716 17.63 7.12316C17.63 8.75916 16.303 10.0862 14.667 10.0862M19.333 13.3352C20.438 13.3352 21.333 14.2302 21.333 15.3352V19.8422M12.296 7.12316C12.296 8.75958 10.9694 10.0862 9.33299 10.0862C7.69657 10.0862 6.36999 8.75958 6.36999 7.12316C6.36999 5.48674 7.69657 4.16016 9.33299 4.16016C10.9694 4.16016 12.296 5.48674 12.296 7.12316Z"
                stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <span class="left-menu-label">{{ __('billing/menu.user_status') }}</span>
    </a>

    <a role="list"
       class="list-inline-item
           border-0
           btn-plus-order
           text-dark
           text-decoration-none
           mr-0
            {{ localeRoute('billing.orders.calculator') === url()->current() ? 'active' : '' }}"
       href="{{ localeRoute('billing.orders.calculator') }}"
       {{--       data-toggle="tooltip"--}}
       {{--       data-placement="right"--}}
       title="{{ __('billing/menu.calculator') }}"
    >
        <span class="mr-2">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M9.33203 6.66797H14.666M9.32716 17.332L9.32924 17.3333M9.32716 14.6679L9.32924 14.6692M9.32716 12.0039L9.32924 12.0052M11.999 12.0039L12.0011 12.0052M11.999 14.6679L12.0011 14.6692M11.999 17.332L12.0011 17.3333M14.6719 12.0039L14.674 12.0052M14.6875 17.3398V14.6641M15.666 21.332H8.33203C6.67503 21.332 5.33203 19.989 5.33203 18.332V5.66797C5.33203 4.01097 6.67503 2.66797 8.33203 2.66797H15.665C17.322 2.66797 18.665 4.01097 18.665 5.66797V18.332C18.666 19.989 17.323 21.332 15.666 21.332Z"
                    stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="left-menu-label">{{ __('billing/menu.calculator') }}</span>
    </a>
</ul>
