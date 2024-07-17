@extends('templates.panel.app')

@section('title', __('panel/postal_regions.header_edit'))
@section('class', 'postal-regions edit')


@section('content')
    <div class="postal-regions">
        <form class="edit" method="POST" action="{{localeRoute('panel.postal-regions.update', $region)}}">
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label>{{__('panel/postal_regions.name')}}</label>
                <input value="{{old('name', $region->name)}}" required name="name" type="text" class="@error('name') is-invalid @enderror form-control">
                @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-row">

                <div class="col form-group">
                    <label>{{__('panel/postal_regions.katm_region')}}</label>
                    <select class="form-control @error('katm_region') is-invalid @enderror" name="katm_region">
                        <option value="">{{__('panel/postal_regions.choose_region')}}</option>
                        @foreach($katm_regions as $key => $value)
                            <option @if($key == old('katm_region', $region->katm_region)) selected @endif value="{{$key}}">{{$value}}</option>
                        @endforeach
                    </select>
                    @error('katm_region')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="col form-group">
                    <label>{{__('panel/postal_regions.external_id')}}</label>
                    <input value="{{old('external_id', $region->external_id)}}" required name="external_id" type="text" class="@error('external_id') is-invalid @enderror form-control">
                    @error('external_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

            </div>

            <hr>

            <div class="form-controls">
                <a href="{{localeRoute('panel.postal-regions.index')}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>
                <button type="submit" class="btn btn-primary ml-md-auto">{{__('app.btn_save')}}</button>
            </div>
        </form>
    </div><!-- /.postal-regions -->

@endsection
