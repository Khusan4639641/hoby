@extends('templates.frontend.app')
@section('class', 'catalog category show')

@section('title', $category->locale->title)

@section('content')

    @if($category->image)
        <section class="info" style="background-image: url(/storage/{{$category->image->path}});">
            <div class="container">

                <h1>{{$category->locale->title}}</h1>
                <div class="description">
                    {{$category->locale->detail_text}}
                </div>
            </div><!-- /.container -->
        </section><!-- /.info -->
    @endif

    <div class="container">


        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                <li class="breadcrumb-item" aria-current="page">
                    <a href="{{localeRoute('catalog.index')}}">{{__('frontend/breadcrumbs.catalog')}}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{$category->language->title}}
                </li>

            </ol>
        </nav>

        @if($category->image == null)
            <h1>{{$category->locale->title}}</h1>
        @endif

        @if(count($subcategories) > 0)
            {{--<h4>{{__('frontend/catalog.categories')}}</h4>--}}
            <div class="row subcategories">
                @foreach($subcategories as $subcategory)
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="item"  @if($subcategory->image)style="background-image: url(/storage/{{$subcategory->image->path}});"@endif>
                            <a href="{{localeRoute('catalog.category.show', ['slug' => $subcategory->locale->slug, 'id' => $subcategory->id])}}" class="inner">
                                <div class="name">{{$subcategory->locale->title}}</div>
                            </a>
                            <!-- /.inner -->
                        </div><!-- /.item -->
                    </div>
                @endforeach
            </div>
        @endif
        @if(count($products) > 0)
            @include('frontend.catalog.parts.products', ['products' => $products])
        @elseif(count($subcategories) == 0)
            <div class="alert alert-warning">{{__('frontend/catalog.empty_category')}}</div>
        @endif
    </div>

@endsection
