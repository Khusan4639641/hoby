@extends('templates.frontend.app')
@section('class', 'page '.request()->name)
@section('title', __('frontend/page_installment.title'))


@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{__('frontend/page_installment.title')}}
                </li>
            </ol>
        </nav>

        <h1>{{__('frontend/page_installment.h1')}}</h1>
        <p>Страница в разработке.</p>

    </div>
@endsection
