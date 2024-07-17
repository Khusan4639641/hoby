@if($menu)
    <ul class="list-inline navbar-bottom">
    @foreach($menu as $item)
        <li class="nav-item">
            <a class="nav-link" href="{{$item->link}}">
                {{$item->caption}}
            </a>
        </li>
    @endforeach
    </ul>
@endif
