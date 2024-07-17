@extends('templates.panel.app')

@section('title', __('panel/partner.header_create'))
@section('class', 'partners edit')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.partners.index')}}"><img
            src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="partner create" method="POST" enctype="multipart/form-data"
          action="{{localeRoute('panel.partners.store')}}" @submit="submitDisabled = true">
        @csrf
        {{-- Partner Header --}}
        <div class="form-row">

            <div class="col-12">
                <div class="lead">{{__('billing/profile.lbl_partner')}}</div>
            </div>

            <div class="form-group col-6 col-md-4">
                <label>{{__('panel/partner.company_parent')}}</label>
                <input v-model="parentCompanyId" v-number-only name="company_parent_id" type="text"
                       class="@error('company_parent_id') is-invalid @enderror form-control modified"
                >
                @error('company_parent_id')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror

                <div v-if="showPartner" class="dropdown-menu show user-info-dropdown">
                    <div class="dropdown-item" @click="setPartner(partner)">
                        @{{partner.name}} (@{{partner.id}})
                    </div>
                </div>
            </div>

            <div class="form-group col-6 col-md-4">
                <label>{{__('panel/partner.company_brand')}}</label>
                <input name="company_brand" type="text"
                       class="@error('company_brand') is-invalid @enderror form-control modified"
                       v-model="formData.brand">
                @error('company_brand')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group col-6 col-md-4">
                <label>{{__('panel/partner.company_uniq_num')}}</label>
                <input
                    required
                    name="company_uniq_num"
                    type="text"
                    class="@error('company_uniq_num') is-invalid @enderror form-control modified"
                    value="{{old('company_uniq_num')}}"
                />
                @error('company_uniq_num')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group col-6 col-md-4">
                <div class="form-group">
                    <label for="date">{{__('panel/partner.company_date_pact')}}</label>
                    <date-picker
                        id="date"
                        value-type="format"
                        v-model="formData.date_pact"
                        type="date"
                        format="DD.MM.YYYY"
                        placeholder="{{ __('panel/partner.company_date_pact') }}">
                    </date-picker>
                    <input type="hidden" name="company_date_pact" v-model="formData.date_pact">
                </div>
            </div>

            <div class="col-3 col-md-3">
                <label>{{__('panel/partner.manager_id')}}</label>
                <select name="manager_id" class="form-control modified" v-model="formData.manager_id">
                    @foreach($managers as $manager)
                        <option value="{{$manager->id}}">{{ $manager->fio }}</option>
                    @endforeach
                </select>

                @error('manager_id')
                <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                @enderror
            </div>
            <div class="col-3 col-md-3">
                <div class="form-group col-md-4">
                    <div class="forms d-flex align-items-center justify-content-start">
                        <input
                            type="checkbox"
                            name="reverse_calc"
                            class="form-control mr-3"
                            style="width: 24px"
                            id="reverse_calc"
                        />
                        <label for="reverse_calc">Активировать обратный просчет наценки</label>
                    </div>
                </div>
            </div>

        </div>

        <div class="form-row">
            {{--<div class="form-group col-md-6">
                <input @change="updateFiles" ref="logo" accept=".png, .jpg, .jpeg, .gif" name="logo" type="file"
                       class="d-none" id="customFile">

                <div v-if="preview" class="preview">
                    <button v-on:click="resetFiles" class="btn btn-sm btn-danger">
                        <img src="{{asset('images/icons/icon_close.svg')}}">
                    </button>
                    <img :src="preview" />
                </div>
                <div v-else class="no-preview">
                    <label class="btn btn-orange" for="customFile">{{__('billing/profile.btn_load_logo')}}</label>
                    <div class="help">{!! __('app.help_image_short')!!}</div>
                </div>
                @error('logo')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>--}}

            <div class="col-12 col-md-6 form-group d-flex align-items-center">
                {{--<div v-if="preview" class="preview">
                    <div
                        class="img"
                        :style="'background-image: url(' + preview +');'"
                    ></div>

                    <div>
                        <div class="lead">Загрузите логотип компании</div>
                        <p>Допустимые форматы изображения — JPG, PNG, BMP</p>
                        <label for="logo-company"></label>
                    </div>
                    <label v-on:click="resetFiles('passport_first_page')">
                        {{__('panel/buyer.passport_first_page')}}
                    </label>
                </div>--}}
                <div class="no-preview img-passport-1 row">
                    <div class="col-md-4 mb-md-0 mb-3">
                        <input @change="updateFiles"
                               accept=".png, .jpg, .jpeg, .gif"
                               name="logo"
                               type="file"
                               hidden
                               ref="logo"
                               id="logo-company"
                        >

                        <div v-if="preview" class="img">
                            <div style="width: 100%; height: 170px; background-size: cover"
                                 :style="'background-image: url(' + preview +');'"></div>
                        </div>
                        <div v-else class="img">
                            <div class="dummy"></div>
                        </div>
                        <div ref="validate" style="display: none"></div>
                    </div>

                    <div class="col-md-8" style="margin: auto 0;">
                        <div class="lead">Загрузите логотип компании</div>
                        <p style="width: 80%">{{ __('billing/contract.photo_formats') }} JPG, PNG, BMP</p>
                        <label class="btn-orange btn" for="logo-company">{{ __('app.btn_upload') }}</label>
                    </div>

                    @error('logo')
                    <span class="error" style="padding: 0 15px" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror

                </div>


            </div><!-- /.form-group -->

            <div class="form-group col-md-6">
                <label>{{__('panel/partner.company_description')}}</label>
                <textarea
                    rows="5"
                    name="company_description"
                    type="text"
                    v-model="formData.description"
                    class="@error('company_description') is-invalid @enderror tinymce__detail-text form-control modified"
                >
            </textarea>
                @error('company_description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <hr>

        <div class="lead">{{__('panel/partner.txt_categories')}}</div>

        <div class="form-row">
            @foreach($categories as $category)
                <div class="form-group col-md-4">
                    <div class="forms d-flex align-items-center justify-content-start">
                        <input
                            @if(isset(old('categories')[$category->id]))
                            {{ old('categories')[$category->id] ? 'checked' : '' }}
                            @endif
                            type="checkbox"
                            name="categories[{{ $category->id }}]"
                            class="form-control mr-3"
                            style="width: 24px"
                            data-id="{{$category->id}}"
                            id="category_{{ $category->id }}"
                        />
                        <label for="category_{{ $category->id }}">{{ $category->locale->title }}</label>
                    </div>
                </div>
            @endforeach

            @error('category')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <hr>

        {{-- Contact Information --}}
        <div class="lead">{{__('panel/partner.txt_contact_info')}}</div>

        <div class="form-row">
            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.user_surname')}}</label>
                <input v-model="formData.user.surname" required name="surname" type="text"
                       class="@error('surname') is-invalid @enderror form-control modified">
                @error('surname')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.user_name')}}</label>
                <input v-model="formData.user.name" required name="name" type="text"
                       class="@error('name') is-invalid @enderror form-control modified">
                @error('name')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.user_patronymic')}}</label>
                <input v-model="formData.user.patronymic" name="patronymic" type="text"
                       class="@error('patronymic') is-invalid @enderror form-control modified">
                @error('patronymic')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div><!-- /.form-row -->

        <div class="form-row">
            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.user_phone')}}</label>
                <input v-mask="'+998#########'" v-model="formData.user.phone" required name="phone" type="text"
                       class="@error('phone') is-invalid @enderror form-control modified">
            <!--                <input v-mask="'+998#########'" value="{{old('phone')}}" required name="phone" type="text" class="@error('phone') is-invalid @enderror form-control modified">-->
                @error('phone')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.manager_phone')}}</label>
                <input v-mask="'+998#########'" v-model="formData.phone_manager" name="phone_manager"
                       type="text" class="form-control modified">
            </div>
        </div><!-- /.form-row -->

        <hr>

        {{-- Legal Information --}}
        <div class="form-row">
            <div class="col-12">
                <div class="lead">{{__('panel/partner.txt_law_info')}}</div>
            </div>

            <div class="col-12 col-md-6">

                <div class="form-group">
                    <label>{{__('panel/partner.company_name')}}</label>
                    <input v-model="formData.name" required name="company_name" type="text"
                           class="@error('company_name') is-invalid @enderror form-control modified">
                    @error('company_name')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_inn')}}</label>
                    <input v-model="formData.inn" v-number-only required name="company_inn" type="text"
                           class="@error('company_inn') is-invalid @enderror form-control modified">
                    @error('company_inn')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_mfo')}}</label>
                    <input
                        v-model="formData.mfo" v-number-only
                        name="company_mfo" type="text"
                        class="@error('company_mfo') is-invalid @enderror form-control modified"
                    >
                    @error('company_mfo')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_nds_number')}}</label>
                    <input
                        v-model="formData.nds_numder"
                        v-number-only
                        name="company_nds_number"
                        type="text"
                        class="@error('company_nds_number') is-invalid @enderror form-control modified">
                    @error('company_nds_number')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_oked')}}</label>
                    <input v-model="formData.oked" v-number-only name="company_oked" type="text"
                           class="@error('company_oked') is-invalid @enderror form-control modified">
                    @error('company_oked')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group row">
                    <div class="col-md-6">
                        <label>{{__('panel/partner.company_lat')}}</label>
                        <input v-model="formData.lat" name="company_lat" type="text"
                               class="@error('company_lat') is-invalid @enderror form-control modified">
                        @error('company_lat')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label>{{__('panel/partner.company_lon')}}</label>
                        <input v-model="formData.lon" name="company_lon" type="text"
                               class="@error('company_lon') is-invalid @enderror form-control modified">
                        @error('company_lon')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.region')}}</label>
                    <select name="region_id" class="form-control modified" v-model="formData.region_id">
                        @foreach($regions as $region)
                            <option
                                {{$region->id===1726?'selected':''}}
                                value="{{$region->id}}">{{$region->name}}
                            </option>
                        @endforeach
                    </select>

                    @error('region_id')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div><!-- /.col-12 col-md-6 -->

            <div class="col-12 col-md-6">

                <div class="form-group">
                    <label>{{__('panel/partner.company_address')}}</label>
                    <input v-model="formData.address" required name="company_address" type="text"
                           class="@error('company_address') is-invalid @enderror form-control modified">
                    @error('company_address')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_legal_address')}}</label>
                    <input v-model="formData.legal_address" required name="company_legal_address" type="text"
                           class="@error('company_legal_address') is-invalid @enderror form-control modified">
                    @error('company_legal_address')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_bank_name')}}</label>
                    <input v-model="formData.bank_name" required name="company_bank_name" type="text"
                           class="@error('company_bank_name') is-invalid @enderror form-control modified">
                    @error('company_bank_name')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_payment_account')}}</label>
                    <input v-model="formData.payment_account" required v-number-only name="company_payment_account"
                           type="text"
                           class="@error('company_payment_account') is-invalid @enderror form-control modified">
                    @error('company_payment_account')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_phone')}}</label>
                    <input v-mask="'+998#########'" v-model="formData.phone" name="company_phone" type="text"
                           class="@error('company_phone') is-invalid @enderror form-control modified">
                    @error('company_phone')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_website')}}</label>
                    <input v-model="formData.website" name="company_website" type="text"
                           class="@error('company_website') is-invalid @enderror form-control modified">
                    @error('company_website')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group">
                    <label>{{__('panel/partner.company')}}</label>
                    <select name="general_company_id" class="form-control modified"
                            v-model="formData.general_company_id">
                        <option disabled value="null" selected>{{__('panel/partner.choose_company')}}</option>
                        <option
                            v-for="generalCompany in generalCompanies"
                            :key="generalCompany.id"
                            :value="generalCompany.id"
                            :selected="generalCompany.id === formData.general_company_id"
                        >@{{generalCompany.name_ru}}
                        </option>
                        {{--                        @foreach($generalCompanies as $generalCompany)--}}
                        {{--                            <option value="{{$generalCompany->id}}">{{$generalCompany->name_ru}}</option>--}}
                        {{--                        @endforeach--}}
                    </select>
                </div>
            </div>
        </div><!-- /.form-row -->

        <hr>

        {{-- Partner Settings START --}}
        <div class="form-row">
            <div class="col-12">
                <div class="lead">{{__('panel/partner.settings')}}</div>
            </div>

            <div class="col-12 col-md-6">

                <div class="form-group">
                    <label>{{__('panel/partner.nds')}}</label>
                    <select name="nds" class="form-control modified" v-model="formData.settings.nds">
                        <option :selected="formData.settings.nds === 0" value="0">{{__('app.no')}}</option>
                        <option :selected="formData.settings.nds === 1" value="1">{{__('app.yes')}}</option>
                    </select>

                    @error('nds')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.markup_1')}}</label>
                    <input
                        v-model="formData.settings.markup_1"
                        v-number-only
                        required
                        name="markup_1"
                        type="number"
                        class="@error('markup_1') is-invalid @enderror form-control modified"
                    >
                    @error('markup_1')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.markup_3')}}</label>
                    <input
                        v-model="formData.settings.markup_3"
                        v-number-only
                        required
                        name="markup_3"
                        type="number"
                        class="@error('markup_3') is-invalid @enderror form-control modified"
                    >
                    @error('markup_3')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.markup_6')}}</label>
                    <input
                        v-model="formData.settings.markup_6"
                        v-number-only
                        required
                        name="markup_6"
                        type="number"
                        class="@error('markup_6') is-invalid @enderror form-control modified"
                    >
                    @error('markup_6')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.markup_9')}}</label>
                    <input
                        v-model="formData.settings.markup_9"
                        v-number-only
                        required
                        name="markup_9"
                        type="number"
                        class="@error('markup_9') is-invalid @enderror form-control modified"
                    >
                    @error('markup_9')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.markup_12')}}</label>
                    <input
                        v-model="formData.settings.markup_12"
                        v-number-only
                        name="markup_12"
                        type="number"
                        class="@error('markup_12') is-invalid @enderror form-control modified"
                    >
                    @error('markup_12')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.markup_24')}}</label>
                    <input
                        v-model="formData.settings.markup_24"
                        v-number-only
                        name="markup_24"
                        type="number"
                        class="@error('markup_24') is-invalid @enderror form-control modified"
                    >
                    @error('markup_24')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>


                <div class="form-group">
                    <label>{{__('panel/partner.contract_confirmation')}}</label>
                    <select name="contract_confirmation" class="form-control modified"
                            v-model="formData.settings.contract_confirm" required>
                        <option :selected="formData.settings.contract_confirm === 0" value="0">{{__('app.no')}}</option>
                        <option :selected="formData.settings.contract_confirm === 1"
                                value="1">{{__('app.yes')}}</option>
                    </select>

                    @error('contract_confirmation')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

            </div><!-- /.col-12 col-md-6 -->

            <div class="col-12 col-md-6">

                <div class="form-group">
                    <label>{{__('panel/partner.company_seller_coefficient')}}</label>
                    <input
                        v-model="formData.seller_coefficient"
                        step=".01"
                        required
                        name="company_seller_coefficient"
                        type="number"
                        class="@error('company_seller_coefficient') is-invalid @enderror form-control modified"
                    >
                    @error('company_seller_coefficient')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.discount_3')}}</label>
                    <input
                        v-model="formData.settings.discount_3"
                        v-number-only
                        required
                        name="discount_3"
                        type="number"
                        class="@error('discount_3') is-invalid @enderror form-control modified"
                    >
                    @error('discount_3')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.discount_6')}}</label>
                    <input
                        v-model="formData.settings.discount_6"
                        v-number-only
                        required
                        name="discount_6"
                        type="number"
                        class="@error('discount_6') is-invalid @enderror form-control modified"
                    >
                    @error('discount_6')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.discount_9')}}</label>
                    <input
                        v-model="formData.settings.discount_9"
                        v-number-only
                        required
                        name="discount_9"
                        type="number"
                        class="@error('discount_9') is-invalid @enderror form-control modified"
                    >
                    @error('discount_9')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.discount_12')}}</label>
                    <input
                        v-model="formData.settings.discount_12"
                        v-number-only
                        name="discount_12"
                        type="number"
                        class="@error('discount_12') is-invalid @enderror form-control modified"
                    >
                    @error('discount_12')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.discount_24')}}</label>
                    <input
                        v-model="formData.settings.discount_24"
                        v-number-only
                        name="discount_24"
                        type="number"
                        class="@error('discount_24') is-invalid @enderror form-control modified"
                    >
                    @error('discount_24')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.limit_for_24')}}</label>
                    <input
                        v-model="formData.settings.limit_for_24"
                        v-number-only
                        name="limit_for_24"
                        type="number"
                        class="@error('limit_for_24') is-invalid @enderror form-control modified"
                    >
                    @error('limit_for_24')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                {{--<div class="form-group">
                    <label>{{__('panel/partner.discount_direct')}}</label>
                    <input :value="partnerData.settings?.discount_direct" required name="discount_direct" type="text"
                           class="@error('discount_direct') is-invalid @enderror form-control modified">
                    @error('discount_direct')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>--}}

                <div class="form-group">
                    <label>{{__('panel/partner.plans_extended_confirmation')}}</label>
                    <select name="plan_extended_confirm" class="form-control modified"
                            v-model="formData.settings.plan_extended_confirm" required>
                        <option :selected="formData.settings.plan_extended_confirm === 0"
                                value="0">{{__('app.no')}}</option>
                        <option :selected="formData.settings.plan_extended_confirm === 1"
                                value="1">{{__('app.yes')}}</option>
                    </select>

                    @error('plan_extended_confirm')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>


            </div>
        </div>
        {{-- Partner Settings END --}}


        <!-- /.row START -->
        <div class="form-row form-group">

            <div class="col-3">
                <label>{{__('panel/partner.limit_3')}}</label>
                <select name="limit_3" class="form-control modified" v-model="formData.settings.limit_3">
                    <option :selected="formData.settings.limit_3 === 0" value="0">{{__('app.no')}}</option>
                    <option :selected="formData.settings.limit_3 === 1" value="1">{{__('app.yes')}}</option>
                </select>

                @error('limit_3')
                <span class="invalid-feedback" role="alert">v
                    <strong>{{ $message }}</strong>
                </span>

                @enderror
            </div>
            <div class="col-3">
                <label>{{__('panel/partner.limit_6')}}</label>
                <select name="limit_6" class="form-control modified" v-model="formData.settings.limit_6">
                    <option :selected="formData.settings.limit_6 === 0" value="0">{{__('app.no')}}</option>
                    <option :selected="formData.settings.limit_6 === 1" value="1">{{__('app.yes')}}</option>
                </select>

                @error('limit_6')
                <span class="invalid-feedback" role="alert">v
                    <strong>{{ $message }}</strong>
                </span>

                @enderror
            </div>
            <div class="col-3">
                <label>{{__('panel/partner.limit_9')}}</label>
                <select name="limit_9" class="form-control modified" v-model="formData.settings.limit_9">
                    <option :selected="formData.settings.limit_9 === 0" value="0">{{__('app.no')}}</option>
                    <option :selected="formData.settings.limit_9 === 1" value="1">{{__('app.yes')}}</option>
                </select>

                @error('limit_9')
                <span class="invalid-feedback" role="alert">v
                    <strong>{{ $message }}</strong>
                </span>

                @enderror
            </div>
            <div class="col-3">
                <label>{{__('panel/partner.limit_12')}}</label>
                <select name="limit_12" class="form-control modified" v-model="formData.settings.limit_12">
                    <option :selected="formData.settings.limit_12 === 0" value="0">{{__('app.no')}}</option>
                    <option :selected="formData.settings.limit_12 === 1" value="1">{{__('app.yes')}}</option>
                </select>

                @error('limit_12')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>

                @enderror
            </div>
        </div>
        <!-- /.row END -->


        <div class="form-row" v-if="isShowSettingsBlock">
            @foreach($availablePeriods as $period)
                <div class="form-group col-md-4">
                    <div class="forms d-flex align-items-center justify-content-start">
                        <input
                            @if(isset(old('tariffs')[$period->id]))
                            {{ old('tariffs')[$period->id] ? 'checked' : '' }}
                            @endif
                            type="checkbox"
                            value=1
                            name="tariffs[{{ $period->id }}]"
                            class="form-control mr-3"
                            style="width: 24px"
                            data-id="{{ $period->id }}"
                            id="tariffs{{ $period->id }}"
                        />
                        <label for="tariffs{{ $period->id }}">{{ $period->title_ru }}</label>
                    </div>
                </div>
            @endforeach

            @error('tariffs')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>


        <hr>
        <div class="form-row">
            <div class="col-3">
                <label>Уточнение названия товаров</label>
                <select name="is_trustworthy" class="form-control modified" v-model="formData.is_trustworthy">
                    <option value="0">Нет</option>
                    <option value="1">Да</option>
                </select>

                @error('is_trustworthy')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label>{{__('panel/partner.is_allowed_to_online_signing')}}</label>
                <select name="is_allowed_online_signature" class="form-control modified">
                    <option
                        :selected="formData.is_allowed_online_signature == 0" value="0">{{__('app.no')}}</option>
                    <option
                        :selected="formData.is_allowed_online_signature == 1" value="1">{{__('app.yes')}}</option>
                </select>
                @error('is_allowed_online_signature')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>

                @enderror
            </div>

            <div class="col-2 ml-5">
                <label for="is_scoring_enabled">Включить основной скоринг</label>
                <input
                    type="checkbox"
                    name="is_scoring_enabled"
                    class="form-control mr-3"
                    style="width: 24px"
                    value=1
                    id="is_scoring_enabled"
                    @if(old('is_scoring_enabled'))
                        {{ old('is_scoring_enabled') ? 'checked' : '' }}
                    @endif
                    v-model="formData.settings.is_scoring_enabled"
                />

                @error('is_scoring_enabled')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-2 ml-5">
                <label for="is_mini_scoring_enabled">Включить мини-скоринг</label>
                <input
                    type="checkbox"
                    name="is_mini_scoring_enabled"
                    class="form-control mr-3"
                    style="width: 24px"
                    value=1
                    id="is_mini_scoring_enabled"
                    @if(old('is_mini_scoring_enabled'))
                        {{ old('is_mini_scoring_enabled') ? 'checked' : '' }}
                    @endif
                    v-model="formData.settings.is_mini_scoring_enabled"
                />

                @error('is_mini_scoring_enabled')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

        </div>

        {{-- Actions (Save & Cancel) --}}
        <div class="form-controls">
            <a class="btn btn-peach text-orange"
               href="{{localeRoute('panel.partners.index')}}">{{__('app.btn_cancel')}}</a>

            <button
                type="submit"
                class="btn btn-orange ml-lg-auto btn-submit"
                :disabled="submitDisabled"
            >
                {{__('app.btn_save')}}
            </button>
        </div>

    </form>

    <script>
    const oldData = {
        brand: '{{old('company_brand')}}',
        description: '{{old('company_description')}}' || '',
        surname: '{{old('surname')}}' || '',
        name: '{{old('company_name')}}',
        inn: '{{old('company_inn')}}',
        mfo: '{{old('company_mfo')}}',
        nds_numder: '{{old('company_nds_number')}}',
        oked: '{{old('company_oked')}}',
        lat: '{{old('company_lat')}}',
        lon: '{{old('company_lon')}}',
        address: '{{old('company_address')}}',
        legal_address: '{{old('company_legal_address')}}',
        bank_name: '{{old('company_bank_name')}}',
        payment_account: '{{old('company_payment_account')}}',
        phone: '{{old('company_phone')}}',
        website: '{{old('company_website')}}',
        user: {
            name: '{{old('name')}}',
            surname: '{{old('surname')}}',
            patronymic: '{{old('patronymic')}}',
            phone: '{{old('phone')}}',
        },
        settings: {
            markup_1: '{{old('markup_1')}}',
            markup_3: '{{old('markup_3')}}',
            markup_6: '{{old('markup_6')}}',
            markup_9: '{{old('markup_9')}}',
            markup_12: '{{old('markup_12')}}',
            markup_24: '{{old('markup_24')}}',
            nds: '{{old('nds')}}' || 0,
            discount_3: '{{old('discount_3')}}',
            discount_6: '{{old('discount_6')}}',
            discount_9: '{{old('discount_9')}}',
            discount_12: '{{old('discount_12')}}',
            discount_24: '{{old('discount_24')}}',
            limit_for_24: '{{old('limit_for_24')}}',
            plan_extended_confirm: '{{old('plan_extended_confirm')}}' || 0,
            limit_3: '{{old('limit_3')}}' || 0,
            limit_6: '{{old('limit_6')}}' || 0,
            limit_9: '{{old('limit_9')}}' || 0,
            limit_12: '{{old('limit_12')}}' || 0,
            {{--limit003: '{{old('limit003')}}' || 1,--}}
            contract_confirm: '{{old('contract_confirmation')}}' || 0,
        },
        seller_coefficient: '{{old('company_seller_coefficient')}}',
        phone_manager: '{{old('phone_manager')}}' || '',
        manager_id: '{{old('manager_id')}}' || 'disabled',
        general_company_id: '{{old('general_company_id')}}' || 'null',
        region_id: '{{old('region_id')}}' || 1726,
        date_pact: '{{old('company_date_pact')}}' || '',
        is_trustworthy: '{{ old('is_trustworthy') }}' || 0,
        is_allowed_online_signature: '{{old('is_allowed_online_signature', @$partner->is_allowed_online_signature)}}',
        tariffs: @json(@$partner->tariffs),
    };
    var app = new Vue({
        el: '#app',
        data: {
            parentCompanyId: '{{old('company_parent_id')}}' || null,
            formData: oldData,
            categories: @json($categories),
            generalCompanies: @json($generalCompanies),
            error: false,
            errors: [],
            files: {
                new: null,
                old: null,
                delete: null,
            },
            preview: null,
            partner: null,
            showPartner: false,
            submitDisabled: false,
            availablePeriods: @json($availablePeriods),
            isShowSettingsBlock: false
        },
        watch: {
            parentCompanyId: async function () {
                if (this.parentCompanyId !== null && this.parentCompanyId.length > 5) {
                    try {
                        const { data: partner } = await axios.post(`/api/v1/partners/detail/${this.parentCompanyId}`);
                        if (partner.status === 'success') {
                            this.showPartner = true;
                            this.partner = partner.data;
                        } else {
                            this.errors = partner.response.errors;
                        }

                    } catch (e) {
                        console.log(e.message);
                    }
                }
            },
            formData: {
                handler(value){
                    const generalCompany = this.generalCompanies.find((item) => item.id == value.general_company_id)

                    if(generalCompany?.is_mfo === 1) {
                        this.isShowSettingsBlock = true
                        return
                    }

                    this.isShowSettingsBlock = false
                },
                deep: true
            }
        },
        methods: {
            setPartner(partner) {
                this.showPartner = false;
                this.formData = {
                    ...this.formData, ...partner,
                    date_pact: moment(partner.date_pact).format('DD.MM.YYYY'),
                };
            },
            resetFiles() {
                this.preview = null;
                this.$refs.logo.value = '';
                this.files.new = null;
                this.files.delete = this.files.old;
            },
            updateFiles() {
                this.files.new = this.$refs.logo.files;
                let arr = ['image/jpeg', 'image/png', 'image/jpg', 'image/bmp'];
                const divText = this.$refs.validate;
                if (this.files.new.length > 0 && arr.includes(this.files.new[0]['type'])) {
                    this.preview = URL.createObjectURL(this.files.new[0]);
                    this.files.delete = this.files.old;
                    divText.textContent = '';
                } else {
                    divText.textContent = '{{__('panel/partner.company_image')}}';
                    divText.style.display = 'block';
                    divText.style.color = 'red';
                    this.preview = '';
                }
            },
        },
    });

    </script>

@endsection
