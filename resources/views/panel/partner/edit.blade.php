@extends('templates.panel.app')

@section('title', $partner->name.' ('.__('panel/partner.id').' '.$partner->id.')')
@section('class', 'partners edit')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.partners.show', $partner->id)}}"><img
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

    <form class="partner create" method="POST" enctype="multipart/form-data" @submit="submitDisabled = true"
          action="{{localeRoute('panel.partners.update', $partner)}}">
        @csrf
        @method('PATCH')

        <input type="hidden" name="files_to_delete" :value="files.delete">

        {{--<div class="form-group">
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

        <hr>--}}


        <div class="form-row">
            <div class="col-12">
                <div class="lead">{{__('billing/profile.lbl_partner')}}</div>
            </div>

            <div class="form-group col-3 col-md-3">
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

            <div class="form-group col-3 col-md-3">
                <label>{{__('panel/partner.company_uniq_num')}}</label>
                <input
                    name="company_uniq_num"
                    type="text"
                    class="@error('company_uniq_num') is-invalid @enderror form-control modified"
                    v-model="formData.company_uniq_num"
                    v-number-only
                >
                @error('company_uniq_num')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-3 col-md-3">
                <div class="form-group">
                    <label for="date">{{__('panel/partner.company_date_pact')}}</label>
                    <date-picker
                        id="date"
                        value-type="format"
                        v-model="formData.date"
                        type="date"
                        format="DD.MM.YYYY"
                        placeholder="{{ __('panel/partner.company_date_pact') }}">
                    </date-picker>
                    <input type="hidden" name="company_date_pact" v-model="formData.date">
                </div>
            </div>

            <div class="col-3 col-md-3">
                <label>{{__('panel/partner.manager_id')}}</label>
                <select name="manager_id" class="form-control modified">
                    @foreach($managers as $manager)
                        <option
                            {{$manager->id==$partner->manager_id?'selected':''}} value="{{$manager->id}}">{{ $manager->fio }}</option>
                    @endforeach
                </select>

                @error('manager_id')
                <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                @enderror
            </div>

        </div>
        <br>

        <div class="form-row">
            <div class="form-group col-md-12 col-lg-6">

                {{--
                <label>{{__('panel/partner.company_short_description')}}</label>
                <textarea v-tinymce name="company_short_description" type="text" class="@error('company_short_description') is-invalid @enderror tinymce__preview-text form-control modified">{{old('company_short_description', $partner->short_description)}}</textarea>
                @error('company_short_description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
                --}}

                <div class="no-preview img-passport-1 row">
                    <div class="col-md-4 col-lg-4 mb-md-0 mb-3">
                        <input
                            accept=".png, .jpg, .jpeg"
                            name="logo"
                            type="file"
                            hidden
                            ref="logo"
                            id="logo-company"
                            @change="updateFiles"
                        >

                        <div v-if="preview" class="img">
                            <div style="width: 100%; height: 170px; background-size: contain; background-repeat: no-repeat; background-position: center;"
                                 :style="'background-image: url(' + preview +');'"></div>
                        </div>
                        <div v-else class="img">
                            <div class="dummy"></div>
                        </div>
                    </div>

                    <div class="col-md-8 col-lg-8" style="margin: auto 0;">
                        <div class="lead">Загрузите логотип компании</div>
                        <p>{{ __('billing/contract.photo_formats') }} JPG, PNG, BMP</p>
                        <label class="btn-orange btn" for="logo-company">{{ __('app.btn_upload') }}</label>
                    </div>
                </div>
            </div>

            <div class="form-group col-md-12 col-lg-6">
                <label>{{__('panel/partner.company_description')}}</label>
                <textarea
                    rows="6" name="company_description" type="text"
                    class="@error('company_description') is-invalid @enderror tinymce__detail-text form-control modified"
                    v-model="formData.company_description"
                >
                </textarea>
                @error('company_description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group col-md-24 col-lg-12">
                <label for="reverse_calc">Активировать обратный просчет наценки</label>
                <input
                    type="checkbox"
                    name="reverse_calc"
                    class="form-control mr-3"
                    style="width: 24px"
                    id="reverse_calc"
                    {{ $partner->reverse_calc ? 'checked' : ''  }}
                />
            </div>
        </div>

        <hr>

        <div class="lead">{{__('panel/partner.txt_categories')}}</div>

        <div class="form-row">
            @foreach($categories as $category)
                <div class="form-group col-md-4">
                    <div class="forms d-flex align-items-center justify-content-start">
                        <input type="checkbox" name="categories[{{ $category->id }}]" type="checkbox"
                               class="form-control mr-3"
                               style="width: 24px"
                               data-id="{{$category->id}}" {{ in_array($category->id,$catalogPartners) ? 'checked' : ''  }}
                        />
                        {{ $category->locale->title }}
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
                <input
                    v-mask="'+############'"
                    v-model="formData.user.phone" required name="phone"
                    type="text" class="@error('phone') is-invalid @enderror form-control modified">
                @error('phone')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.manager_phone')}}</label>
                <input
                    v-mask="'+############'"
                    v-model="formData.phone_manager"
                    name="phone_manager"
                    type="text" class="form-control modified"
                >
            </div>
        </div><!-- /.form-row -->

        <hr>

        <div class="form-row">

            <div class="col-12">
                <div class="lead">{{__('panel/partner.txt_law_info')}}</div>
            </div>

            <div class="col-12 col-md-6">

                <div class="form-group">
                    <label>{{__('panel/partner.company_name')}}</label>
                    <input
                        v-model="formData.company_name"
                        required name="company_name" type="text"
                        class="@error('company_name') is-invalid @enderror form-control modified"
                    >
                    @error('company_name')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_inn')}}</label>
                    <input v-model="formData.company_inn" required name="company_inn" type="text"
                           class="@error('company_inn') is-invalid @enderror form-control modified">
                    @error('company_inn')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_mfo')}}</label>
                    <input v-model="formData.company_mfo" name="company_mfo" type="text"
                           class="@error('company_mfo') is-invalid @enderror form-control modified">
                    @error('company_mfo')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_nds_number')}}</label>
                    <input
                        v-model="formData.company_nds_number"
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
                    <input v-model="formData.company_oked" name="company_oked" type="text"
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
                        <input v-model="formData.company_lat" name="company_lat" type="text"
                               class="@error('company_lat') is-invalid @enderror form-control modified">
                        @error('company_lat')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label>{{__('panel/partner.company_lon')}}</label>
                        <input v-model="formData.company_lon" name="company_lon" type="text"
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
                    <select name="region_id" class="form-control modified">
                        @foreach($regions as $region)
                            <option
                                {{$region->id==$partner->region_id?'selected':''}} value="{{$region->id}}">{{ $region->name }}</option>
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
                    <input v-model="formData.company_address" required name="company_address"
                           type="text" class="@error('company_address') is-invalid @enderror form-control modified">
                    @error('company_address')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_legal_address')}}</label>
                    <input v-model="formData.company_legal_address" required
                           name="company_legal_address" type="text"
                           class="@error('company_legal_address') is-invalid @enderror form-control modified">
                    @error('company_legal_address')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_bank_name')}}</label>
                    <input v-model="formData.company_bank_name" required name="company_bank_name"
                           type="text" class="@error('company_bank_name') is-invalid @enderror form-control modified">
                    @error('company_bank_name')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_payment_account')}}</label>
                    <input v-model="formData.company_payment_account" required
                           name="company_payment_account" type="text"
                           class="@error('company_payment_account') is-invalid @enderror form-control modified">
                    @error('company_payment_account')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_phone')}}</label>
                    <input v-model="formData.company_phone" name="company_phone" type="text"
                           class="@error('company_phone') is-invalid @enderror form-control modified">
                    @error('company_phone')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company_website')}}</label>
                    <input v-model="formData.company_website" name="company_website" type="text"
                           class="@error('company_website') is-invalid @enderror form-control modified">
                    @error('company_website')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('panel/partner.company')}}</label>
                    <select name="general_company_id"
                            class="form-control modified"
                            v-model="generalCompanyId"
                    >
                        <option disabled value="null">{{__('panel/partner.choose_company')}}</option>
                        <option
                            v-for="generalCompany in generalCompanies"
                            :key="generalCompany.id"
                            :value="generalCompany.id"
                            :selected="generalCompany.id === formData.general_company_id"
                        >
                            @{{generalCompany.name_ru}}
                        </option>
                    </select>
                </div>

            </div>
        </div><!-- /.form-row -->

        {{-- Partner Settings START --}}
        <div class="form-row">

            <div class="col-12">
                <div class="lead">{{__('panel/partner.settings')}}</div>
            </div>

            <div class="col-12 col-md-6">

                <div class="form-group">
                    <label>{{__('panel/partner.nds')}}</label>
                    <select name="nds" class="form-control modified">
                        <option :selected="formData.settings.nds == 0" value="0">{{__('app.no')}}</option>
                        <option :selected="formData.settings.nds == 1" value="1">{{__('app.yes')}}</option>
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
                        required
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
                        required
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
                    <select name="contract_confirmation" class="form-control modified">
                        <option
                            :selected="formData.settings.contract_confirmation == 0" value="0">{{__('app.no')}}</option>
                        <option
                            :selected="formData.settings.contract_confirmation == 1" value="1">{{__('app.yes')}}</option>
                    </select>

                    @error('contract_confirmation')
                    <span class="invalid-feedback" role="alert">v
                        <strong>{{ $message }}</strong>
                    </span>

                    @enderror
                </div>
            </div><!-- /.col-12 col-md-6 -->

            <div class="col-12 col-md-6">

                <div class="form-group">
                    <label>{{__('panel/partner.company_seller_coefficient')}}</label>
                    <input
                        v-model="formData.company_seller_coefficient"
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
                        type="number" class="@error('discount_6') is-invalid @enderror form-control modified"
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
                        required
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
                        required
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
                        required
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

{{--                <div class="form-group">--}}
{{--                    <label>{{__('panel/partner.discount_direct')}}</label>--}}
{{--                    <input value="{{old('discount_direct', @$partner->settings->discount_direct)}}" required--}}
{{--                    name="discount_direct" type="text"--}}
{{--                    class="@error('discount_direct') is-invalid @enderror form-control modified">--}}

{{--                    @error('discount_direct')--}}
{{--                        <span class="invalid-feedback" role="alert">--}}
{{--                            <strong>{{ $message }}</strong>--}}
{{--                        </span>--}}
{{--                    @enderror--}}
{{--                </div>--}}

                <div class="form-group">
                    <label>{{__('panel/partner.plans_extended_confirmation')}}</label>
                    <select name="plan_extended_confirm" class="form-control modified">
                        <option
                            :selected="formData.settings.plan_extended_confirm == 0" value="0">{{__('app.no')}}</option>
                        <option
                            :selected="formData.settings.plan_extended_confirm == 1" value="1">{{__('app.yes')}}</option>
                    </select>

                    @error('plan_extended_confirm')
                        <span class="invalid-feedback" role="alert">v
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
        </div>
        {{-- Partner Settings END --}}

        <!-- /.row START -->
        <div class="form-row col-12">

            <div class="col-3">
                <label>{{__('panel/partner.limit_3')}}</label>
                <select name="limit_3" class="form-control modified">
                    <option
                        :selected="formData.settings.limit_3 == 0" value="0">{{__('app.no')}}</option>
                    <option
                        :selected="formData.settings.limit_3 == 1" value="1">{{__('app.yes')}}</option>
                </select>

                @error('limit_3')
                <span class="invalid-feedback" role="alert">v
                        <strong>{{ $message }}</strong>
                    </span>

                @enderror
            </div>

            <div class="col-3">
                <label>{{__('panel/partner.limit_6')}}</label>
                <select name="limit_6" class="form-control modified">
                    <option
                        :selected="formData.settings.limit_6 == 0" value="0">{{__('app.no')}}</option>
                    <option
                        :selected="formData.settings.limit_6 == 1" value="1">{{__('app.yes')}}</option>
                </select>

                @error('limit_6')
                <span class="invalid-feedback" role="alert">v
                        <strong>{{ $message }}</strong>
                    </span>

                @enderror
            </div>
            <div class="col-3">
                <label>{{__('panel/partner.limit_9')}}</label>
                <select name="limit_9" class="form-control modified">
                    <option
                        :selected="formData.settings.limit_9 == 0" value="0">{{__('app.no')}}</option>
                    <option
                        :selected="formData.settings.limit_9 == 1"  value="1">{{__('app.yes')}}</option>
                </select>

                @error('limit_9')
                <span class="invalid-feedback" role="alert">v
                        <strong>{{ $message }}</strong>
                    </span>

                @enderror
            </div>

            <div class="col-3">
                <label>{{__('panel/partner.limit_12')}}</label>
                <select name="limit_12" class="form-control modified">
                    <option
                        :selected="formData.settings.limit_12 == 0" value="0">{{__('app.no')}}</option>
                    <option
                        :selected="formData.settings.limit_12 == 1" value="1">{{__('app.yes')}}</option>
                </select>

                @error('limit_12')
                    <span class="invalid-feedback" role="alert">v
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
                            @if( isset( old('tariffs')[$period->id] ) )
                            {{ old('tariffs')[$period->id] ? 'checked' : '' }}
                            @else
                                @foreach ($partner->tariffs as $tariff_period)
                                    @if( $tariff_period->id === $period->id )
                                        {{"checked"}}
                                    @endif
                                @endforeach
                            @endif
                            type="checkbox"
                            value=1
                            name="tariffs[{{ $period->id }}]"
                            class="form-control mr-3"
                            style="width: 24px"
                            data-id="{{ $period->id }}"
                            id="tariffs_{{ $period->id }}"
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


        <div class="form-row col-12">
            <div class="col-3">
                <label>Уточнение названия товаров</label>
                <select name="is_trustworthy" class="form-control modified" v-model="formData.is_trustworthy">
                    <option value="0">Нет</option>
                    <option value="1">Да</option>
                </select>

                @error('is_trustworthy')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{$message}}</strong>
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
                    @else
                        @if( $partner->settings->is_scoring_enabled )
                            {{"checked"}}
                        @endif
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
                    @else
                        @if( $partner->settings->is_mini_scoring_enabled )
                            {{"checked"}}
                        @endif
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

        <div class="form-controls col-12">
            <a class="btn btn-peach text-orange"
               href="{{localeRoute('panel.partners.show', $partner->id)}}">{{__('app.btn_cancel')}}</a>

            <button type="submit" :disabled="submitDisabled" class="btn btn-orange ml-lg-auto btn-submit">{{__('app.btn_save')}}</button>
        </div>
    </form>

    @include('templates.backend.parts.tinymce')

    <script>
        const oldData = {
            brand: @json(old('company_brand', $partner->brand)),
            company_uniq_num: '{{old('company_uniq_num', $partner->uniq_num)}}',
            date: '{{ Illuminate\Support\Carbon::parse($partner->date_pact)->format('d.m.Y') }}',
            company_description: @json(old('company_description', $partner->description)),
            user: {
                surname: '{{old('surname', @$partner->user->surname)}}',
                name: '{{old('name', @$partner->user->name)}}',
                phone: '{{old('phone', @$partner->user->phone)}}',
                patronymic:'{{old('patronymic', @$partner->user->patronymic)}}',
            },
            phone_manager: '{{old('phone_manager', @$partner->phone_manager)}}',
            company_name: '{{old('company_name', @$partner->name)}}',
            company_inn: '{{old('company_inn', @$partner->inn)}}',
            company_mfo: '{{old('company_mfo', @$partner->mfo)}}',
            company_nds_number: '{{old('company_nds_number', @$partner->nds_numder)}}',
            company_oked: '{{old('company_oked', @$partner->oked)}}',
            company_lat: '{{old('company_lat', @$partner->lat)}}',
            company_lon: '{{old('company_lon', @$partner->lon)}}',
            company_address: '{{old('company_address', @$partner->address)}}',
            company_legal_address: '{{old('company_legal_address', @$partner->legal_address)}}',
            company_bank_name: '{{old('company_bank_name', @$partner->bank_name)}}',
            company_payment_account: '{{old('company_payment_account', @$partner->payment_account)}}',
            company_phone: '{{old('company_phone', @$partner->phone)}}',
            company_website: '{{old('company_website', @$partner->website)}}',
            settings: {
                markup_1: '{{old('markup_1', @$partner->settings->markup_1)}}',
                markup_3: '{{old('markup_3', @$partner->settings->markup_3)}}',
                markup_6: '{{old('markup_6', @$partner->settings->markup_6)}}',
                markup_9: '{{old('markup_9', @$partner->settings->markup_9)}}',
                markup_12: '{{old('markup_12', @$partner->settings->markup_12)}}',
                markup_24: '{{old('markup_24', @$partner->settings->markup_24)}}',
                contract_confirmation: '{{old('contract_confirmation', @$partner->settings->contract_confirm)}}',
                discount_3: '{{old('discount_3', @$partner->settings->discount_3)}}',
                discount_6: '{{old('discount_6', @$partner->settings->discount_6)}}',
                discount_9: '{{old('discount_9', @$partner->settings->discount_9)}}',
                discount_12: '{{old('discount_12', @$partner->settings->discount_12)}}',
                discount_24: '{{old('discount_24', @$partner->settings->discount_24)}}',
                limit_for_24: '{{old('limit_for_24', @$partner->settings->limit_for_24)}}',
                plan_extended_confirm: '{{old('plan_extended_confirm', @$partner->settings->plan_extended_confirm)}}',
                limit_3: '{{old('limit_3', @$partner->settings->limit_3)}}',
                limit_6: '{{old('limit_6', @$partner->settings->limit_6)}}',
                limit_9: '{{old('limit_9', @$partner->settings->limit_9)}}',
                limit_12: '{{old('limit_12', @$partner->settings->limit_12)}}',
                {{--limit003: '{{old('limit003', @$partner->settings->limit003)}}',--}}
                nds: '{{old('nds', @$partner->settings->nds)}}',
            },
            is_allowed_online_signature: '{{old('is_allowed_online_signature', @$partner->is_allowed_online_signature)}}',
            company_seller_coefficient: '{{old('company_seller_coefficient', @$partner->seller_coefficient)}}',
            is_trustworthy: '{{ old('is_trustworthy', @$partner->is_trustworthy) }}' || 0,
            tariffs: @json($partner->tariffs),
        };

        var app = new Vue({
            el: '#app',
            data: {
                formData: oldData,
                submitDisabled: false,
                generalCompanyId: '{{ $partner->general_company_id }}' || '' ,
                phone_manager: '{{ $partner->manager_phone }}' || '',
                error: false,
                files: {
                    new: null,
                    old: '{{$partner->logo->id??null}}',
                    delete: null,
                },
                preview: '{{$partner->logo->GlobalPreview??null}}',
                generalCompanies: @json($generalCompanies),
                tariffs: @json($partner->tariffs),
                availablePeriods: @json($availablePeriods),
                isShowSettingsBlock: false
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
                    if (this.files.new.length > 0) {
                        this.preview = URL.createObjectURL(this.files.new[0]);
                        this.files.delete = this.files.old;
                    }
                },
            },
            watch: {
                generalCompanyId: {
                    handler(value){
                        const generalCompany = this.generalCompanies.find((item) => item.id == this.generalCompanyId)

                        if(generalCompany.is_mfo === 1) {
                            this.isShowSettingsBlock = true
                            return
                        }
                        this.isShowSettingsBlock = false
                    },
                    deep: true,
                    immediate: true
                }
            },
        });

    </script>

@endsection

