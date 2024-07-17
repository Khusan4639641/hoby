<div class="mobile-bar d-block d-lg-none">
    <div class="container-fluid">
        <div class="buttons">
            <a class="catalog-open" href="#">
                <img src="{{asset('images/icons/icon_catalog_green.svg')}}">
                <br>
                {{__('cabinet/cabinet.menu_mobile_catalog')}}
            </a>
            {{--<a href="#">
                <img src="{{asset('images/icons/icon_favorite_green_circle.svg')}}">
                <br>
                {{__('cabinet/cabinet.menu_mobile_favorite')}}
            </a>--}}
            <a href="{{localeRoute('cart')}}">
                <img src="{{asset('images/icons/icon_cart_green.svg')}}">
                <br>
                {{__('cabinet/cabinet.menu_mobile_cart')}}
            </a>
            @guest
                <a href="#" data-toggle="modal" data-target="#auth">
                    <img src="{{asset('images/icons/icon_user_green_circle.svg')}}">
                    <br>
                    {{__('cabinet/cabinet.menu_mobile_login')}}
                </a>
            @endguest
            @auth
                <a href="{{localeRoute('cabinet.index')}}">
                    <img src="{{asset('images/icons/icon_user_green_circle.svg')}}">
                    <br>
                    {{__('cabinet/cabinet.menu_mobile_cab')}}
                </a>
            @endauth
        </div>
        <!-- /.buttons -->
    </div><!-- /.content-fluid -->
</div><!-- /.mobile-bar -->
