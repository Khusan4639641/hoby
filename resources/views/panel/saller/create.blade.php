@extends('templates.panel.app')

@section('title', __('panel/sallers.header_create'))
@section('class', 'partners edit')

@section('center-header-prefix')
    <a class="link-back" href="{{ localeRoute('panel.sallers.index') }}"><img src="{{ asset('images/icons/icon_arrow_green.svg') }}"></a>
@endsection

@section('content')

    <form method="POST" enctype="multipart/form-data" action="{{ localeRoute('panel.sallers.store') }}">
        @csrf
        <div class="form-group">
            <input @change="updateFiles" ref="passport" accept=".png, .jpg, .jpeg, .gif" name="passport" type="file" class="d-none" id="passport" required>

            <div v-if="preview" class="preview">
                <button v-on:click="resetFiles" class="btn btn-sm btn-danger">
                    <img src="{{asset('images/icons/icon_close.svg')}}">
                </button>
                <img :src="preview" />
            </div>

            <div v-else class="no-preview">
                <label class="btn btn-primary" for="passport">{{__('panel/buyer.passport')}}</label>
                <div class="help">{!! __('app.help_image_short')!!}</div>
            </div>

            @error('passport')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
        </div>

        <div class="form-group">
            <input @change="updateFiles" ref="passport_address" accept=".png, .jpg, .jpeg, .gif" name="passport_address" type="file" class="d-none" id="passport_address" required>

            <div v-if="previewAddress" class="preview">
                <button v-on:click="resetFiles" class="btn btn-sm btn-danger">
                    <img src="{{asset('images/icons/icon_close.svg')}}">
                </button>
                <img :src="previewAddress" />
            </div>
            <div v-else class="no-preview">
                <label class="btn btn-primary" for="passport_address">{{__('panel/buyer.passport_with_address')}}</label>
                <div class="help">{!! __('app.help_image_short')!!}</div>
            </div>
            @error('passport_address')
            <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
            @enderror
        </div>

        <hr>

        <div class="form-row">
            <div class="form-group col-12 col-sm-6 col-md-4">
                <label>{{__('billing/order.lbl_partner_phone')}}</label>
                <input v-mask="'+998#########'" required type="text"
                       placeholder="{{__("billing/order.txt_search_by_phone")}}"
                       :class="'form-control ' + (processing_user?'processing':'')"
                       v-model="strSearchPhone" />

                <div v-if="sallers.length > 0" class="dropdown-menu show user-info-dropdown">
                    <a v-for="(item, index) in sallers" :key="item.id" class="dropdown-item"
                       v-on:click="setBuyer(index)">
                        {{--                                    <div class="preview" v-if="item.personals && item.personals.passport_selfie"--}}
                        {{--                                         :style="'background-image: url(/storage/' + item.personals.passport_selfie.path + ');'"></div>--}}
                        {{--                                    <div v-else class="preview dummy"></div>--}}

                        @{{item.surname}} @{{item.name}} @{{item.patronymic}}
                        (@{{item.phone}})
                    </a>
                </div>
            </div>
        </div><!-- /.form-row -->


        <div class="form-row">
            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.company_brand')}}</label>

                <select class="form-control" name="company_id" id="company_brand">
                    @if($companies)
                        @foreach($companies as $company)
                            <option
                                @if($company->id == old('company_id'))selected @endif
                                value="{{$company->id}}"
                            >
                                {{$company->id}} - {{$company->brand}}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>

        <hr>

        <div class="lead">{{__('panel/partner.txt_contact_info')}}</div>

        <div class="form-row">
            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.user_surname')}}</label>
                <input value="{{old('surname')}}" required name="surname" v-model="surname" type="text" class="@error('surname') is-invalid @enderror form-control">
                @error('surname')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.user_name')}}</label>
                <input value="{{old('name')}}" required name="name" v-model="name" type="text" class="@error('name') is-invalid @enderror form-control">
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.user_patronymic')}}</label>
                <input value="{{old('patronymic')}}" name="patronymic" v-model="patronymic" type="text" class="@error('patronymic') is-invalid @enderror form-control">
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
                <input v-mask="'+############'" value="{{old('phone')}}" required name="phone" v-model="phone" type="text" class="@error('phone') is-invalid @enderror form-control">
                @error('phone')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/buyer.pinfl')}}</label>
                <input value="{{old('pinfl')}}" required name="pinfl" v-model="pinfl" type="text" class="@error('pinfl') is-invalid @enderror form-control">
                @error('pinfl')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

        </div><!-- /.form-row -->

        <hr>

        <div class="lead">Пластиковая карта</div>

        <div class="form-row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <div class="font-size-16">{{__('panel/buyer.card_number')}}</div>
                    <input
                        id="inputCardNumber"
                        type="text"
                        name="card"
                        :class="(errors.card_number?'is-invalid':'') + ' form-control modified'"
                        v-mask="'#### #### #### ####'"
                        placeholder="0000 0000 0000 0000"
                        required
                    >
                    <div class="error" v-for="item in errors.number">@{{ item }}</div>
                </div>
            </div>
            <div class="card-expired-date">
                <div class="form-group">
                    <div class="font-size-16">{{__('panel/buyer.card_expired_date')}}</div>
                    <input
                        id="inputCardExp"
                        type="text"
                        name="exp"
                        :class="(errors.card_exp?'is-invalid':'') + ' form-control modified'"
                        v-mask="'##/##'"
                        placeholder="00/00"
                        required
                    >
                    <div class="error" v-for="item in errors.card_exp">@{{ item }}</div>
                </div>
            </div>
        </div>

        <hr>

        <div class="lead">{{__('cabinet/profile.passport')}}</div>

        <div class="form-row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <div class="font-size-16">{{__('cabinet/profile.passport_number')}}</div>
                    <input
                        v-model="passport_number"
                        id="passport_number"
                        type="text"
                        name="passport_number"
                        :class="(errors.passport_number?'is-invalid':'') + ' form-control modified'"
                        required
                    >
                    <div class="error" v-for="item in errors.passport_number">@{{ item }}</div>
                </div>
            </div>
        </div>

        <div class="form-controls">
            <a class="btn btn-outline-secondary" href="{{localeRoute('panel.sallers.index')}}">{{__('app.btn_cancel')}}</a>
            <button type="submit" class="btn btn-outline-primary ml-lg-auto">{{__('app.btn_save')}}</button>
        </div>

    </form>

    <script>
        $(document).ready(function () {
            $('#company_brand').select2({
                theme: 'bootstrap4'
            });
        })

        var app = new Vue({
            el: '#app',
            data: {
                errors:[],
                error: false,
                filter: {
                    date: '{{ !empty($partner) ? Illuminate\Support\Carbon::parse($partner->date_pact)->format('d.m.Y') : null }}'
                },
                files: {
                    passport: {
                        old: '',
                        new: null,
                        delete: ''
                    },
                    passport_address: {
                        old: '',
                        new: null,
                        delete: ''
                    },
                },

                preview: null,
                previewAddress: null,

                processing_user: false,
                strSearchPhone: null,
                sallers: [],
                saller: null,

                name: null,
                pinfl: null,
                patronymic: null,
                surname: null,
                phone: null,
                passport_number: null,
                files_to_delete: [],

            },
            methods: {
                resetFiles(e) {
                    let input = e.target.name,
                        files = e.target.files;
                    this.files[input].new = null;
                    this.files[input].delete = null;
                    this.files[input].value = null;
                   if(input=='passport') this.preview = null;
                   if(input=='passport_address') this.preview_address = null;
                },
                updateFiles(e) {
                    let input = e.target.name,
                        files = e.target.files;
                    if (files.length > 0) {
                        this.files[input].new = files[0];
                        const previewUrl = URL.createObjectURL(files[0]);
                        this.files[input].preview = previewUrl;
                        this.initPhotoViewer(previewUrl, input);
                    }
                    if (this.files[input].old) {
                        this.files_to_delete.push(this.files[input].old);
                    }
                },
                update(item){
                    this.name = item.name; //  item.locale.name;
                    this.pinfl = item.pinfl; //  item.locale.name;
                    this.surname = item.surname; //  item.locale.name;
                    this.patronymic = item.patronymic; //  item.locale.name;
                    this.pinfl = item.personals.pinfl; //  item.locale.name;
                    this.phone = item.phone; //  item.locale.name;
                    this.passport_number = item.personals.passport_number; //passport number !!without hash!!
                },
                setBuyer(index){
                    this.saller = this.sallers[index];
                    this.update(this.saller)
                    this.sallers = [];
                },
                unsetBuyer(){
                    this.buyer = null;
                },
                initPhotoViewer(src = '', title = '') {
                    const items = [
                        { src, title },
                    ];
                    const options = { index: 0 };

                    new PhotoViewer(items, options);
                }
            },
            watch: {
                strSearchPhone: function(){
                    if(this.strSearchPhone != null && this.strSearchPhone.length >= 13){
                        this.processing_user = true;
                        axios.post('/api/v1/sallers/list', {
                                api_token: '{{$user->api_token}}',
                                phone__like: this.strSearchPhone,
                            },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                        ).then(response => {
                            if (response.data.status === 'success') {
                                if(response.data.response.total === 0){
                                    this.sallers = 404;
                                }else{
                                    this.sallers = response.data.data;
                                }
                                this.phone = this.strSearchPhone;
                            }
                            this.processing_user = false;
                        })
                    }
                }
            },
        })
    </script>

@endsection
