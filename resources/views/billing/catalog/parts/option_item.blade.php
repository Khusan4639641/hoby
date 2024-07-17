@foreach($categories as $category)
    @php $space = str_repeat("—", ($loop->depth - 1)); @endphp
    <option data-level="{{$loop->depth}}" class="level-{{$loop->depth}}" @if($productCategories && in_array($category->id, old('categories', $productCategories->pluck('id')->toArray()))) selected @endif
    value="{{$category->id}}">
    {{$space}} {{$category->locale->title}}
    </option>
    @if(count($category->child) > 0)
        @include('billing.catalog.parts.option_item', ['categories' => $category->child, 'productCategories' => $productCategories])
    @endif
@endforeach

