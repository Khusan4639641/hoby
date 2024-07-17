<header class="header fixed">
    <div class="center">
        <nav class="navbar navbar-expand-md navbar-light py-3">

            <a class="navbar-brand" href="{{ localeRoute('billing.orders.index') }}">
                <img src="https://resusnasiya.uz/img/resus-logo.dcaaeb47.svg">
            </a>

            @if(\Illuminate\Support\Facades\Auth::check())
                <div class="header-partner-card d-none d-md-block">
                    {{App\Http\Controllers\Web\Billing\ProfileController::card()}}
                </div>
            @endif

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown"
                    aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Скрыть меню">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                @if(\Illuminate\Support\Facades\Auth::check())
                    <div class="header-partner-card d-block d-md-none">
                        {{App\Http\Controllers\Web\Billing\ProfileController::card()}}
                    </div>
                @endif
                <ul class="d-none d-md-flex align-items-center navbar-nav ml-auto menu-user">
                    <li class="mr-3">
                        <div class="customer-support d-flex">
                            <img class="mr-3" src="{{ asset('assets/icons/customer-support.svg') }}" alt="">
                            <div>
                                {!! __('cabinet/cabinet.lbl_call_center')!!}
                                <br>
                                <span class="font-weight-normal">
                                    {{\Illuminate\Support\Facades\Config::get('test.help_phone')}}
                                </span>
                            </div>
                        </div>
                    </li>
                    <li class="mr-3">
                        @include('templates.common.parts.locale')
                    </li>
{{--                    <li class="mr-4 notifications">--}}
{{--                        <a role="button"--}}
{{--                           href="{{localeRoute('billing.notification.index')}}"--}}
{{--                           title="{{ __('billing/menu.notifications') }}"--}}
{{--                        >--}}
{{--                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"--}}
{{--                                 xmlns="http://www.w3.org/2000/svg">--}}
{{--                                <path--}}
{{--                                    d="M14.282 20.241C13.83 21.033 12.977 21.567 12 21.567C11.022 21.567 10.169 21.033 9.71699 20.24M2.66699 17.318L21.334 17.318L19.174 14.09C18.844 13.596 18.667 13.016 18.667 12.422V9.33497C18.667 5.65297 15.682 2.66797 12 2.66797C8.31799 2.66797 5.33299 5.65297 5.33299 9.33497L5.33299 12.423C5.33299 13.017 5.15699 13.598 4.82599 14.091L2.66699 17.318Z"--}}
{{--                                    stroke="#1E1E1E"--}}
{{--                                    stroke-miterlimit="10"--}}
{{--                                    stroke-linecap="round"--}}
{{--                                    stroke-linejoin="round"--}}
{{--                                />--}}
{{--                            </svg>--}}
{{--                        </a>--}}
{{--                    </li>--}}
                    <li class="logout">
                        <a role="button"
                           href="{{localeRoute('logout')}}"
                           title="{{ __('app.btn_exit') }}"
                        >
                            <svg width="24"
                                 height="24"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M9.33411 21.832C9.61025 21.832 9.83411 21.6082 9.83411 21.332C9.83411 21.0559 9.61025 20.832 9.33411 20.832V21.832ZM6.28711 21.332V21.832V21.332ZM3.28711 18.332H2.78711H3.28711ZM3.28711 5.66602H3.78711H3.28711ZM6.28711 2.66602V3.16602V2.66602ZM9.33411 3.16602C9.61025 3.16602 9.83411 2.94216 9.83411 2.66602C9.83411 2.38987 9.61025 2.16602 9.33411 2.16602V3.16602ZM15.9647 5.96746C15.7694 5.7722 15.4528 5.7722 15.2576 5.96746C15.0623 6.16272 15.0623 6.47931 15.2576 6.67457L15.9647 5.96746ZM21.3241 12.034L21.6777 12.3876C21.7714 12.2938 21.8241 12.1666 21.8241 12.034C21.8241 11.9014 21.7714 11.7742 21.6777 11.6805L21.3241 12.034ZM15.2576 17.3935C15.0623 17.5887 15.0623 17.9053 15.2576 18.1006C15.4528 18.2958 15.7694 18.2958 15.9647 18.1006L15.2576 17.3935ZM8.01111 11.534C7.73497 11.534 7.51111 11.7579 7.51111 12.034C7.51111 12.3102 7.73497 12.534 8.01111 12.534V11.534ZM21.3081 12.534C21.5843 12.534 21.8081 12.3102 21.8081 12.034C21.8081 11.7579 21.5843 11.534 21.3081 11.534V12.534ZM9.33411 20.832H6.28711V21.832H9.33411V20.832ZM6.28711 20.832C4.90625 20.832 3.78711 19.7129 3.78711 18.332H2.78711C2.78711 20.2652 4.35397 21.832 6.28711 21.832V20.832ZM3.78711 18.332L3.78711 5.66602H2.78711L2.78711 18.332H3.78711ZM3.78711 5.66602C3.78711 4.28516 4.90625 3.16602 6.28711 3.16602V2.16602C4.35397 2.16602 2.78711 3.73287 2.78711 5.66602H3.78711ZM6.28711 3.16602L9.33411 3.16602V2.16602L6.28711 2.16602V3.16602ZM15.2576 6.67457L20.9706 12.3876L21.6777 11.6805L15.9647 5.96746L15.2576 6.67457ZM20.9706 11.6805L15.2576 17.3935L15.9647 18.1006L21.6777 12.3876L20.9706 11.6805ZM8.01111 12.534H21.3081V11.534H8.01111V12.534Z"
                                    fill="#1E1E1E"
                                />
                            </svg>
                        </a>
                    </li>
                </ul>
                <ul class="d-block d-md-none list-inline mb-0">
                    <li class="list-inline-item">
                        <div class="customer-support d-flex">
                            <img class="mr-3" src="{{ asset('assets/icons/customer-support.svg') }}" alt="">
                            <div>
                                {!! __('cabinet/cabinet.lbl_call_center')!!}
                                <br>
                                <span class="font-weight-normal">
                                    {{\Illuminate\Support\Facades\Config::get('test.help_phone')}}
                                </span>
                            </div>
                        </div>
                    </li>
                    <li class="list-inline-item">
                        @include('templates.common.parts.locale')
                    </li>
{{--                    <li class="list-inline-item mr-4">--}}
{{--                        <a role="button" class=""--}}
{{--                           href="{{localeRoute('billing.notification.index')}}">--}}
{{--                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"--}}
{{--                                 xmlns="http://www.w3.org/2000/svg">--}}
{{--                                <path--}}
{{--                                    d="M14.282 20.241C13.83 21.033 12.977 21.567 12 21.567C11.022 21.567 10.169 21.033 9.71699 20.24M2.66699 17.318L21.334 17.318L19.174 14.09C18.844 13.596 18.667 13.016 18.667 12.422V9.33497C18.667 5.65297 15.682 2.66797 12 2.66797C8.31799 2.66797 5.33299 5.65297 5.33299 9.33497L5.33299 12.423C5.33299 13.017 5.15699 13.598 4.82599 14.091L2.66699 17.318Z"--}}
{{--                                    stroke="#1E1E1E"--}}
{{--                                    stroke-miterlimit="10"--}}
{{--                                    stroke-linecap="round"--}}
{{--                                    stroke-linejoin="round"--}}
{{--                                />--}}
{{--                            </svg>--}}
{{--                        </a>--}}
{{--                    </li>--}}
                    <li class="list-inline-item mr-4">
                        <a role="button" href="{{localeRoute('logout')}}">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M9.33411 21.832C9.61025 21.832 9.83411 21.6082 9.83411 21.332C9.83411 21.0559 9.61025 20.832 9.33411 20.832V21.832ZM6.28711 21.332V21.832V21.332ZM3.28711 18.332H2.78711H3.28711ZM3.28711 5.66602H3.78711H3.28711ZM6.28711 2.66602V3.16602V2.66602ZM9.33411 3.16602C9.61025 3.16602 9.83411 2.94216 9.83411 2.66602C9.83411 2.38987 9.61025 2.16602 9.33411 2.16602V3.16602ZM15.9647 5.96746C15.7694 5.7722 15.4528 5.7722 15.2576 5.96746C15.0623 6.16272 15.0623 6.47931 15.2576 6.67457L15.9647 5.96746ZM21.3241 12.034L21.6777 12.3876C21.7714 12.2938 21.8241 12.1666 21.8241 12.034C21.8241 11.9014 21.7714 11.7742 21.6777 11.6805L21.3241 12.034ZM15.2576 17.3935C15.0623 17.5887 15.0623 17.9053 15.2576 18.1006C15.4528 18.2958 15.7694 18.2958 15.9647 18.1006L15.2576 17.3935ZM8.01111 11.534C7.73497 11.534 7.51111 11.7579 7.51111 12.034C7.51111 12.3102 7.73497 12.534 8.01111 12.534V11.534ZM21.3081 12.534C21.5843 12.534 21.8081 12.3102 21.8081 12.034C21.8081 11.7579 21.5843 11.534 21.3081 11.534V12.534ZM9.33411 20.832H6.28711V21.832H9.33411V20.832ZM6.28711 20.832C4.90625 20.832 3.78711 19.7129 3.78711 18.332H2.78711C2.78711 20.2652 4.35397 21.832 6.28711 21.832V20.832ZM3.78711 18.332L3.78711 5.66602H2.78711L2.78711 18.332H3.78711ZM3.78711 5.66602C3.78711 4.28516 4.90625 3.16602 6.28711 3.16602V2.16602C4.35397 2.16602 2.78711 3.73287 2.78711 5.66602H3.78711ZM6.28711 3.16602L9.33411 3.16602V2.16602L6.28711 2.16602V3.16602ZM15.2576 6.67457L20.9706 12.3876L21.6777 11.6805L15.9647 5.96746L15.2576 6.67457ZM20.9706 11.6805L15.2576 17.3935L15.9647 18.1006L21.6777 12.3876L20.9706 11.6805ZM8.01111 12.534H21.3081V11.534H8.01111V12.534Z"
                                    fill="#FF7643" />
                            </svg>
                        </a>
                    </li>
                </ul>
            </div><!-- /.collapse -->
        </nav>
    </div><!-- /.center -->
</header><!-- /.header -->
