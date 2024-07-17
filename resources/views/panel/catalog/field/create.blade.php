@extends('templates.panel.app')

@section('title', __('panel/catalog.field.header_create'))
@section('class', 'fields create')

@section('content')

    <form class="create" method="POST" enctype="multipart/form-data" action="{{localeRoute('panel.catalog.fields.store')}}">
        @csrf

        @foreach($languages as $language)
            <h3>{{$language->name}}</h3>
            <div class="form-group">
                <label>{{__('panel/catalog.field.title')}}</label>
                <input {{$language->default?'required':''}} value="{{old($language->code.'_title')}}" name="{{$language->code}}_title" type="text" class="@error($language->code.'_title') is-invalid @enderror form-control">
                @error($language->code.'_title')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        @endforeach

        <div class="form-controls">
            <a href="{{localeRoute('panel.catalog.fields.index')}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>
            <button type="submit" class="btn ml-md-auto btn-primary">{{__('app.btn_save')}}</button>
        </div>
    </form>

@endsection
