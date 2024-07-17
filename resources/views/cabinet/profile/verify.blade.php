@extends('templates.cabinet.app')
@section('title', __('cabinet/verify.title'))
@section('class', 'profile verify')

@section('content')

    <div id="verify">
        <div class="steps">
            <div :class="'step' + (step == 1? ' active': '')"><div class="number">1</div></div>
            <div :class="'step' + (step == 2? ' active': '')"><div class="number">2</div></div>
            <div :class="'step' + (step == 3? ' active': '')"><div class="number">3</div></div>
        </div>

        <div v-if="messages.length">
            <div class="alert alert-success" v-for="message in messages">@{{ message }}</div>
        </div>
        <div class="alert alert-danger" v-for="item in errors.system">@{{ item }}</div>

        <div class="step step1" v-if="step===2">  <!-- меняем местами шаги 1 и 2 сначала карта -->
            <div v-if="loading" class="loader">
                <img src="{{asset('images/media/loader.svg')}}">
            </div>
            <form class="edit" method="POST">
                @csrf
                @method('PATCH')

                {{--
                <div class="lead">{{__('cabinet/verify.personal_data')}}</div>

                <div class="form-row">
                    <div class="col-12 col-md form-group">
                        <label>{{__('panel/buyer.surname')}}</label>
                        <input required v-model="user.surname" name="surname" type="text"
                               :class="'form-control' + (errors.surname?' is-invalid':'')">
                        <div class="error" v-for="item in errors.surname">@{{ item }}</div>
                    </div>
                    <div class="col-12 col-md form-group">
                        <label>{{__('panel/buyer.name')}}</label>
                        <input required v-model="user.name" name="name" type="text"
                               :class="'form-control' + (errors.name?' is-invalid':'')">
                        <div class="error" v-for="item in errors.name">@{{ item }}</div>
                    </div>
                    <div class="col-12 col-md form-group">
                        <label>{{__('panel/buyer.patronymic')}}</label>
                        <input required v-model="user.patronymic" name="patronymic" type="text"
                               :class="'form-control' + (errors.patronymic?' is-invalid':'')">
                        <div class="error" v-for="item in errors.patronymic">@{{ item }}</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-md form-group">
                        <label>{{__('panel/buyer.birthday')}}</label>
                        <date-picker value-type="format" v-model="user.birthday" type="date" format="DD.MM.YYYY" name="birthday" :class="(errors.birthday?' is-invalid':'')"></date-picker>
                        <div class="error" v-for="item in errors.birthday">@{{ item }}</div>
                    </div>
                </div>

                <hr>

                <div class="lead">{{__('panel/buyer.address_residential')}}</div>

                <div class="form-row">
                    <div class="col-12 col-md form-group">
                        <label>{{__('panel/buyer.address_region')}}</label>
                        <select required ref="selectRegion" v-model="user.address_region" name="address_region" type="text"
                                :class="'form-control' + (errors.address_region?' is-invalid':'')" v-on:change="changeRegion()">
                            <option value="">{{__('panel/buyer.choose_region')}}</option>
                            <option v-for="(region, index) in region.list" :value="region.regionid">@{{ region['name' + locale]}}</option>
                        </select>
                        <div class="error" v-for="item in errors.address_region">@{{ item }}</div>
                    </div>
                    <div class="col-12 col-md form-group">
                        <label>{{__('panel/buyer.address_area')}}</label>
                        <select required ref="selectArea" :disabled="area.disabled" v-model="user.address_area" name="address_area" type="text"
                                :class="'form-control' + (errors.address_area?' is-invalid':'')" v-on:change="changeArea()">
                            <option value="">{{__('panel/buyer.choose_area')}}</option>
                            <option v-for="(area, index) in area.list" :value="area.areaid">@{{ area['name' + locale]}}</option>
                        </select>
                        <div class="error" v-for="item in errors.address_area">@{{ item }}</div>
                    </div>
                    <div class="col-12 col-md form-group">
                        <label>{{__('panel/buyer.address_city')}}</label>
                        <select ref="selectCity" :disabled="city.disabled" v-model="user.address_city" name="address_city" type="text"
                                :class="'form-control' + (errors.address_city?' is-invalid':'')" v-on:change="changeCity()">
                            <option value="">{{__('panel/buyer.choose_city')}}</option>
                            <option v-for="(city, index) in city.list" :value="city.cityid">@{{ city['name' + locale]}}</option>
                        </select>
                        <div class="error" v-for="item in errors.address_city">@{{ item }}</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-md-9 form-group">
                        <label>{{__('panel/buyer.address')}}</label>
                        <input required v-model="user.address" name="address" type="text"
                               :class="'form-control' + (errors.address?' is-invalid':'')">
                        <div class="error" v-for="item in errors.address">@{{ item }}</div>
                    </div>
                    <div class="col-12 col-md-3 form-group">
                        <label>{{__('panel/buyer.home_phone')}}</label>
                        <input v-mask="'+998#########'" v-model="user.home_phone" name="home_phone" type="text"
                               :class="'form-control' + (errors.home_phone?' is-invalid':'')">
                        <div class="error" v-for="item in errors.home_phone">@{{ item }}</div>
                    </div>
                </div>
                <hr>
                <div class="lead">{{__('panel/buyer.work_place')}}</div>
                <div class="form-group">
                    <label>{{__('panel/buyer.work_company')}}</label>
                    <input v-model="user.work_company" name="work_company" type="text"
                           :class="'form-control' + (errors.work_company?' is-invalid':'')">
                    <div class="error" v-for="item in errors.work_company">@{{ item }}</div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-md-4 form-group">
                        <label>{{__('panel/buyer.work_phone')}}</label>
                        <input v-mask="'+998#########'" v-model="user.work_phone" name="work_phone" type="text"
                               :class="'form-control' + (errors.work_phone?' is-invalid':'')">
                        <div class="error" v-for="item in errors.work_phone">@{{ item }}</div>
                    </div>
                </div>

                <hr> --}}

                <div class="lead">{{__('panel/buyer.passport_photo')}}</div>

                <div class="form-row">
                    <div class="col-12 col-sm-6 text-center col-md-4 form-group">
                        <input @change="updateFiles" accept=".png, .jpg, .jpeg, .gif"
                               name="passport_selfie"
                               type="file" class="d-none" id="passport_selfie">
                        <div v-if="user.files.passport_selfie.preview" class="preview">
                            <label for="passport_selfie" v-on:click="resetFiles('passport_selfie')">
                                <div class="img" :style="'background-image: url(' + user.files.passport_selfie.preview +');'"></div>
                                <div class="caption">{{__('panel/buyer.passport_selfie')}}</div>
                            </label>
                        </div>
                        <div v-else class="no-preview img-selfie">
                            <label for="passport_selfie">
                                <div class="img"></div>
                                <div class="caption">{{__('panel/buyer.passport_selfie')}}</div>
                            </label>
                        </div>
                        <div class="error" v-for="item in errors.passport_selfie">@{{ item }}</div>
                    </div>

                    <div class="col-12 col-sm-6 text-center col-md-4 form-group">

                        <input @change="updateFiles" accept=".png, .jpg, .jpeg, .gif"
                               name="passport_first_page"
                               type="file" class="d-none" id="passport_first_page">

                        <div v-if="user.files.passport_first_page.preview" class="preview">
                            <label for="passport_first_page">
                                <div class="img" :style="'background-image: url(' + user.files.passport_first_page.preview +');'"></div>
                                <div class="caption">{{__('panel/buyer.passport_first_page')}}</div>
                            </label>
                        </div>
                        <div v-else class="no-preview img-passport-1">
                            <label for="passport_first_page">
                                <div class="img"></div>
                                <div class="caption">{{__('panel/buyer.passport_first_page')}}</div>
                            </label>
                        </div>
                        <div class="error" v-for="item in errors.passport_first_page">@{{ item }}</div>
                    </div>

                    {{--<div class="col-12 col-sm-6 text-center col-md-4 form-group">
                        <input @change="updateFiles" accept=".png, .jpg, .jpeg, .gif"
                               name="passport_with_address" type="file" class="d-none"
                               id="passport_with_address">

                        <div v-if="user.files.passport_with_address.preview" class="preview">
                            <label for="passport_with_address">
                                <div class="img" :style="'background-image: url(' + user.files.passport_with_address.preview +');'"></div>
                                <span class="caption">{{__('panel/buyer.passport_with_address')}}</span>
                            </label>
                        </div>
                        <div v-else class="no-preview img-passport-2">
                            <label for="passport_with_address">
                                <div class="img"></div>
                                <span class="caption">{{__('panel/buyer.passport_with_address')}}</span>
                            </label>
                        </div>
                        <div class="error" v-for="item in errors.passport_with_address">@{{ item }}</div>
                    </div> --}}
                </div>

                <hr>

                <div class="form-controls">
                    <a class="btn btn-outline-secondary" href="{{localeRoute('cabinet.profile.show')}}">{{__('app.btn_cancel')}}</a>
                    <button v-on:click="validateStep" :disabled="waitValidate" type="button"
                            class="btn btn-primary ml-lg-auto">{{__('app.btn_continue')}}</button>
                </div>

            </form>
        </div>

        <div class="step" v-else-if="step===1">
            <div v-if="loading" class="loader">
                <img src="{{asset('images/media/loader.svg')}}">
            </div>
            <div v-else>
                {{--<div class="cards" v-if="user.cards != null">
                    <div class="list">
                        <div class="row">
                            <div class="col-12 col-md-4" v-for="item in user.cards" :key="item.id">
                                <div :class="'item ' + item.type">
                                    <div class="number">@{{ item.card_number }}</div>
                                    <div class="date">@{{ item.card_valid_date }}</div>
                                </div><!-- /.item -->
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="item add" v-on:click="openFormAddCard" type="submit"
                                     class="btn btn-success">
                                    <div class="number">{{__('card.btn_add')}}</div>
                                    <div class="types">{{__('card.uzcard_humo')}}</div>
                                </div>
                            </div>
                        </div><!-- /.row -->
                    </div><!-- /.list -->
                </div><!-- .cards -->
                <div v-else>
                    {{__('panel/buyer.txt_empty_card_list')}}
                </div> --}}


                <div class="buyer-card__add" v-if="showFormAddCards">
                    <hr>

                    <div class="lead">{{__('card.btn_add')}}</div>

                    <div class="form-row">
                        <div class="form-group col-6 col-md-4 col-lg-3">
                            <label for="inputCardNumber">{{__('panel/buyer.card_number')}}</label>
                            <input v-model="user.card.number" type="text" :class="'form-control' + (errors.cardNumber?' is-invalid':'')"
                                   id="inputCardNumber"
                                   v-mask="'#### #### #### ####'">
                            <div class="error" v-for="item in errors.cardNumber">@{{ item }}</div>
                        </div>
                        <div class="form-group col-6 col-md-4 col-lg-3">
                            <label for="inputCardExp">{{__('panel/buyer.card_expired_date')}}</label>
                            <input v-mask="'##/##'" v-model="user.card.exp" type="text" :class="'form-control' + (errors.exp?' is-invalid':'')"
                                   id="inputCardExp">
                            <div class="error" v-for="item in errors.exp">@{{ item }}</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6 col-md-4 col-lg-3">
                            <input :disabled="!showInputSMSCode" v-model="user.smsCode" type="text" :class="(errors.smsCode?'is-invalid':'') + ' form-control'" v-mask="'######'">
                            <div class="error" v-for="item in errors.smsCode">@{{ item }}</div>
                        </div>
                        <div class="form-group col-6 col-md-4 col-lg-3">
                            <button v-if="showInputSMSCode" v-on:click="checkSmsCode" type="submit"
                                    class="btn btn-success mr-4">{{__('panel/buyer.btn_card_save')}}</button>
                            <a class="btn btn-success" href="javascript:" v-on:click="sendSmsCode"
                               v-if="showInputSMSCode===false">{{__('app.btn_get_sms')}}</a>
                        </div>
                        <div class="form-group col-12 col-md-12 col-lg-12" v-if="showInputSMSCode===true">{{__('panel/buyer.txt_expire_sms_code')}}: @{{ timers }}</div>
                    </div>

                    <div class="error" v-for="item in errors.phone">@{{ item }}</div>

                </div>

                <hr>

                <div class="form-controls" id="adadadada">
                    <button v-on:click="nextStep" type="submit"
                            class="btn btn-primary ml-lg-auto" :disabled="!verifyComplete">{{__('app.btn_continue')}}</button>
                </div>
            </div>
        </div><!-- /.step -->

        <div class="step" v-else-if="step===3">
            <h5>{{__('cabinet/profile.header_verify_complete')}}</h5>
            <div class="checking-text">{!! __('cabinet/profile.txt_verify_checking')!!}</div>
            <hr>
            <div class="form-controls">
                <a href="{{localeRoute('cabinet.profile.show')}}" class="btn btn-success ml-lg-auto">{{__('app.btn_continue')}}</a>
            </div>
        </div><!-- /.step -->
    </div>

    @include('cabinet.profile.parts.verify')

@endsection
