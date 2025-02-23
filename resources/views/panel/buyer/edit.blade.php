@extends('templates.panel.app')

@section('title', __('panel/buyer.header_moderate'))
@section('class', 'buyers edit')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.buyers.show', $buyer->id)}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('content')

    <div class="buyer" id="buyer">

        <div v-if="messages.length">
            <div :class="'alert alert-' + message.type" v-for="message in messages">@{{ message.text }}</div>
        </div>


        <form class="edit" method="POST">
            @csrf
            @method('PATCH')


            <div class="lead">{{__('panel/buyer.settings')}}</div>
            {{-- <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.limit')}}</label>
                    <select v-model="user.limit" name="limit" class="form-control">
                        @foreach($limits as $limit)
                            <option @if($limit == $buyer->settings->limit) selected @endif value="{{$limit}}">{{$limit}}</option>
                        @endforeach
                    </select>
                </div>
            </div><!-- /.form-row --> --}}

            <hr>
            <p class="lead">{{__('panel/buyer.lbl_verification')}}</p>

            <div class="form-group">
                <label>{{__('panel/buyer.status')}}</label>
                <select v-model="user.status" class="form-control" name="verify_message">
                    <option value="1">{{__('app.btn_choose')}}</option>
                    @foreach($statuses as $status)
                        <option value="{{$status}}">{{ __('user.status_'.$status) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>{{__('panel/buyer.verify_message')}}</label>
                <select v-model="user.verify_message" class="form-control" name="verify_message" rows="4">
                    <option value="">{{__('app.btn_choose')}}</option>
                    @foreach(explode('|', __('panel/buyer.verify_messages')) as $message)
                        <option value="{{$message}}">{{$message}}</option>
                    @endforeach
                </select>
            </div>

            <hr>

            <div class="lead">{{__('panel/buyer.detail_info')}}</div>
            <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.surname')}}</label>
                    <input v-model="user.surname" name="surname" type="text"
                           :class="(errors.surname?'is-invalid':'') + ' form-control'">
                    <span v-if="errors.surname" v-for="error in errors.surname" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>

                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.name')}}</label>
                    <input v-model="user.name" name="name" type="text"
                           :class="(errors.name?'is-invalid':'') + ' form-control'">
                    <span v-if="errors.name" v-for="error in errors.name" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>

                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.patronymic')}}</label>
                    <input v-model="user.patronymic" name="patronymic" type="text"
                           :class="(errors.patronymic?'is-invalid':'') + ' form-control'">
                    <span v-if="errors.patronymic" v-for="error in errors.patronymic" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
            </div><!-- /.form-row -->

            <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.phone')}}</label>
                    <input value="{{$buyer->phone}}" name="phone" type="text" disabled
                           :class="(errors.phone?'is-invalid':'') + ' form-control'">
                    <span v-if="errors.phone" v-for="error in errors.phone" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>

                {{--<div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.birthday')}}</label>
                    <date-picker value-type="format" v-model="user.birthday" type="date" format="DD.MM.YYYY" name="birthday" :class="(errors.birthday?'is-invalid':'')"></date-picker>
                    <span v-if="errors.birthday" v-for="error in errors.birthday" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div> --}}

                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.birthday')}}</label>
                    <input value="{{$buyer->personals->birthday }}" name="birthday" type="text"
                           :class="(errors.birthday?'is-invalid':'') + ' form-control'">
                    <span v-if="errors.birthday" v-for="error in errors.birthday" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>


            </div><!-- /.form-row -->

            <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.pinfl')}}</label>
                    <input v-model="user.pinfl" name="pinfl" type="text"
                           :class="(errors.pinfl?'is-invalid':'') + ' form-control'">
                    <span v-if="errors.pinfl" v-for="error in errors.pinfl" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
                <div class="form-group col-12 col-md-4">
                    <label>&nbsp;</label>
                    <button type="button" :disabled="user.pinfl == ''" @click="checkPinfl()" class="btn ml-lg-auto btn-primary">{{__('panel/buyer.btn_check_pinfl')}}</button>
                </div>
            </div><!-- /.form-row -->

            <div class="form-row">
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('panel/buyer.inn')}}</label>
                        <input v-model="user.inn" name="inn" type="text"
                               :class="(errors.inn?'is-invalid':'') + ' form-control'">
                        <span v-if="errors.inn" v-for="error in errors.inn" class="invalid-feedback" role="alert">
                            <strong>@{{ error }}</strong>
                        </span>
                    </div>
                </div>
            </div><!-- /.form-row -->

            <hr>

            {{--<div class="lead">{{__('panel/buyer.work_place')}}</div>
            <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.work_company')}}</label>
                    <input v-model="user.work_company" name="work_company" type="text"
                           :class="(errors.work_company?'is-invalid':'') + ' form-control'">
                    <span v-if="errors.work_company" v-for="error in errors.work_company" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/buyer.work_phone')}}</label>
                    <input v-model="user.work_phone" name="work_phone" type="text"
                           :class="(errors.work_phone?'is-invalid':'') + ' form-control'">
                    <span v-if="errors.work_phone" v-for="error in errors.work_phone" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
            </div><!-- /.form-row -->


            <hr>
            --}}
            {{--<div class="lead">{{__('panel/buyer.address_residential')}}</div>
            <div class="form-row">
                <div class="col-12 col-md form-group">
                    <label>{{__('panel/buyer.address_region')}}</label>
                    <select ref="address_residential_selectRegion" v-model="user.address_residential_region" name="address_region" type="text"
                            :class="'form-control' + (errors.address_residential_region?' is-invalid':'')" v-on:change="changeRegion('address_residential')">
                        <option value="">{{__('panel/buyer.choose_region')}}</option>
                        <option v-for="(region, index) in inputs.address_residential.region.list" :value="region.regionid">@{{ region['name' + locale]}}</option>
                    </select>
                    <span v-if="errors.address_residential_region" v-for="error in errors.address_residential_region" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
                <div class="col-12 col-md form-group">
                    <label>{{__('panel/buyer.address_area')}}</label>
                    <select ref="address_residential_selectArea" :disabled="inputs.address_residential.area.disabled" v-model="user.address_residential_area" name="address_area" type="text"
                            :class="'form-control' + (errors.address_residential_area?' is-invalid':'')" v-on:change="changeArea('address_residential')">
                        <option value="">{{__('panel/buyer.choose_area')}}</option>
                        <option v-for="(area, index) in inputs.address_residential.area.list" :value="area.areaid">@{{ area['name' + locale]}}</option>
                    </select>
                    <span v-if="errors.address_residential_area" v-for="error in errors.address_residential_area" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
                <div class="col-12 col-md form-group">
                    <label>{{__('panel/buyer.address_city')}}</label>
                    <select ref="address_residential_selectCity" :disabled="inputs.address_residential.city.disabled" v-model="user.address_residential_city" name="address_city" type="text"
                            :class="'form-control' + (errors.address_residential_city?' is-invalid':'')" v-on:change="changeCity('address_residential')">
                        <option value="">{{__('panel/buyer.choose_city')}}</option>
                        <option v-for="(city, index) in inputs.address_residential.city.list" :value="city.cityid">@{{ city['name' + locale]}}</option>
                    </select>
                    <span v-if="errors.choose_city" v-for="error in errors.choose_city" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
            </div>
            <div class="form-row">
                <div class="col-12 col-md-9 form-group">
                    <label>{{__('panel/buyer.address')}}</label>
                    <input v-model="user.address_residential_address" name="address" type="text"
                           :class="'form-control' + (errors.address_residential_address?' is-invalid':'')">
                    <span v-if="errors.address_residential_address" v-for="error in errors.address_residential_address" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
                <div class="col-12 col-md-3 form-group">
                    <label>{{__('panel/buyer.home_phone')}}</label>
                    <input v-mask="'+############'" v-model="user.home_phone" name="home_phone" type="text"
                           :class="'form-control' + (errors.home_phone?' is-invalid':'')">
                    <span v-if="errors.home_phone" v-for="error in errors.home_phone" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
            </div>

            <hr>
--}}
            <div class="lead">{{__('panel/buyer.address_registration')}}</div>
            {{--<div class="form-row">
                <div class="col-12 col-md form-group">
                    <label>{{__('panel/buyer.address_region')}}</label>
                    <select ref="address_registration_selectRegion" v-model="user.address_registration_region" name="address_region" type="text"
                            :class="'form-control' + (errors.address_registration_region?' is-invalid':'')" v-on:change="changeRegion('address_registration')">
                        <option value="">{{__('panel/buyer.choose_region')}}</option>
                        <option v-for="(region, index) in inputs.address_registration.region.list" :value="region.regionid">@{{ region['name' + locale]}}</option>
                    </select>
                    <span v-if="errors.address_registration_region" v-for="error in errors.address_registration_region" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
                <div class="col-12 col-md form-group">
                    <label>{{__('panel/buyer.address_area')}}</label>
                    <select ref="address_registration_selectArea" :disabled="inputs.address_registration.area.disabled" v-model="user.address_registration_area" name="address_area" type="text"
                            :class="'form-control' + (errors.address_registration_area?' is-invalid':'')" v-on:change="changeArea('address_registration')">
                        <option value="">{{__('panel/buyer.choose_area')}}</option>
                        <option v-for="(area, index) in inputs.address_registration.area.list" :value="area.areaid">@{{ area['name' + locale]}}</option>
                    </select>
                    <span v-if="errors.address_registration_area" v-for="error in errors.address_registration_area" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
                <div class="col-12 col-md form-group">
                    <label>{{__('panel/buyer.address_city')}}</label>
                    <select ref="address_registration_selectCity" :disabled="inputs.address_registration.city.disabled" v-model="user.address_registration_city" name="address_city" type="text"
                            :class="'form-control' + (errors.address_registration_city?' is-invalid':'')" v-on:change="changeCity('address_registration')">
                        <option value="">{{__('panel/buyer.choose_city')}}</option>
                        <option v-for="(city, index) in inputs.address_registration.city.list" :value="city.cityid">@{{ city['name' + locale]}}</option>
                    </select>
                    <span v-if="errors.address_registration_city" v-for="error in errors.address_registration_city" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
            </div>--}}
            <div class="form-row">
                <div class="col-12 form-group">
                    <label>{{__('panel/buyer.address')}}</label>
                    <input v-model="user.address_registration_address" name="address" type="text"
                           :class="'form-control' + (errors.address_registration_address?' is-invalid':'')">
                    <span v-if="errors.address_registration_address" v-for="error in errors.address_registration_address" class="invalid-feedback" role="alert">
                        <strong>@{{ error }}</strong>
                    </span>
                </div>
            </div>

            <hr>

            <div class="lead">{{__('panel/buyer.passport')}}</div>
            <div class="form-row">
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('panel/buyer.passport_number')}}</label>
                        <input v-model="user.passport_number" name="passport_number" type="text"
                               :class="(errors.passport_number?'is-invalid':'') + ' form-control'">
                        <span v-if="errors.passport_number" v-for="error in errors.passport_number" class="invalid-feedback" role="alert">
                            <strong>@{{ error }}</strong>
                        </span>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('panel/buyer.passport_issued_by')}}</label>
                        <input v-model="user.passport_issued_by" name="passport_issued_by" type="text"
                               :class="(errors.passport_issued_by?'is-invalid':'') + ' form-control'">
                        <span v-if="errors.passport_issued_by" v-for="error in errors.passport_issued_by" class="invalid-feedback" role="alert">
                            <strong>@{{ error }}</strong>
                        </span>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('panel/buyer.passport_date_issue')}}</label>
                        {{--<date-picker value-type="format" v-model="user.passport_date_issue" type="date" format="DD.MM.YYYY" name="passport_date_issue"
                             :class="(errors.passport_date_issue?'is-invalid':'')"></date-picker> --}}
                        <input v-model="user.passport_date_issue" name="passport_date_issue" type="text"
                               :class="(errors.passport_date_issue?'is-invalid':'') + ' form-control'">
                        <span v-if="errors.passport_date_issue" v-for="error in errors.passport_date_issue" class="invalid-feedback" role="alert">
                            <strong>@{{ error }}</strong>
                        </span>
                    </div>
                </div>
            </div>
            {{--<div class="form-row">
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('panel/buyer.city_birth')}}</label>
                        <input v-model="user.city_birth" name="city_birth" type="text"
                               :class="(errors.city_birth?'is-invalid':'') + ' form-control'">
                        <span v-if="errors.city_birth" v-for="error in errors.city_birth" class="invalid-feedback" role="alert">
                            <strong>@{{ error }}</strong>
                        </span>
                    </div>
                </div>
            </div>--}}

            <div class="form-row">
                <div class="col-12 col-md-4">
                    <div class="form-group">

                        <input @change="updateFiles" accept=".png, .jpg, .jpeg, .gif" name="passport_selfie"
                               type="file" class="custom-file-input" id="passport_selfie">

                        <div v-if="user.files.passport_selfie.preview" class="preview">
                            <label for="passport_selfie" v-on:click="resetFiles('passport_selfie')">
                                <div class="img" :style="'background-image: url(' + user.files.passport_selfie.preview +');'"></div>
                                <div class="caption">{{__('panel/buyer.passport_selfie')}}</div>
                            </label>
                            @if($personals['passport_selfie']['path'])
                                <p class="download">
                                    <a href="{{localeRoute('download.url', ['url' => $personals['passport_selfie']['path'], 'name' =>__('panel/buyer.passport_selfie').' '.$buyer->fio ])}}">{{__('app.btn_download')}}</a>
                                </p>
                            @endif
                        </div>
                        <div v-else class="no-preview img-selfie">
                            <label for="passport_selfie">
                                <div class="img"></div>
                                <div class="caption">{{__('panel/buyer.passport_selfie')}}</div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-group">

                        <input @change="updateFiles" accept=".png, .jpg, .jpeg, .gif" name="passport_first_page"
                               type="file" class="custom-file-input" id="passport_first_page">

                        <div v-if="user.files.passport_first_page.preview" class="preview">
                            <label for="passport_first_page">
                                <div class="img" :style="'background-image: url(' + user.files.passport_first_page.preview +');'"></div>
                                <div class="caption">{{__('panel/buyer.passport_first_page')}}</div>
                            </label>
                            @if($personals['passport_first_page']['path'])
                                <p class="download">
                                    <a href="{{localeRoute('download.url', ['url' => $personals['passport_first_page']['path'], 'name' =>__('panel/buyer.passport_first_page').' '.$buyer->fio ])}}">{{__('app.btn_download')}}</a>
                                </p>
                            @endif
                        </div>
                        <div v-else class="no-preview img-passport-1">
                            <label for="passport_first_page">
                                <div class="img"></div>
                                <div class="caption">{{__('panel/buyer.passport_first_page')}}</div>
                            </label>
                        </div>

                    </div>
                </div>


            </div><!-- /.form-row -->



            <div class="form-controls">
                @if($buyer->status != 4)
                    <button type="button" v-on:click="setVerified" class="btn btn-success">{{__('panel/buyer.btn_verify')}}</button>
                @endif

                <button v-on:click="validatePersonals" type="button" class="btn ml-lg-auto btn-primary">{{__('app.btn_save')}}</button>
            </div><!-- /.form-controls -->
        </form>


    </div><!-- /.buyer -->

    @include('panel.buyer.parts.edit')

@endsection
