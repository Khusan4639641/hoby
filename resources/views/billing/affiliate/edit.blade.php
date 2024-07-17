@extends('templates.billing.app')

@section('title', $affiliate->name)
@section('class', 'affiliates edit')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('billing.affiliates.index')}}"><img src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>
@endsection

@section('content')

    <form method="POST" enctype="multipart/form-data" action="{{localeRoute('billing.affiliates.update', $affiliate)}}">
        @csrf
        @method('PATCH')

        <input type="hidden" name="files_to_delete" :value="files.delete">

        <div class="form-group">
            <input @change="updateFiles" ref="logo" accept=".png, .jpg, .jpeg, .gif" name="logo" type="file" class="d-none" id="customFile">

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

        <div class="form-group">
            <label>{{__('billing/affiliate.company_short_description')}}</label>
            <textarea v-tinymce  maxlength="255" name="company_short_description" class="@error('company_short_description') is-invalid @enderror tinymce__preview-text form-control">
                    {{old('company_short_description', $affiliate->short_description)}}
                </textarea>
            @error('company_short_description')
            <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="form-group">
            <label>{{__('billing/affiliate.company_description')}}</label>
            <textarea v-tinymce name="company_description" class="@error('company_description') is-invalid @enderror tinymce__detail-text form-control">
                    {{old('company_description', $affiliate->description)}}
                </textarea>
            @error('company_description')
            <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <hr>

        <div class="lead">{{__('billing/affiliate.txt_contact_info')}}</div>

        <div class="form-row">

            <div class="form-group col-12 col-md-4">
                <label>{{__('billing/affiliate.user_surname')}}</label>
                <input value="{{old('surname', $affiliate->user->surname)}}" required name="surname" type="text" class="@error('surname') is-invalid @enderror form-control">
                @error('surname')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('billing/affiliate.user_name')}}</label>
                <input value="{{old('name', $affiliate->user->name)}}" required name="name" type="text" class="@error('name') is-invalid @enderror form-control">
                @error('name')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('billing/affiliate.user_patronymic')}}</label>
                <input value="{{old('patronymic', $affiliate->user->patronymic)}}" required name="patronymic" type="text" class="@error('patronymic') is-invalid @enderror form-control">
                @error('patronymic')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div><!-- /.form-row -->

        <div class="form-row">
            <div class="form-group col-12 col-md-4">
                <label>{{__('billing/affiliate.user_phone')}}</label>
                <input v-mask="'+998#########'" value="{{old('phone', $affiliate->user->phone)}}" required name="phone" type="text" class="@error('phone') is-invalid @enderror form-control">
                @error('phone')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div><!-- /.form-row -->

        <hr>


        <div class="lead">{{__('billing/affiliate.txt_law_info')}}</div>
        <div class="form-row">
            <div class="col-12 col-md-6">

                <div class="form-group">
                    <label>{{__('billing/affiliate.company_name')}}</label>
                    <input value="{{old('company_name', $affiliate->name)}}" name="company_name" required type="text" class="@error('company_name') is-invalid @enderror form-control">
                    @error('company_name')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('billing/affiliate.company_inn')}}</label>
                    <input value="{{old('company_inn', $affiliate->inn)}}" name="company_inn" required type="text" class="@error('company_inn') is-invalid @enderror form-control">
                    @error('company_inn')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('billing/affiliate.company_address')}}</label>
                    <input value="{{old('company_address', $affiliate->address)}}" name="company_address" required type="text" class="@error('company_address') is-invalid @enderror form-control">
                    @error('company_address')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('billing/affiliate.company_legal_address')}}</label>
                    <input value="{{old('company_legal_address', $affiliate->legal_address)}}" name="company_legal_address" required type="text" class="@error('company_legal_address') is-invalid @enderror form-control">
                    @error('company_legal_address')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>
            </div><!-- /.col-12 col-md-6 -->

            <div class="col-12 col-md-6">


                <div class="form-group">
                    <label>{{__('billing/affiliate.company_bank_name')}}</label>
                    <input value="{{old('company_bank_name', $affiliate->bank_name)}}" name="company_bank_name" required type="text" class="@error('company_bank_name') is-invalid @enderror form-control">
                    @error('company_bank_name')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('billing/affiliate.company_payment_account')}}</label>
                    <input value="{{old('company_payment_account', $affiliate->payment_account)}}" name="company_payment_account" required type="text" class="@error('company_payment_account') is-invalid @enderror form-control">
                    @error('company_payment_account')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('billing/affiliate.company_phone')}}</label>
                    <input  v-mask="'+998#########'" value="{{old('company_phone', $affiliate->phone)}}" name="company_phone" type="text" class="@error('company_phone') is-invalid @enderror form-control">
                    @error('company_phone')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('billing/affiliate.company_website')}}</label>
                    <input value="{{old('company_phone', $affiliate->website)}}" name="company_website" type="text" class="@error('company_website') is-invalid @enderror form-control">
                    @error('company_website')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

            </div><!-- /.col-12 col-md-6 -->

        </div><!-- /.form-row -->


        <div class="form-controls">
            <a class="btn btn-outline-secondary" href="{{localeRoute('panel.partners.show', $affiliate->id)}}">{{__('app.btn_cancel')}}</a>
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
                    old: '{{$affiliate->logo->id??null}}',
                    delete: null
                },
                preview: '{{$affiliate->logo->preview??null}}',
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
