<header class="header">
    <div class="top d-none d-md-block">
        <div class="container">
            <nav class="navbar navbar-expand-sm">
                {{App\Helpers\MenuHelper::render('frontend', 'top')}}
                @include('templates.common.parts.locale')
            </nav>
        </div><!-- /.container -->
    </div>
    <div class="center">
        <div class="container">
            <nav class="navbar navbar-expand-md navbar-light">
                <a class="navbar-brand" href="/">
                    <img class="d-none d-sm-block" src="{{asset('images/logo_white.svg')}}">
                    <img class="d-block d-sm-none" width="40px" height="40px" src="{{asset('images/logo_img_white.svg')}}">
                </a>

               <span class="d-none d-md-block btn-success catalog-open">{{__('template.btn_catalog')}}</span>

                <div class="input-search d-none d-md-block">
                    <form action="{{localeRoute('search')}}">
                        <div class="input-group">
                            <input type="text" required name="q" class="form-control" value="{{request()->get('q')}}" autocomplete="off">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-success btn-search"></button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="d-block d-md-none ml-auto mr-4">
                    @include('templates.common.parts.locale')
                </div>

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Скрыть меню">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavDropdown">

                    <div class="d-block d-md-none menu-mobile">
                        {{App\Helpers\MenuHelper::render('frontend', 'top')}}

                        <ul class="navbar-nav menu-user">
                        @guest
                            <li class="nav-item"><a class="nav-link link-user" href="#" data-toggle="modal" data-target="#auth">{{__('template.txt_login_buyer')}}</a></li>
                            <li class="nav-item"><a class="nav-link link-shop" href="{{localeRoute('partners.login')}}">{{__('template.txt_login_partner')}}</a></li>
                        @endguest
                        @auth
                            <li class="nav-item"><a class="nav-link link-user" href="{{localeRoute('cabinet.index')}}">{{__('template.txt_cab')}}</a></li>
                            <li class="nav-item"><a class="nav-link link-exit" href="{{localeRoute('logout')}}">{{__('app.btn_exit')}}</a></li>
                        @endauth
                            <li class="nav-item cart-item">
                                <a class="nav-link link-cart" href="{{localeRoute('cart')}}" role="button">{{__('app.btn_cart')}}&nbsp;(<span class="count"><?=\App\Http\Controllers\Core\CartController::countCartProducts()?></span>)</a>
                            </li>
                        </ul>
                    </div>

                    <ul class="d-none d-md-flex navbar-nav ml-auto menu-user">
                        @guest
                            <li class="nav-item dropdown">
                                <a class="nav-link link-user dropdown-toggle" href="#" id="navbarDropdownLogin" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('app.btn_enter')}}</a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownLogin">
                                    <a class="dropdown-item link-user" href="#" data-toggle="modal" data-target="#auth">{{__('template.txt_login_buyer')}}</a>
                                    <a class="dropdown-item link-shop" href="{{localeRoute('partners.login')}}">{{__('template.txt_login_partner')}}</a>
                                </div>
                            </li>
                        @endguest
                        @auth
                            <li class="nav-item dropdown">
                                <a class="nav-link link-user dropdown-toggle" href="#" id="navbarDropdownLogin" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    @if(Auth::user()->name)
                                        {{Auth::user()->surname}} {{Auth::user()->name}}
                                    @else
                                        {{Auth::user()->phone}}
                                    @endif
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownLogin">
                                    <a class="dropdown-item link-user" href="{{localeRoute('cabinet.index')}}">{{__('template.txt_cab')}}</a>
                                    <a class="dropdown-item link-exit" href="{{localeRoute('logout')}}">{{__('app.btn_exit')}}</a>
                                </div>
                            </li>
                        @endauth
                        <li class="nav-item">
                            <a class="nav-link link-cart" href="{{localeRoute('cart')}}" role="button">{{__('app.btn_cart')}}&nbsp;(<span class="count"><?=\App\Http\Controllers\Core\CartController::countCartProducts()?></span>)</a>
                        </li>
                    </ul>
                </div><!-- /.collapse -->
            </nav>

            <span class="mb-3 d-block d-md-none btn-success catalog-open">{{__('template.btn_catalog')}}</span>

        </div><!-- /.container -->
    </div><!-- /.center -->
</header><!-- /.header -->



@php
    $categories = \App\Http\Controllers\Core\CatalogCategoryController::tree();
@endphp
<div class="catalog-overlay">
    <div class="container">
        <div class="row align-items-stretch no-gutters">
            <div class="col-md-4 col-xl-3 categories-root no-gutters">
                <!-- Desktop menu -->
                <ul class="cats-menu d-none d-md-block">
                    @foreach($categories as $category)
                        <li class="@if($loop->iteration == 1) active @endif category-{{$category->id}}"  data-id="{{$category->id}}">{{$category->locale->title}}</li>
                    @endforeach
                </ul>

                <!-- Mobile menu -->
                <ul class="d-block d-md-none">
                    @foreach($categories as $category)
                        <li class="@if($loop->iteration == 1) active @endif category-{{$category->id}}"  data-id="{{$category->id}}">
                            <a href="{{localeRoute('catalog.category.show', ['slug' => $category->locale->slug, 'id' => $category->id])}}"> {{$category->locale->title}}</a>
                        </li>
                    @endforeach
                </ul>

                <!-- Pages menu -->
                <div class="pages-menu">
                    {{App\Helpers\MenuHelper::render('frontend', 'pages')}}
                </div>
            </div>
            <div class="d-none d-md-block col-md-8 col-xl-9 categories-sub no-gutters">

                @foreach($categories as $category)

                    <div class="category-level1" data-parent="{{$category->id}}">
                        @if(count($category->child) > 0)
                            <div class="row">
                                @foreach($category->child as $child)
                                    <div class="col-12 col-md-6 col-xl-4 item">
                                        <div class="category-level2">
                                            <div class="title"><a href="{{localeRoute('catalog.category.show', ['slug' => $child->locale->slug, 'id' => $child->id])}}">{{$child->locale->title}}</a></div>
                                        </div>
                                        @if(count($child->child) > 0)
                                            <div class="category-level3">
                                                @foreach($child->child as $item)
                                                    <div class="title"><a href="{{localeRoute('catalog.category.show', ['slug' => $item->locale->slug, 'id' => $item->id])}}">{{$item->locale->title}}</a></div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{__('app.empty_list')}}
                        @endif
                    </div>

                @endforeach
            </div>
        </div>

    </div>
</div>
