@extends('templates.panel.app')

@section('title', __('panel/pay-system.header_create'))
@section('class', 'pay-system edit')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.pay-system.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('content')

    <form method="POST" enctype="multipart/form-data" action="{{localeRoute('panel.pay-system.store')}}">
        @csrf

        <div class="form-group">
            <label>{{__('panel/pay_sys.title')}}</label>
            <input name="title" type="text" class="@error('company_brand') is-invalid @enderror form-control" value="{{old('title')}}">
            @error('company_brand')
            <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="form-group">
            <label>{{__('panel/pay_sys.link')}}</label>
            <input name="link" type="text" class="@error('company_brand') is-invalid @enderror form-control" value="{{old('link')}}">
            @error('company_brand')
            <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>


        <hr>
        <div class="form-row">
            <input @change="updateFiles" ref="logo" accept=".png, .jpg, .jpeg, .gif" name="logo" type="file" class="d-none" id="customFile">

            <div class="form-group col-12 col-md-6">
            <div v-if="preview" class="preview">
                <button v-on:click="resetFiles" class="btn btn-sm btn-danger">
                    <img src="{{asset('images/icons/icon_close.svg')}}">
                </button>
                <img :src="preview" />
            </div>
            <div v-else class="no-preview">
                <label class="btn btn-primary" for="customFile">{{__('billing/profile.btn_load_logo')}}</label>
                <div class="help">{!! __('app.help_image_short')!!}</div>
            </div>
            @error('logo')
            <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
            @enderror
            </div>

            <div class="form-group col-12 col-md-6">
                <label>{{__('panel/pay_sys.status')}}</label>
                <input value="{{old('status')}}" required name="status" type="text" class="@error('name') is-invalid @enderror form-control">
                @error('name')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

        </div>



        <hr>




        <div class="form-controls">
            <a class="btn btn-outline-secondary" href="{{localeRoute('panel.partners.index')}}">{{__('app.btn_cancel')}}</a>

            <button type="submit" class="btn btn-outline-primary ml-lg-auto">{{__('app.btn_save')}}</button>
        </div>

    </form>

    @include('templates.backend.parts.tinymce')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                error: false,
                files: {
                    new: null,
                    old: null,
                    delete: null
                },
                preview: null,
            },
            methods: {
                resetFiles() {
                    this.preview = null;
                    this.$refs.logo.value = '';
                    this.files.new = null;
                    this.files.delete = this.files.old;
                },
                updateFiles() {
                    this.files.new = this.$refs.logo.files;
                    if(this.files.new.length > 0) {
                        this.preview = URL.createObjectURL(this.files.new[0]);
                        this.files.delete = this.files.old;
                    }
                }
            }
        })
    </script>

@endsection
