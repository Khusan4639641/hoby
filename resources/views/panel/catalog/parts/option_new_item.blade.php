@foreach($categories as $category)
    @php $space = str_repeat("—", ($loop->depth - 1)); @endphp
    <option class="level-{{$loop->depth}}" @if(old('parent') == $category->id) selected @endif value="{{$category->id}}">
    {{$space}} {{$category->locale->title}}
    </option>
    @if(count($category->child) > 0)
        @include('panel.catalog.parts.option_new_item', ['categories' => $category->child])
    @endif
@endforeach

