@extends('templates.billing.app')

@section('title', __('billing/catalog.catalog'))

@section('content')
    <a href="{{localeRoute('catalog.create')}}" class="btn btn-success">{{__('app.btn_add')}}</a>
@endsection
