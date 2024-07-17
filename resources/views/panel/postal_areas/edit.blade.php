@extends('templates.panel.app')

@section('title', __('panel/postal_areas.header_edit'))
@section('class', 'postal-areas edit')


@section('content')
    <div class="postal-areas">
        <form class="edit" method="POST" action="{{localeRoute('panel.postal-areas.update', $area)}}">
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label>{{__('panel/postal_areas.name')}}</label>
                <input value="{{old('name', $area->name)}}" required name="name" type="text" class="@error('name') is-invalid @enderror form-control">
                @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-row">

                <div class="col form-group">
                    <label>{{__('panel/postal_areas.katm_local_region')}}</label>
                    <select class="form-control @error('katm_local_region') is-invalid @enderror" name="katm_local_region">
                        <option value="">{{__('panel/postal_areas.choose_region')}}</option>
                        @foreach($katm_local_regions as $key => $value)
                            <option @if($key == old('katm_local_region', $area->katm_local_region)) selected @endif value="{{$key}}">{{$value}}</option>
                        @endforeach
                    </select>
                    @error('katm_local_region')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="col form-group">
                    <label>{{__('panel/postal_areas.postal_region_id')}}</label>
                    <select class="form-control @error('postal_region_id') is-invalid @enderror" name="postal_region_id">
                        <option value="">{{__('panel/postal_areas.choose_region')}}</option>
                        @foreach($postal_regions as $key => $value)
                            <option @if($key == old('postal_region_id', $area->postal_region_id)) selected @endif value="{{$key}}">{{$value}}</option>
                        @endforeach
                    </select>
                    @error('postal_region_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="col form-group">
                    <label>{{__('panel/postal_areas.external_id')}}</label>
                    <input value="{{old('external_id', $area->external_id)}}" required name="external_id" type="text" class="@error('external_id') is-invalid @enderror form-control">
                    @error('external_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

            </div>

            <hr>

            <div class="form-controls">
                <a href="{{localeRoute('panel.postal-areas.index')}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>
                <button type="submit" class="btn btn-primary ml-md-auto">{{__('app.btn_save')}}</button>
            </div>
        </form>
    </div><!-- /.postal-areas -->

@endsection
