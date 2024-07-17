@if($menu)
    <ul class="navbar-nav mr-auto menu-top">
        @foreach($menu as $item)
            <li class="nav-item {{$item->active?"active":""}}">
                <a class="nav-link" href="{{$item->link}}">
                    {{$item->caption}}
                </a>
            </li>
        @endforeach
    </ul>
@endif
