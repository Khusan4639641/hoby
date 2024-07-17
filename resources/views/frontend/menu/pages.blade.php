@if($menu)
    <ul class="list-unstyled">
    @foreach($menu as $item)
        <li>
            <a href="{{$item->link}}">
                {{$item->caption}}
            </a>
        </li>
    @endforeach
    </ul>
@endif
