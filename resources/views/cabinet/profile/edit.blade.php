@extends('templates.cabinet.app')
@section('title', __('cabinet/profile.header_profile_edit'))
@section('class', 'profile edit')

@section('content')

    <div id="profile">
        <form method="POST" enctype="multipart/form-data" action="{{localeRoute('cabinet.profile.update')}}">
            @csrf
            @method('PATCH')

            <input type="hidden" name="files_to_delete" :value="files.delete">

            {{--<div class="form-group">
                <input @change="updateFiles" ref="userAvatar" accept=".png, .jpg, .jpeg, .gif" name="avatar" type="file" class="@error('avatar') is-invalid @enderror d-none" id="customFile">

                <div v-if="preview" class="preview">
                    <button v-on:click="resetFiles" class="btn btn-sm btn-danger">x</button>
                    <img :src="preview" />
                </div>
                <div v-else class="no-preview">
                    <div class="img">
                        <div class="dummy"></div>
                        <div class="help">
                            {!! __('app.help_image') !!}
                        </div>
                    </div>
                    <label class="btn btn-outline-primary" for="customFile">+ {{__('app.btn_choose_avatar')}}</label>
                </div>

                @error('avatar')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror

            </div>--}}

            @if($profile->status != 4)
                <div class="form-row">

                    <div class="form-group col-12 col-md">
                        <label for="inputSurname">{{__('cabinet/profile.surname')}}</label>
                        <input value="{{old('surname', $profile->surname)}}" required name="surname" type="text" class="@error('surname') is-invalid @enderror form-control">
                        @error('surname')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md">
                        <label for="inputName">{{__('cabinet/profile.name')}}</label>
                        <input value="{{old('name', $profile->name)}}" required name="name" type="text" class="@error('name') is-invalid @enderror form-control">
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md">
                        <label for="inputPatronymic">{{__('cabinet/profile.patronymic')}}</label>
                        <input value="{{old('patronymic', $profile->patronymic)}}" required name="patronymic" type="text" class="@error('patronymic') is-invalid @enderror form-control">
                        @error('patronymic')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div><!-- /.form-row -->
            @endif

            {{--<div class="form-row">
                <div class="form-group col-12 col-sm-6 col-md-4">
                    <label for="inputWorkCompany">{{__('cabinet/profile.city_birth')}}</label>
                    <input value="{{old('city_birth', $profile->personals->city_birth)}}" type="text" name="city_birth" class="@error('city_birth') is-invalid @enderror form-control">
                    @error('city_birth')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group col-12 col-md-4">
                    <label for="city_birth">{{__('cabinet/profile.birthday')}}</label>
                    <input type="hidden" name="birthday" :value="birthday" class="@error('birthday') is-invalid @enderror">
                    <date-picker v-model="birthday" value-type="format" type="date"
                                 format="DD.MM.YYYY"></date-picker>
                    @error('birthday')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>--}}


            <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label>{{__('cabinet/profile.home_phone')}}</label>
                    <input type="tel" v-mask="'+############'" v-model="home_phone" name="home_phone" class="@error('home_phone') is-invalid @enderror form-control"/>
                    @error('home_phone')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
            </div>

            <hr>

           <div class="lead">{{__('cabinet/profile.work_place')}}</div>

            <div class="form-group">
                <label for="inputWorkCompany">{{__('cabinet/profile.work_company')}}</label>
                <input value="{{old('work_company', $profile->personals->work_company)}}" type="text" name="work_company" class="@error('work_company') is-invalid @enderror form-control">
                @error('work_company')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label for="inputWorkPhone">{{__('cabinet/profile.work_phone')}}</label>
                    <input v-mask="'+############'" value="{{old('work_phone', $profile->personals->work_phone)}}" name="work_phone" class="@error('work_phone') is-invalid @enderror form-control"></the-mask>
                    @error('work_phone')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>
            </div>

            <hr>


            <div class="lead">{{__('cabinet/profile.social_networks')}}</div>

            <div class="form-row">
                <div class="form-group col-12 col-sm-6 col-md-4">
                    <label for="inputWorkCompany">{{__('cabinet/profile.social_vk')}}</label>
                    <input value="{{old('social_vk', $profile->personals->social_vk)}}" type="text" name="social_vk" class="@error('social_vk') is-invalid @enderror form-control">
                    @error('social_vk')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="form-group col-12 col-sm-6 col-md-4">
                    <label for="inputWorkCompany">{{__('cabinet/profile.social_facebook')}}</label>
                    <input value="{{old('social_facebook', $profile->personals->social_facebook)}}" type="text" name="social_facebook" class="@error('social_facebook') is-invalid @enderror form-control">
                    @error('social_facebook')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="form-group col-12 col-sm-6 col-md-4">
                    <label for="inputWorkCompany">{{__('cabinet/profile.social_linkedin')}}</label>
                    <input value="{{old('social_linkedin', $profile->personals->social_linkedin)}}" type="text" name="social_linkedin" class="@error('social_linkedin') is-invalid @enderror form-control">
                    @error('social_linkedin')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="form-group col-12 col-sm-6 col-md-4">
                    <label for="inputWorkCompany">{{__('cabinet/profile.social_instagram')}}</label>
                    <input value="{{old('social_instagram', $profile->personals->social_instagram)}}" type="text" name="social_instagram" class="@error('social_instagram') is-invalid @enderror form-control">
                    @error('social_instagram')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
            </div>

            <hr>

            <div class="form-controls">
                <a class="btn btn-outline-secondary" href="{{localeRoute('cabinet.profile.show')}}">{{__('app.btn_cancel')}}</a>
                <button type="submit" class="btn btn-primary ml-lg-auto">{{__('app.btn_save')}}</button>
            </div>
        </form>
    </div><!-- /#profile -->

    <script>
        var app = new Vue({
            el: '#profile',
            data :{
                birthday: '{{old('patronymic', $profile->personals->birthday)}}',
                home_phone: '{{old('home_phone', $profile->personals->home_phone)}}',
                work_phone: '{{old('work_phone', $profile->personals->work_phone)}}',
                files: {
                    new: null,
                    old: {{$profile->avatar?$profile->avatar->id:'null'}},
                    delete: null
                },
                preview: '{{$profile->avatar->path??null}}',
            },
            methods: {
                resetFiles() {
                    this.preview = null;
                    this.$refs.userAvatar.value = '';
                    this.files.new = null;
                    this.files.delete = this.files.old;
                },
                updateFiles() {
                    this.files.new = this.$refs.userAvatar.files;
                    if(this.files.new.length > 0) {
                        this.preview = URL.createObjectURL(this.files.new[0]);
                        this.files.delete = this.files.old;
                    }
                }
            }
        });
    </script>

@endsection



