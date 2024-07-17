@extends('templates.panel.app')

@section('title', $saller->name.' ('.__('panel/partner.id').' '.$saller->id.')')
@section('class', 'partners edit')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.sallers.index')}}"><img
            src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('content')

    <form method="POST" enctype="multipart/form-data" action="{{localeRoute('panel.sallers.update', $saller)}}">
        @csrf
        @method('PATCH')

        <input type="hidden" name="files_to_delete" :value="files.delete">

        <div class="form-group">
            <input @change="updateFiles" ref="passport" accept=".png, .jpg, .jpeg, .gif" name="passport" type="file"
                   class="d-none" id="passport">

            <div v-if="preview" class="preview" @click="showPreviewPhoto(preview, 'passport')">
                <button v-on:click="resetFiles('passport')" class="btn btn-sm btn-danger">
                    <img src="{{asset('images/icons/icon_close.svg')}}">
                </button>
                <img :src="preview"/>
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
            <input @change="updateFiles" ref="passport_address" accept=".png, .jpg, .jpeg, .gif" name="passport_address"
                   type="file" class="d-none" id="passport_address">

            <div v-if="previewAddress" class="preview" @click="showPreviewPhoto(previewAddress, 'passport_address')">
                <button v-on:click="resetFiles('passport_address')" class="btn btn-sm btn-danger">
                    <img src="{{asset('images/icons/icon_close.svg')}}">
                </button>
                <img :src="previewAddress"/>
            </div>

            <div v-else class="no-preview">
                <label class="btn btn-primary"
                       for="passport_address">{{__('panel/buyer.passport_with_address')}}</label>
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
            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.company_brand')}}</label>

                <select class="form-control" name="company_id" id="company_brand">
                    @if($companies)
                        @foreach($companies as $company)
                            <option
                                @if($company->id == old('company_id', $saller->seller_company_id)) selected @endif
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
                <label>{{__('panel/partner.user_name')}}</label>
                <input value="{{old('name', @$saller->user->name)}}" required name="name" type="text"
                       class="@error('name') is-invalid @enderror form-control">
                @error('name')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.user_surname')}}</label>
                <input value="{{old('surname', @$saller->user->surname)}}" required name="surname" type="text"
                       class="@error('surname') is-invalid @enderror form-control">
                @error('surname')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/partner.user_patronymic')}}</label>
                <input value="{{old('patronymic', @$saller->user->patronymic)}}" name="patronymic" type="text"
                       class="@error('patronymic') is-invalid @enderror form-control">
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
                <input v-mask="'+############'" value="{{old('phone', @$saller->user->phone)}}" required name="phone"
                       type="text" class="@error('phone') is-invalid @enderror form-control">
            <!--                <input v-mask="'+998#########'" value="{{old('phone', @$saller->user->phone)}}" required name="phone" type="text" class="@error('phone') is-invalid @enderror form-control">-->
                @error('phone')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group col-12 col-md-4">
                <label>{{__('panel/buyer.pinfl')}}</label>
                <input value="{{old('pinfl', \App\Helpers\EncryptHelper::decryptData(@$saller->personals->pinfl))}}"
                       required name="pinfl" type="text" class="@error('pinfl') is-invalid @enderror form-control">
                @error('pinfl')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

        </div><!-- /.form-row -->

        <hr>

        <div class="lead">Пластиковая карта</div>
        <div>{{ 'Добавлена карта: ' . $card . ' ' . $exp }}</div>

        <br>
        <br>

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
                        id="passport_number"
                        type="text"
                        name="passport_number"
                        value="{{old('passport_number', \App\Helpers\EncryptHelper::decryptData(@$saller->personals->passport_number))}}"
                        :class="(errors.passport_number?'is-invalid':'') + ' form-control modified'"
                        required
                    >
                    <div class="error" v-for="item in errors.passport_number">@{{ item }}</div>
                </div>
            </div>
        </div>

        <div class="form-controls">
            <a class="btn btn-outline-secondary"
               href="{{localeRoute('panel.sallers.index')}}">
                {{__('app.btn_cancel')}}
            </a>

            <button type="submit" class="btn btn-outline-primary ml-lg-auto">{{__('app.btn_save')}}</button>
        </div>
    </form>

    <script>
    const sftpFileServerDomain =  @json(env('SFTP_FILE_SERVER_DOMAIN'));
    $(document).ready(function () {
        $('#company_brand').select2({
            theme: 'bootstrap4',
        });
    });

    var app = new Vue({
        el: '#app',
        data: {
            errors: [],
            error: false,
            filter: {
                date: '{{ Illuminate\Support\Carbon::parse($saller->date_pact)->format('d.m.Y') }}',
            },

            files: {
                passport: {
                    old: "{{$saller->passport->id??null}}",
                    new: null,
                    delete: '',
                },
                passport_address: {
                    old: "{{$saller->passportAddress->id??null}}",
                    new: null,
                    delete: '',
                },
            },

            preview: '{{$saller->latestPassport->globalPreview??null}}',
            previewAddress: '{{$saller->latestPassportAddress->globalPreview??null}}',
            files_to_delete: [],

        },
        methods: {
            resetFiles(name) {

                this.files[name].new = null;
                this.files[name].delete = null;
                this.files[name].value = null;
                if (name == 'passport') this.preview = null;
                if (name == 'passport_address') this.previewAddress = null;
            },
            updateFiles(e) {
                e.preventDefault();
                let input = e.target.name,
                    files = e.target.files;
                if (files.length > 0) {
                    this.files[input].new = files[0];
                    const previewUrl = URL.createObjectURL(files[0]);
                    this.files[input].preview = previewUrl;
                    this.initPhotoViewer(previewUrl, input)
                }
                if (this.files[input].old) {
                    this.files_to_delete.push(this.files[input].old);
                }
            },
            showPreviewPhoto(src, title) {
                this.initPhotoViewer(src, title)
            },
            initPhotoViewer(src = '', title = '') {
                const items = [
                    { src, title },
                ];
                const options = { index: 0 };

                new PhotoViewer(items, options);
            },
        },
    });
    </script>

@endsection
