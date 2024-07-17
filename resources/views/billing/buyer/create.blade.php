@extends('templates.billing.app')
@section('title', __('billing/buyer.new_buyer'))
@section('class', 'buyer create')

@section('center-header-control')
    <a href="{{ url()->previous() }}" class="btn btn-orange">{{__('app.btn_back')}}</a>
@endsection

@section('content')

    <div id="newBuyer">
        <div v-if="orderCreated" class="order-success">
            <div class="">
                {{--            <img src="{{ asset('assets/icons/Badge.svg') }}" alt="badge">--}}

                <lottie-player
                    src="{{ asset('assets/json/confirm.lottie.json') }}"
                    background="transparent"
                    speed="1"
                    style="width: 300px; height: 300px"
                    loop
                    autoplay
                ></lottie-player>

            </div>
            <div class="d-flex flex-column justify-content-center">
                <div class="font-weight-900 font-size-40 text">
                    {{__('billing/buyer.new_buyer_created')}}
                </div>
                <p class="font-size-18 mt-3">{{ __('billing/buyer.txt_check') }}</p>

                <a class="btn btn-orange text-white modern-shadow w-50 mt-3"
                   href="{{localeRoute('billing.user.status')}}">{{__('billing/menu.user_status')}}</a>
            </div>
        </div>

        <div v-else>

            <div v-if="userStatus == 2" class="alert alert-info">
                {{__('billing/buyer.waiting_for_verification')}}
            </div>

            <div v-if="messages.length">
                <div class="alert alert-success" v-for="message in messages">@{{ message }}</div>
            </div>

            <div class="alert alert-info" v-for="item in errors.system" v-html="item"></div>
            <div class="font-size-24 font-weight-bold mb-3">{{__('billing/profile.txt_contacts')}}</div>

            <div v-if="step<3">
                {{--    mobile number input    --}}
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="inputPhone" class="form-label">{{__('auth.label_input_phone')}}</label>
                        <input @keyup.enter="checkPhone" v-mask="'+998 (##) ###-##-##'" v-model="user.phone.number"
                               type="text"
                               :class="'form-control modified' + (errors.phone?' is-invalid':'')" id="inputPhone"
                               :readonly="phoneAdded || user.phone.showInputSMSCode"
                               placeholder="{{__('auth.label_input_phone')}}">
                        <div class="error" style="padding: 5px 0" v-if="errors.phone">@{{ errors.phone }}</div>
                    </div>
                    <div v-if="!user.phone.showInputSMSCode" class="form-group col-md-4">
                        <label>&nbsp;</label>
                        <button
                            :disabled="!isPhoneValid(user.phone.number)"
                            v-if="step===1"
                            @click="checkPhone"
                            type="submit"
                            class="btn btn-orange"
                        >
                            {{__('app.btn_get_sms')}}
                        </button>
                    </div>
                </div>
                {{-- sms code input--}}
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="phoneInputSMSCode" class="form-label">{{__('auth.label_input_sms_code')}}</label>
                        <input :disabled="!user.phone.showInputSMSCode " v-mask="'####'" v-model="user.phone.smsCode"
                               type="text" placeholder="{{__('auth.label_input_sms_code')}}"
                               :readonly="phoneAdded"
                               @keyup.enter="checkPhoneSmsCode"
                               ref="phoneSMSCodeRef"
                               :class="'form-control modified' + (this.errors.sms?' is-invalid':'')"
                               id="phoneInputSMSCode">

                        <div class="error" v-for="item in errors.sms">@{{ item }}</div>
                    </div>
                    <div class="form-group col-md-4">
                        <label>&nbsp;</label>
                        <button
                            :disabled="String(user.phone.smsCode).length != 4"
                            v-if="user.phone.showInputSMSCode"
                            {{--                        v-if="step===1"--}}
                            v-on:click="checkPhoneSmsCode"
                            type="submit"
                            class="btn btn-orange"
                        >
                            {{__('app.btn_send')}}
                        </button>
                    </div>
                </div><!-- /.form-row -->

                <hr>

                <div class="card-container">
                    <div class="font-size-24 font-weight-bold mb-3">{{__('frontend/order.payment_card')}}</div>

                    <div class="buyer-card__add">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="inputCardNumber" class="form-label">{{__('panel/buyer.card_number')}}</label>
                                <input
                                    id="inputCardNumber"
                                    v-model="user.card.number"
                                    :readonly="!cardChange || cardAdded"
                                    :class="(errors.card_number?'is-invalid':'') + ' form-control modified'"
                                    v-mask="'#### #### #### ####'"
                                    placeholder="0000 0000 0000 0000"
                                >
                                <div class="error" v-for="item in errors.number">@{{ item }}</div>
                                <span v-if="cardValidation" class="error">@{{ cardValidation }}</span>
                            </div>

                            <div class="card-expired-date">
                                <label for="inputCardExp" class="form-label">{{__('panel/buyer.card_expired_date')}}</label>
                                <input
                                    id="inputCardExp"
                                    type="text"
                                    v-model="user.card.exp"
                                    :class="(errors.card_exp?'is-invalid':'') + ' form-control modified'"
                                    v-mask="'##/##'"
                                    :readonly="!cardChange || cardAdded"
                                    placeholder="00/00"
                                >
                                <div class="error" v-for="item in errors.card_exp">@{{ item }}</div>
                            </div>
                            <div class="form-group col-md-4" v-if="cardAdded === false">

                                <label>&nbsp;</label>
                                <label style="height: 50px"
                                       class="pl-3 rounded text-orange w-auto d-inline-flex align-items-center"
                                       v-if="countdownTimer > 0">

                                    @if (Config::get('app.locale') == 'ru')
                                        {{ __('panel/buyer.sms_resend_text')}}
                                        @{{ formattedCountdown.m }} : @{{ formattedCountdown.s }}
                                    @else
                                        @{{ formattedCountdown.m }} : @{{ formattedCountdown.s }}
                                        {{ __('panel/buyer.sms_resend_text')}}
                                    @endif
                                </label>
                                <button
                                    v-if="!countdownTimer || countdownTimer <= 0"
                                    :disabled="cardValidation"
                                    class="btn btn-orange"
                                    v-on:click="sendCardSmsCode"
                                >
                                    {{__('app.btn_get_sms')}}
                                </button>
                            </div>

                        </div>

                        <div v-if="!cardAdded" class="form-row">
                            <div class="form-group col-md-4">
                                <label for="form-label">{{__('auth.label_input_sms_code')}}</label>
                                <input id="sms-code-input" :disabled="!user.card.showInputSMSCode"
                                       v-model="user.card.smsCode"
                                       type="text"
                                       :class="(errors.smsCard?'is-invalid':'') + ' form-control modified'"
                                       v-mask="'####'">
                                <div class="error" v-for="item in errors.card" v-html="item"></div>
                                <div class="error" v-for="item in errors.smsCard">@{{ item }}</div>
                            </div>

                            <div class="form-group col-md-4">
                                <label>&nbsp;</label>
                                <button
                                    v-if="user.card.showInputSMSCode && !cardChange"
                                    v-on:click="checkCardSmsCode"
                                    type="button"
                                    class="btn btn-orange"
                                >
                                    {{__('panel/buyer.btn_card_save')}}
                                </button>
                            </div>

                        </div><!-- /.form-row -->

                    </div>

                    <div v-if="userStatus != 1" class="overlay"></div>

                    <div v-if="cardAdded" class="alert alert-success">{{__('panel/buyer.txt_card_added')}}</div>
                </div>

                <hr>

                {{-- SEND BUYER IMAGES --}}
                <div class="step-2">
                    <ul class="row justify-content-between col-6">
                        <li :class="[registerByPassport ? 'my__link text-decoration-none active' : 'my__link text-decoration-none']"
                            @click="checkRegisterPhoto(true)">{{__('panel/buyer.registration_by_passport')}}</li>
                        <li :class="[!registerByPassport ? 'my__link text-decoration-none active' : 'my__link text-decoration-none']"
                            @click="checkRegisterPhoto(false)">{{__('panel/buyer.registration_by_id_card')}}</li>
                    </ul>

                    <div v-if="registerByPassport" class="font-size-24 font-weight-bold mb-2">{{__('panel/buyer.passport_photo')}}</div>
                    <div v-else class="font-size-24 font-weight-bold mb-2">{{__('panel/buyer.id_photo')}}</div>

                    <div class="mb-4">{{__('panel/buyer.txt_img_help')}}</div>

                    <validation-observer v-if="registerByPassport" v-slot="{ invalid, handleSubmit }">
                        <form @submit.prevent="handleSubmit(addBuyerPhotos)">
                            <div class="form-row">
                                <div v-for="(item,index) in passportData" class="col-md-4 form-group d-flex justify-content-center flex-column text-center">
                                    <validation-provider
                                        :key="index"
                                        :name="item.name"
                                        :rules="'required|ext:jpg,jpeg,bmp,png'"
                                        v-slot="{errors, validate}"
                                    >
                                        <input
                                            @change="updateFiles($event, validate)"
                                            accept=".jpg, .jpeg, .png, .bmp"
                                            :name="item.name"
                                            type="file"
                                            class="d-none"
                                            :id="item.name"
                                        >
                                        <p class="error">@{{errors[0]}}</p>
                                    </validation-provider>

                                    <div v-if="user.files[item.name].preview" class="preview">
                                        <div class="img" :style="'background-image: url(' + user.files[item.name].preview +');'"></div>
                                        <label :for="item.name" @click="resetFiles(item.name)">
                                            @{{ item.label }}
                                            <img src="{{asset('images/icons/icon_close_red.svg')}}" :alt="item.name">
                                        </label>
                                    </div>

                                    <div v-else class="no-preview buyer-image" :class="item.name">
                                        <div class="img" :class="item.name"></div>
                                        <label :for="item.name" @click="resetFiles(item.name)"> @{{ item.label }}</label>
                                    </div>

                                    <div class="error" v-for="item in errors[item.name]">@{{ item }}</div>
                                </div>
                            </div>
                            <div class="form-controls">
                                <div class="info mb-3 mb-lg-0 d-flex align-items-center">{{ __('billing/buyer.txt_check') }}</div>
                                <button
                                    type="submit"
                                    :disabled='invalid || loading'
                                    class="btn btn-orange ml-lg-auto"
                                >
                                    {{__('app.btn_save')}}
                                </button>
                            </div>
                        </form>

                    </validation-observer>
                    {{-- registration by id card--}}
                    <validation-observer v-else v-slot="{ invalid, handleSubmit }">
                        <form @submit.prevent="handleSubmit(addBuyerPhotos)">
                            <div class="form-row">
                                <div v-for="(item,index) in idCardData" :key="index" class="col-12 col-md-3 form-group d-flex justify-content-center flex-column text-center">
                                    <validation-provider
                                        :name="item.name"
                                        rules="required|ext:jpg,jpeg,png,bmp"
                                        v-slot="{errors, validate}"
                                    >
                                        <input
                                            @change="updateFiles($event, validate)"
                                            accept=".jpg, .jpeg, .png, .bmp"
                                            :name="item.name"
                                            type="file"
                                            class="d-none"
                                            :id="item.name"
                                        >
                                        <p class="error">@{{errors[0]}}</p>
                                    </validation-provider>

                                    <div v-if="user.files[item.name].preview" class="preview">
                                        <div class="img" :style="'background-image: url(' + user.files[item.name].preview +');'"></div>
                                        <label v-on:click="resetFiles(item.name)">
                                            @{{ item.label }}
                                            <img src="{{asset('images/icons/icon_close_red.svg')}}">
                                        </label>
                                    </div>

                                    <div v-else class="no-preview" :class="item.name" v-on:click="resetFiles(item.name)">
                                        <div class="img" :class="item.name"></div>
                                        <label :for="item.name"> @{{ item.label }}</label>
                                    </div>

                                    <div class="error" v-for="item in errors[item.name]">@{{ item }}</div>
                                </div>
                            </div>
                            <div class="form-controls">
                                <div class="info mb-3 mb-lg-0 d-flex align-items-center">{{ __('billing/buyer.txt_check') }}</div>
                                <button
                                    type="submit"
                                    :disabled='invalid || loading'
                                    class="btn btn-orange ml-lg-auto"
                                >
                                    {{__('app.btn_save')}}
                                </button>
                            </div>
                        </form>

                    </validation-observer>

                    <div v-if="userStatus == 12 || userStatus == 2" class="alert alert-success">{{__('panel/buyer.txt_images_added')}}</div>

                    <hr>

                    <div v-if="userStatus != 5 && userStatus != 10 && userStatus != 11" class="overlay"></div>

            </div>

            <div class="add-guarant" >
                <validation-observer v-slot="{invalid, handleSubmit}">
                    <div class="font-size-24 font-weight-bold mb-2">
                        {{ __('billing/order.lbl_guarant_title') }}
                    </div>

                    <div class="mb-4 w-75">
                        {{ __('billing/order.lbl_guarant_text') }}
                    </div>

                    <div class="form-row">
                        <div class="col-12 col-md-4 form-group">
                            <div class="confidant-card">
                                <div class="title">
                                    {{ __('billing/order.lbl_guarant', ['number' => 1]) }}
                                </div>

                                <validation-provider
                                    name="guarantName-1"
                                    rules="required|min:5"
                                    v-slot="{errors}"
                                >
                                    <div class="form-card">
                                        <label for="name" class="form-label">
                                            {{ __('billing/order.lbl_fio') }}
                                        </label>

                                        <input
                                            v-model="guarantName1"
                                            type="text"
                                            id="name"
                                            class="form-control modified"
                                            @keypress="isLetter($event)"
                                            placeholder="{{ __('billing/order.lbl_fio') }}"
                                        />

                                        <p class="error">&nbsp;@{{ errors[0] }}</p>
                                    </div>
                                </validation-provider>

                                <validation-provider
                                    name="guarantPhone-1"
                                    :rules="`required|phone|guarantPersonalPhone:${user.phone.number}|guarantPhoneMatch:${guarantPhoneNumber2}`"
                                    v-slot="{errors}"
                                >
                                    <label for="phone" class="form-label">
                                        {{ __('billing/order.lbl_phone') }}
                                    </label>
                                    <input
                                        v-model="guarantPhoneNumber1"
                                        type="tel"
                                        v-mask="'+998 (##) ###-##-##'"
                                        placeholder="{{ __('billing/order.lbl_phone') }}"
                                        id="phone"
                                        class="form-control modified"
                                    />
                                    <p class="error">&nbsp;@{{ errors[0] }}</p>
                                </validation-provider>
                            </div>
                        </div>

                        <div class="col-12 col-md-4 form-group">
                            <div class="confidant-card">
                                <div class="title">
                                    {{ __('billing/order.lbl_guarant', ['number' => 2]) }}
                                </div>

                                <validation-provider
                                    name="guarantName-2"
                                    rules="required|min:5"
                                    v-slot="{errors}"
                                >
                                    <div class="form-card">
                                        <label for="name" class="form-label">
                                            {{ __('billing/order.lbl_fio') }}
                                        </label>

                                        <input
                                            v-model="guarantName2"
                                            type="text"
                                            id="name"
                                            @keypress="isLetter($event)"
                                            class="form-control modified"
                                            placeholder="{{ __('billing/order.lbl_fio') }}"
                                        />

                                        <p class="error">&nbsp;@{{ errors[0] }}</p>
                                    </div>
                                </validation-provider>

                                <validation-provider
                                    name="guarantPhone-2"
                                    :rules="`required|phone|guarantPersonalPhone:${user.phone.number}|guarantPhoneMatch:${guarantPhoneNumber1}`"
                                    v-slot="{ errors }"
                                >
                                    <label for="phone" class="form-label">
                                        {{ __('billing/order.lbl_phone') }}
                                    </label>
                                    <input
                                        v-model="guarantPhoneNumber2"
                                        type="tel"
                                        v-mask="'+998 (##) ###-##-##'"
                                        placeholder="{{ __('billing/order.lbl_phone') }}"
                                        id="phone"
                                        class="form-control modified"
                                    />
                                    <p class="error">&nbsp;@{{ errors[0] }}</p>
                                </validation-provider>
                            </div>
                        </div>

                    </div>

                    <div v-if="userStatus != 12" class="overlay"></div>

                    <div class="form-controls mt-0 pt-0">
                        <div class="info mb-3 mb-lg-0 d-flex align-items-center">{{ __('billing/buyer.txt_check') }}</div>
                        <button
                            @click="handleSubmit(addBuyerGuarants)"
                            type="submit"
                            :disabled='invalid || loading'
                            class="btn btn-orange ml-lg-auto"
                        >
                            {{__('app.btn_save')}}
                        </button>
                    </div>

                    <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>

                </validation-observer>
                <div v-if="userStatus == 2" class="alert alert-success">{{__('billing/buyer.guarants_added')}}</div>

            </div>

        </div>

    </div><!-- /#newBuyer -->

    @include('billing.buyer.parts.create')

@endsection
