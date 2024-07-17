<div class="mobile-bar d-block d-lg-none">
    <div class="container-fluid">
        <div class="buttons">
            <a class="catalog-open" href="{{localeRoute('billing.orders.create')}}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M12 9.016V14.984M9.016 12H14.984M18.332 21.332H5.66797C4.01097 21.332 2.66797 19.989 2.66797 18.332V5.66797C2.66797 4.01097 4.01097 2.66797 5.66797 2.66797H18.332C19.989 2.66797 21.332 4.01097 21.332 5.66797V18.332C21.332 19.989 19.989 21.332 18.332 21.332Z"
                        stroke="#1E1E1E"
                        stroke-miterlimit="10"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <br>
                <span>{{__('billing/order.btn_create_order')}}</span>
            </a>
            <a href="{{ localeRoute('billing.orders.index') }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M6.66699 8.00017H17.333M6.66699 12.0002H17.333M6.66699 16.0002H17.333M18.333 21.4932H5.66699C4.00999 21.4932 2.66699 20.1502 2.66699 18.4932V5.82617C2.66699 4.16917 4.00999 2.82617 5.66699 2.82617H18.334C19.991 2.82617 21.334 4.16917 21.334 5.82617V18.4932C21.333 20.1502 19.99 21.4932 18.333 21.4932Z"
                        stroke="#1E1E1E"
                        stroke-miterlimit="10"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <br>
                {{ __('billing/menu.orders') }}
            </a>
            <a href="{{ localeRoute('billing.statistics.index') }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M4 4.00195V17.428C4 18.848 5.151 19.999 6.571 19.999H20M8 16V10.668M12 16.001V8.00195M16 16V12.002M20 16V5.33595"
                        stroke="#1E1E1E"
                        stroke-miterlimit="10"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <br>
                {{ __('billing/menu.statistics') }}
            </a>

            <a href="{{ localeRoute('billing.buyers.create') }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M4 4.00195V17.428C4 18.848 5.151 19.999 6.571 19.999H20M8 16V10.668M12 16.001V8.00195M16 16V12.002M20 16V5.33595"
                        stroke="#1E1E1E"
                        stroke-miterlimit="10"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <br>
                {{ __('billing/menu.register_client') }}
            </a>

            <a href="{{ localeRoute('billing.user.status') }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M4 4.00195V17.428C4 18.848 5.151 19.999 6.571 19.999H20M8 16V10.668M12 16.001V8.00195M16 16V12.002M20 16V5.33595"
                        stroke="#1E1E1E"
                        stroke-miterlimit="10"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <br>
                {{ __('billing/menu.user_status') }}
            </a>


        </div>
        <!-- /.buttons -->
    </div><!-- /.content-fluid -->
</div><!-- /.mobile-bar -->
