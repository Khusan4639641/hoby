@if(count($faq) > 0)
    <ul class="list-unstyled">
        @foreach($faq as $item)
            <li><a href="{{localeRoute('faq.index')}}#{{$item->id}}">{{$item->locale->title}}</a></li>
        @endforeach
    </ul>
@endif
