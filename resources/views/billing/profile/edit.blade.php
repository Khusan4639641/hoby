@extends('templates.billing.app')

@section('title', __('billing/profile.header_profile'))
@section('class', 'profile edit')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('billing.index')}}"><img src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>
@endsection

@section('content')

    <div class="employees">
        <form class="edit" method="POST" enctype="multipart/form-data" action="{{localeRoute('billing.profile.update')}}">
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

            <hr>
            <div class="form-group">
                <label>{{__('panel/partner.company_brand')}}</label>
                <input name="company_brand" type="text" class="@error('company_brand') is-invalid @enderror form-control" value="{{old('company_brand', $partner->company->brand)}}">
                @error('company_brand')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label>{{__('billing/profile.company_short_description')}}</label>
                <textarea v-tinymce maxlength="255" name="company_short_description" class="@error('company_short_description') is-invalid @enderror tinymce__preview-text form-control">
                    {{old('company_short_description', $partner->company->short_description)}}
                </textarea>
                @error('company_short_description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label>{{__('billing/profile.company_description')}}</label>
                <textarea v-tinymce name="company_description" class="@error('company_description') is-invalid @enderror tinymce__detail-text form-control">
                    {{old('company_description', $partner->company->description)}}
                </textarea>
                @error('company_description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <hr>

            <div class="lead">{{__('billing/profile.txt_requisites')}}</div>

            <div class="form-group">
                <label>{{__('billing/profile.company_name')}}</label>
                <input value="{{old('company_name', $partner->company->name)}}" required name="company_name" type="text" class="@error('company_name') is-invalid @enderror form-control">
                @error('company_name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group">
                <label>{{__('billing/profile.company_inn')}}</label>
                <input value="{{old('company_inn', $partner->company->inn)}}" required name="company_inn" type="text" class="@error('company_inn') is-invalid @enderror form-control">
                @error('company_inn')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group">
                <label>{{__('billing/profile.company_address')}}</label>
                <input value="{{old('company_address', $partner->company->address)}}" required name="company_address" type="text" class="@error('company_address') is-invalid @enderror form-control">
                @error('company_address')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group">
                <label>{{__('billing/profile.company_legal_address')}}</label>
                <input value="{{old('company_legal_address', $partner->company->legal_address)}}" required name="company_legal_address" type="text" class="@error('company_legal_address') is-invalid @enderror form-control">
                @error('company_legal_address')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group">
                <label>{{__('billing/profile.company_bank_name')}}</label>
                <input value="{{old('company_bank_name', $partner->company->bank_name)}}" required name="company_bank_name" type="text" class="@error('company_bank_name') is-invalid @enderror form-control">
                @error('company_bank_name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group">
                <label>{{__('billing/profile.company_payment_account')}}</label>
                <input value="{{old('company_payment_account', $partner->company->payment_account)}}" required name="company_payment_account" type="text" class="@error('company_payment_account') is-invalid @enderror form-control">
                @error('company_payment_account')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label>{{__('billing/profile.company_phone')}}</label>
                <input v-mask="'+998#########'" value="{{old('company_phone', $partner->company->phone)}}" name="company_phone" type="text" class="@error('company_phone') is-invalid @enderror form-control">
                @error('company_phone')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label>{{__('billing/profile.company_website')}}</label>
                <input value="{{old('company_website', $partner->company->website)}}" name="company_website" type="text" class="@error('company_website') is-invalid @enderror form-control">
                @error('company_website')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>


            <div class="form-group">
                <label>{{__('billing/profile.company_working_hours')}}</label>
                <input value="{{old('company_working_hours', $partner->company->working_hours)}}" name="company_working_hours" type="text" class="@error('company_working_hours') is-invalid @enderror form-control">
                @error('company_working_hours')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <hr>

            <div class="lead">{{__('billing/profile.txt_contact_info')}}</div>

            <div class="row">
                <div class="col-12 col-md">
                    <div class="form-group">
                        <label>{{__('billing/profile.user_surname')}}</label>
                        <input value="{{old('surname', $partner->surname)}}" required name="surname" type="text" class="@error('surname') is-invalid @enderror form-control">
                        @error('surname')
                        <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                        @enderror
                    </div>
                </div>

                <div class="col-12 col-md">
                    <div class="form-group">
                        <label>{{__('billing/profile.user_name')}}</label>
                        <input value="{{old('name', $partner->name)}}" required name="name" type="text" class="@error('name') is-invalid @enderror form-control">
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <div class="col-12 col-md">
                    <div class="form-group">
                        <label>{{__('billing/profile.user_patronymic')}}</label>
                        <input value="{{old('patronymic', $partner->patronymic)}}" name="patronymic" type="text" class="@error('patronymic') is-invalid @enderror form-control">
                        @error('patronymic')
                        <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>{{__('billing/profile.user_phone')}}</label>
                <input value="{{old('phone', $partner->phone)}}" required name="phone" type="text" class="@error('phone') is-invalid @enderror form-control">
                @error('phone')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div v-if="changePassword">
                <hr>
                <div class="lead">{{__('billing/profile.password')}}</div>
                <div class="form-group">
                    <input placeholder="{{__('billing/profile.password')}}" value="{{old('password')}}" name="password" type="password" class="@error('password') is-invalid @enderror form-control">
                    @error('password')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>
                <div class="form-group">
                    <input placeholder="{{__('billing/profile.password_confirmation')}}" type="password" class="form-control" name="password_confirmation">
                </div>
            </div>
            <div v-else>
                <p><a class="change-password" href="javascript:;" @click="changePassword = true">{{__('billing/profile.txt_change_password')}}?</a></p>
            </div>

            <div class="form-controls">
                <a href="{{localeRoute('billing.index')}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>
                <button type="submit" class="ml-sm-auto btn btn-success">{{__('app.btn_save')}}</button>
            </div>
        </form>
    </div><!-- /.employees -->

    @include('templates.backend.parts.tinymce')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                changePassword: false,
                error: false,
                files: {
                    new: null,
                    old: '{{$partner->company->logo->id??null}}',
                    delete: null
                },
                preview: '{{$partner->company->logo->preview??null}}',
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
