@extends('templates.frontend.app')

@section('title', __('frontend/search.results_for', ['word' => $q]))
@section('class', 'search index')

@section('content')
    <div class="container">
        <h1>{{__('frontend/search.results_for', ['word' => $q])}}</h1>
        @include('frontend.catalog.parts.products', ['products' => $products])
    </div>
@endsection
