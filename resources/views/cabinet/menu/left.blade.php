<div class="menu">

    @if($menu)
        <ul>
            @php
            $level = 0;
            @endphp
            @foreach($menu as $item)
                @if($level < $item->level)
                    <ul>
                @elseif($level > $item->level)
                    </ul>
                @endif

                <li {!! $item->attr !!} class="nav-item {{$item->active?"active":""}} {{$item->class}}">
                   @if($item->link)
                        <a class="nav-link level-{{$item->level}}" href="{{$item->link}}">{{$item->caption}}</a>
                    @else
                       <span class="nav-link level-{{$item->level}}">{{$item->caption}}</span>
                    @endif
                </li>

                @php
                    $level = $item->level;
                @endphp
            @endforeach
        </ul>
    @endif

</div><!-- /.menu -->
