@extends('templates.frontend.app')
@section('class', 'catalog index')

@section('title', __('frontend/catalog.header_catalog'))

@section('center')

@endsection
@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{__('frontend/breadcrumbs.catalog')}}
                </li>
            </ol>
        </nav>

        <h1>{{__('frontend/catalog.header_catalog')}}</h1>

        @if(count($subcategories) > 0)
            <div class="row subcategories">
                @foreach($subcategories as $subcategory)
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="item"  @if($subcategory->image)style="background-image: url(/storage/{{$subcategory->image->path}});"@endif>

                            <div class="inner">
                                <a class="name" href="{{localeRoute('catalog.category.show', ['slug' => $subcategory->locale->slug, 'id' => $subcategory->id])}}">
                                    {{$subcategory->locale->title}}
                                </a>
                                <a class="btn btn-outline-light" href="{{localeRoute('catalog.category.show', ['slug' => $subcategory->locale->slug, 'id' => $subcategory->id])}}">{{__('app.btn_more')}}</a>
                            </div>
                        </div>
                        <!-- /.item -->
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
