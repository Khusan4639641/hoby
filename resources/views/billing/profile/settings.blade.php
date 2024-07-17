@extends('templates.billing.app')

@section('class', 'profile settings')

@section('title', __('billing/profile.header_settings'))


@section('content')
    <div class="employees">
        <form class="edit" method="POST" action="{{localeRoute('billing.profile.update-settings')}}">
            @csrf
            @method('PATCH')


            <div class="form-row">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label>{{__('billing/profile.check_quantity')}}</label>
                        <select name="check_quantity" class="@error('check_quantity') is-invalid @enderror form-control">
                            <option @if(old('check_quantity', $partner->settings->check_quantity) == 1) selected="selected" @endif value="1">{{__('app.yes')}}</option>
                            <option @if(old('check_quantity', $partner->settings->check_quantity) == 0) selected="selected" @endif value="0">{{__('app.no')}}</option>
                        </select>
                        @error('check_quantity')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label>{{__('billing/profile.discount_3')}}</label>
                        <input value="{{old('discount_3', $partner->settings->discount_3)}}" required name="discount_3" type="text" class="@error('discount_3') is-invalid @enderror form-control">
                        @error('discount_3')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{__('billing/profile.discount_6')}}</label>
                        <input value="{{old('discount_6', $partner->settings->discount_6)}}" required name="discount_6" type="text" class="@error('discount_6') is-invalid @enderror form-control">
                        @error('discount_6')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{__('billing/profile.discount_9')}}</label>
                        <input value="{{old('discount_9', $partner->settings->discount_9)}}" required name="discount_9" type="text" class="@error('discount_9') is-invalid @enderror form-control">
                        @error('discount_9')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
            </div><!-- /.form-row -->


            <div class="form-controls">
                {{--<a href="{{localeRoute('billing.index')}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>--}}
                <button type="submit" class="ml-sm-auto btn btn-success">{{__('app.btn_save')}}</button>
            </div>

        </form>
    </div><!-- /.employees -->
@endsection
