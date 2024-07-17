@php
    $buyerPersonals = $buyer->personals ?? new \App\Models\BuyerPersonal();
@endphp

<style>
    .u-checkbox {

    }
    .u-checkbox_label{
        cursor: pointer;
        display: inline-flex;
        align-items: flex-start;
        gap: 8px;
    }

    .u-checkbox_check{
        width: 24px;
        height: 24px;
        border: 1px solid #e1e1e1;
        display: inline-flex;
        align-items: center;
        background: white;
        justify-content: center;
        border-radius: 8px;
        transition: all .25s ease-out;
    }
    .u-checkbox_check svg{
        color: #ffffff;
        opacity: 0;
        transition: all .25s ease-out;
    }
    .u-checkbox_input{
        display: none;
    }
    .u-checkbox_input:checked + label .u-checkbox_check{
        background: #7000FF;
        border-color: #7000FF;
    }
    .u-checkbox_input:checked + label .u-checkbox_check svg{
        opacity: 1;
    }
    button:focus{
        outline: none;
        box-shadow: none;
    }
    .alert{
        border-radius: 10px;
        padding: 16px;
        color: #ffffff;
    }
    .alert .close svg{
        width: 12px;
        height: 12px;
    }
    .alert .close:hover {
        color: #ffffff;
    }
    .alert.alert-danger {
        background-color: #F84343;
        border-color: #F84343;
    }
    .alert.alert-success {
        background-color: #53DB8C;
        border-color: #53DB8C;

    }
    .alert.alert-warning {
        background-color: #FFA41D;
        border-color: #FFA41D;

    }
    .text-green {
        color: #00AB4A;
    }
    .validation-container {
        border-radius: 16px;
        list-style-type: none;
    }
    .custom-icon-radio input {
        display: none;
    }
    .custom-icon-radio input:checked+label {
        border-color: var(--orange);
        color: var(--orange);
        background: var(--peach);
    }
    .custom-icon-radio input:checked+label:before {
        background: var(--peach);
        border-color: var(--orange);
        border-width: 4px;
    }
    button:disabled,
    .u-checkbox_input:disabled + label,
    .custom-icon-radio input:disabled + label,
    .form-control.modified:disabled{
        cursor: not-allowed !important;
        opacity: .4;
        filter: grayscale(1);
    }
    .custom-icon-radio label {
        cursor: pointer;
        width: 100%;
        transition: all .25s ease-out;
        background: #ffffff;
        border-radius: 12px;
        padding: 16px 20px;
        border: 1px solid #ebebeb;
        font-style: normal;
        font-weight: 500;
        font-size: 16px;
        line-height: 20px;
        display: inline-flex;
        align-items: center;
        gap: 16px
    }
    .custom-icon-radio label:before {
        content: '';
        transition: all .25s ease-out;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: block;
        background: #ededed;
        border: 1px solid transparent;
    }
    .custom-icon-radio label:hover {
        background: #fbf9f9;
        padding: 16px 20px;
    }
</style>
<div class="row">
    <div class="col-12 col-lg-6" id="scoring">
        <div class="choose-card">
            <div class="title">
                {{__('panel/buyer.cards')}}
            </div>
            <div class="text-right">
                <button data-toggle="modal" data-target="#modalCard" class="btn btn-light">
                    {{__('card.btn_add')}}
                </button>
            </div>
            <div class="form-group">
                <label for="chooseCard">{{__('panel/buyer.choose_card')}}</label>
                <select v-model="card" name="card" class="form-control" id="chooseCard" @change="selectCard()">
                    {{--                <select v-model="card" name="card" class="form-control modified dense rounded" id="chooseCard" @change="selectCard()">--}}
                    @if(count($buyer->cards) > 0)
                        {{--                        <option value="" selected>{{__('panel/buyer.choose_card')}}</option>--}}
                        @foreach($buyer->cards as $card)
                            <option value="{{$card->id}}">{{$card->public_number}} ({{$card->type}})</option>
                        @endforeach
                    @else
                        <option selected disabled>{{__('panel/buyer.no_cards')}}</option>
                    @endif
                </select>
            </div>
            <div class="cards-label">
                <img src="{{asset('/images/media/cards_label_grey.svg')}}" alt="">
            </div>
            <template v-if="card !== ''">
                <hr>
                <div class="card-name">
                    <img src="{{asset('/images/icons/icon_user_grey_circle.svg')}}" alt="">
                    <span>
                        @{{ card_name }}
                        <br>
                        @{{ card_username }}
                    </span>
                </div>
                <p>{{ __('panel/employee.sms_info') }} @{{ card_has_sms_info }}</p>
{{--                <div class="balance">--}}
{{--                    <div class="left">--}}
{{--                        <div class="label">--}}
{{--                            {{__('panel/buyer.balance')}}--}}
{{--                        </div>--}}
{{--                        <div class="value" v-if="balance !== null">--}}
{{--                            @{{ numberFormat(balance) }}--}}
{{--                        </div>--}}
{{--                        <div class="value" v-else>--}}
{{--                            ------}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <button class="btn btn-outline-light" :disabled="loading" @click="getBalance">@{{ btnLoading }}--}}
{{--                    </button>--}}
{{--                </div>--}}
            </template>

        </div>
{{--        <div class="scoring-card mt-4" v-if="card !== ''">--}}
{{--            <div class="title">{{__('panel/buyer.scoring')}}</div>--}}
{{--            <div class="row">--}}
{{--                <div class="col">--}}
{{--                    <div class="form-group">--}}
{{--                        <label>{{__('panel/buyer.scoring_date_start')}}</label>--}}
{{--                        <date-picker value-type="format" v-model="date_start" type="date" format="DD.MM.YYYY"--}}
{{--                                     name="date_start" required--}}
{{--                                     class="@error('date_start') is-invalid @enderror"></date-picker>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="col">--}}
{{--                    <div class="form-group">--}}
{{--                        <label>{{__('panel/buyer.scoring_date_end')}}</label>--}}
{{--                        <date-picker value-type="format" v-model="date_end" type="date" format="DD.MM.YYYY"--}}
{{--                                     name="date_end" required--}}
{{--                                     class="@error('date_end') is-invalid @enderror"></date-picker>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="col">--}}
{{--                    <div class="form-group">--}}
{{--                        <label>{{__('panel/buyer.scoring_sum')}}</label>--}}
{{--                        <input v-model="sum" name="sum" type="text"--}}
{{--                               class="@error('sum') is-invalid @enderror form-control">--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <p v-if="this.valdiationDateError" class="text-red" v-html="valdiationDateErrorSMS"></p>--}}

{{--            <div class="form-group text-right" style="clear:both;">--}}
{{--                <div style="float:left;width:50%;padding-top: 10px;">--}}
{{--                    <label>--}}
{{--                        <input type="checkbox" v-model="scoring_from_server">--}}
{{--                        {{ __('panel/buyer.scoring_from_server') }}--}}
{{--                    </label>--}}
{{--                </div>--}}
{{--                <div style="float:right;width:50%;">--}}
{{--                    <button--}}
{{--                        v-on:click.once="checkScoring"--}}
{{--                        :disabled="disableCardCheckButton"--}}
{{--                        class="btn btn-orange"--}}
{{--                        :style="{ cursor: disableCardCheckButton ? 'not-allowed' : 'pointer' }"--}}
{{--                    >--}}
{{--                        {{__('app.btn_check')}}--}}
{{--                    </button>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="dummy" style="padding-top:25px;"></div>--}}
{{--            <hr>--}}


{{--            <h5 class="text-red" v-for="error in this.errors">@{{ error }}</h5>--}}

{{--            <div class="scoring-results" v-if="showResults && errors.length == 0">--}}
{{--                <div class="title">{{__('panel/buyer.scoring_card_result')}}</div>--}}
{{--                <table class="table">--}}
{{--                    <thead>--}}
{{--                    <tr>--}}
{{--                        <th>{{__('panel/buyer.scoring_month_year')}}</th>--}}
{{--                        <th>{{__('panel/buyer.scoring_result')}}</th>--}}
{{--                    </tr>--}}
{{--                    </thead>--}}
{{--                    <tbody>--}}
{{--                    <template v-for="(scoring, index) in scorings" :key="index">--}}

{{--                        <tr>--}}
{{--                            <td class="">@{{ index }}</td>--}}
{{--                            <td class="yes">@{{ numberFormat(scoring/100) }}</td>--}}
{{--                        </tr>--}}
{{--                    </template>--}}
{{--                    </tbody>--}}
{{--                </table>--}}
{{--            </div>--}}
{{--        </div>--}}
        @if(count($buyer->guarants) > 0)
            <div class="col-12 mt-3">
                <h3 class="text-center mb-3">{{__('panel/buyer.trustee')}}</h3>
                @foreach($buyer->guarants as $guarant)
                    <p class="guarant-style">ФИО Доверителя {{$guarant->name}}</p>
                    <p class="guarant-style">Телефон Доверителя {{$guarant->phone}}</p><br>
                @endforeach
            </div>
        @endif
        <div class="modal fade" id="modalCard" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{{__('card.btn_add')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{__('card.card_number')}}</label>
                            <input
                                @blur="this.blur = true"
                                type="text"
                                v-model="card_number"
                                :class="(errors.card_number?'is-invalid':'') + ' form-control modified'"
                                v-mask="'#### #### #### ####'"
                                placeholder="8600 0000 0000 0000"
                            >
                            <span id="errorCardValidation"></span>
                            <p class="error">@{{ validationCardNumberError }}</p>
                        </div>
                        <div class="form-group">
                            <label>{{__('card.card_exp')}}</label>
                            <input
                                @blur="this.blur = true"
                                type="text"
                                v-model="card_valid_date"
                                :class="(errors.card_valid_date?'is-invalid':'') + ' form-control modified'"
                                v-mask="'##/##'"
                                placeholder="00/00"
                            >
                            <p class="error">@{{ validationCardExpDateError }}</p>
                        </div>

                        <div v-if="hasConfirmCodeInput" class="form-group">
                            <label>{{__('card.sms_code')}}</label>
                            <input class="form-control modified" type="text" v-model="sms_сode" id="confirm-code">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                        <button
                            v-if="!hasConfirmCodeInput"
                            @click="save"
                            type="button"
                            class="btn btn-primary"
                            :disabled="loading ||  validationCardNumberError"
                        >
                            {{__('app.btn_send_sms')}}
                        </button>
                        <button
                            v-else
                            @click="confirm"
                            type="button"
                            class="btn btn-primary"
                        >
                            {{__('app.btn_check')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="col-12 col-lg-6" id="scoring_katm">
        <div class="scoring_katm">
            <div class="row">
                <div class="col-6 col-lg-6">

                    <div class="title">
                        {{__('panel/buyer.scoring_buyer')}}
                    </div>

                </div>
                <div class="col-6 col-lg-6">
                    @if($partner = \App\Models\Partner::where('id', $buyer->created_by)->with('company')->first())
                        <div>
                            {{__('panel/buyer.created_by')}}
                            <a class="detail-link" target="_blank"
                               href="../partners/{{@$partner->company->id }}">{{@$partner->company->brand }}</a>
                        </div>
                    @endif
                </div>
            </div>

            <ul id="buyer_personal_files_container">
                @if(count($buyerPersonals->files) > 0)
                    @foreach($buyerPersonals->files->reverse() as $file)
                        <li class="buyer-personal-files">
                            <a
                                href="{{ \App\Helpers\FileHelper::url($file->path) }}"
                                data-imagesrc="{{ $file->path }}"
                                data-docpath="{{ $file->doc_path }}"
                                data-imagelabel="{{__('panel/buyer.'.$file->type)}}"
                            >
                                {{__('panel/buyer.'.$file->type)}}
                            </a>
                        </li>
                    @endforeach
                    {{--                <button id="showImage">img show</button>--}}
                @endif
                    {{--     Myid form link       --}}
                    <a v-if="myId" class="mt-1 d-block" :href="`/uz/panel/contracts/myid/form-1/${myId?.my_id}`" target="_blank">Анкета MyId(Форма №1)</a>
            </ul>




            {{--<div class="form-group">
                <select class="form-control" v-model="region_id" name="region_id" v-on:change="changeRegion()">
                    <option v-for="item in regions" :value="item.id">@{{item.name}}</option>
                </select>
                <select class="form-control mt-2" v-model="local_region_id" name="local_region_id">
                    <option v-for="item in local_regions" :value="item.id">@{{item.name}}</option>
                </select>
            </div>--}}
            {{--            <form @submit.prevent="check">--}}

            <div class="form-group row align-items-end">
                <div class="col">
                    <label>{{__('panel/buyer.upload_image')}}</label>
                    <input type="file" ref="fileInput" @input="previewFiles" :accept="mime_types.join(',')"
                           style="display:none" name="" id="">
                    <multiselect
                        class="modified single"
                        :disabled="selected_file"
                        v-model="selected_file_type"
                        label="name"
                        track-by="value"
                        placeholder="Выберите тип файла"
                        :multiple="false"
                        :allow-empty="true"
                        @input="onFileTypeSelected"

                        :loading="buyer_personals_types_loading"
                        :options="buyer_personals_types"
                        deselect-label="Отменить выбор"
                        selected-label="Выбрано"
                        select-label="{{__('panel/buyer.select')}}"
                    >
                        <template slot="clear" slot-scope="props">
                            <div class="multiselect__clear" v-if="selected_file_type"></div>
                        </template>
                        <span slot="noResult">По вашему запросу нет данных</span>
                        <span slot="noOptions">Нет данных для выбора</span>
                    </multiselect>
                </div>
            </div>

            <div v-if="selected_file" class="form-group fileobj__container bg-white "
                 :class="{'fileobj__uploading': image_uploader_loading}">
                <div class="media">
                    <div @click="viewImage(selected_file.url, selected_file_type.name)"
                         class="fileobj__image-container cursor-pointer bg-light d-inline-flex overflow-hidden align-items-center justify-content-center"
                         id="selected_image_container">
                        <img class="img-fluid " :src="selected_file.url" :alt="selected_file_type.name">
                    </div>
                    <div class="media-body position-relative">
                        <h5 class="mt-0 mb-1">@{{selected_file_type.name}} </h5>
                        <div class="description">
                            <p class="m-0 filename">@{{selected_file.file.name}}</p>
                            <p class="m-0 filesize text-muted">@{{formatBytes(selected_file.file.size)}} </p>
                        </div>

                        <div class="fileobj__actions">
                            <span @click="onFileTypeSelected" class="btn btn-icon  edit" v-html="icons.pencil"></span>
                            <span @click="resetFilePicker" class="btn btn-icon  delete" v-html="icons.trash"></span>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="form-group text-right">
                <button :disabled="image_uploader_loading" v-if="selected_file" @click="postBuyerPersonalImage"
                        type="button" class=" btn my-2 btn-orange form-btn px-4">
                    <span aria-hidden="true">{{__('panel/buyer.upload')}}</span>
                </button>
            </div>

            {{-- форма отправки на Скоринг --}}
            <form @submit.prevent="initScoringProcess">


                <div class="form-group ">
                    <label>Тип документа</label>
                    <div class="row">
                        <div class="col">
                            <div class="custom-icon-radio">
                                <input :disabled="!isModerated" required type="radio" v-model="doc_type" value="0" id="doctype_id_card" name="passport_type">
                                <label for="doctype_id_card">ID Карта</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="custom-icon-radio">
                                <input :disabled="!isModerated" required type="radio" v-model="doc_type" id="doctype_passport" value="6" name="passport_type">
                                <label for="doctype_passport">Паспорт</label>
                            </div>
                        </div>
                    </div>
                    <div class="invalid-feedback d-block" v-if="!doc_type">
                        Выберите тип документа
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{__('panel/buyer.passport_number')}}</label>
                            <input
                                class="form-control modified"
                                autocomplete="off"
                                v-model="formatedPassport"
                                v-mask="`AA #######`"
                                placeholder="AA 0000000"
                                type="text"
                                required
                                :disabled="!isModerated"
                                name="passport_number"
                                value="{{$buyerPersonals->passport_number}}"
                                @keyup.enter.once="check"
                            >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Дата рождения</label>
                            <input
                                class="form-control modified"
                                autocomplete="off"
                                required
                                :disabled="!isModerated"
                                v-model="birth_date"
                                type="date"
                                @change="(e)=>onDateChange(e, birthDateMin, birthDateMax)"
                                :min="birthDateMin"
                                :max="birthDateMax"
                                name="birth_date"
                            >
                        </div>
                    </div>
                </div>

                <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Дата выдачи паспорта</label>
                                <input
                                    class="form-control modified"
                                    autocomplete="off"
                                    v-model="issue_date"
                                    required
                                    :disabled="!isModerated"
                                    type="date"
                                    :min="birthDateMin"
                                    :max="passportExpireMin"
                                    name="passport_date_issue"
                                    @change="(e)=>onDateChange(e, birthDateMin, passportExpireMin)"
                                    value="{{$buyerPersonals->passport_issue}}"
                                    @keyup.enter.once="check"
                                >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Дата окончания срока паспорта</label>
                                <input
                                    class="form-control modified"
                                    autocomplete="off"
                                    required
                                    :disabled="!isModerated"
                                    v-model="exp_date"
                                    type="date"
                                    name="passport_expire_date"
                                    :min="passportExpireMin"
                                    :max="passportExpireMax"
                                    @change="(e)=>onDateChange(e, passportExpireMin, passportExpireMax)"
                                    value="{{$buyerPersonals->passport_issue}}"
                                    @keyup.enter.once="check"
                                >
                            </div>
                        </div>
                </div>

                <div class="form-group">
                    <label>{{__('panel/buyer.pinfl')}}</label>
                    <input
                        class="form-control modified"
                        autocomplete="off"
                        required
                        :disabled="!isModerated"
                        v-model="formatedPinfl"
                        v-mask="`## ## ## ## ## ## ##`"
                        placeholder="00 00 00 00 00 00 00"
                        type="text"
                        name="pinfl"
                        value="{{$buyerPersonals->pinfl}}"
                        @keyup.enter.once="check"
                    >
                    <div class="invalid-feedback d-block" v-if="formatedPinfl.length != 14">
                        Поле должно состоять из 14 цифр
                    </div>
                </div>

                {{-- <div class="form-group">
                    <label>{{__('panel/buyer.katm_method')}}</label>

                    <select id="katm_method" name="katm_method" v-model="katm_method" class="form-control modified select-period">
                        <option value="auto">Auto</option>
                        <option value="manual">Manual</option>
                    </select>
                </div> --}}
                <div class="row ">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{__('panel/buyer.regions')}}</label>
                            <select required :disabled="!isModerated" class="form-control modified" v-model="selectedRegion">
                                <option disabled value="">Выберите регион</option>
                                <option v-for="region in regions" :value="region.id">@{{region.name}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{__('panel/buyer.districts')}}</label>
                            <select required :disabled="!isModerated" class="form-control modified" v-model="selectedArea">
                                <option disabled value="">Выберите район</option>
                                <option v-for="area in regions[selectedRegion].local_region" :value="area.id">@{{area.name}}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>



                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{__('panel/buyer.first_name')}}</label>
                            <input
                                class="form-control modified"
                                autocomplete="off"
                                v-model="first_name"
                                required
                                @keyup="trimOnKeyup"
                                :disabled="!isModerated"
                                type="text"
                                name="first_name"
                                value="{{$buyer->name}}"
                            >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{__('panel/buyer.last_name')}}</label>
                            <input
                                class="form-control modified"
                                autocomplete="off"
                                required
                                :disabled="!isModerated"
                                v-model="last_name"
                                @keyup="trimOnKeyup"
                                type="text"
                                name="last_name"
                                value="{{$buyer->surname}}"
                            >
                        </div>
                    </div>
                </div>



                <div class="form-group">
                    <label>{{__('panel/buyer.patronymic')}}</label>
                    <input
                        class="form-control modified"
                        autocomplete="off"
                        :disabled="!isModerated"
                        @keyup="trimOnKeyup"
                        v-model="patronymic"
                        type="text"
                        name="patronymic"
                        value="{{$buyer->patronymic}}"
                    >
                </div>

                {{-- <div class="form-group">
                    <label>{{__('panel/buyer.mrz')}}</label>
                    <input
                        class="form-control modified"
                        autocomplete="off"
                        v-model="mrz"
                        type="text"
                        name="mrz"
                        value="{{$buyerPersonals->mrz}}"
                    >
                </div> --}}

                <div class="form-group">
                    <label>Пол</label>
                    <div class="row">
                        <div class="col">
                            <div class="custom-icon-radio">
                                <input type="radio" required :disabled="!isModerated" v-model="gender" id="gender_1" value="1" name="gender">
                                <label for="gender_1">Мужской</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="custom-icon-radio">
                                <input type="radio" required :disabled="!isModerated" v-model="gender" id="gender_2" value="2" name="gender">
                                <label for="gender_2">Женский</label>
                            </div>
                        </div>
                    </div>
                    <div class="invalid-feedback d-block" v-if="![1,2].includes(Number(gender))">
                        Выберите пол
                    </div>
                </div>
                {{-- <div class="form-group">
                    <ul class="validation-container">
                        <li class="" :class="[scoringValidation.doc_type ? 'text-green':'text-red']" >
                            <span v-if="scoringValidation.doc_type">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M15.3873 6.29296C15.7778 6.68352 15.7778 7.31668 15.3872 7.70717L9.41958 13.6737C9.0291 14.0641 8.39609 14.0641 8.00557 13.6738L5.29303 10.9623C4.90243 10.5719 4.90231 9.93869 5.29275 9.54809C5.6832 9.15749 6.31637 9.15737 6.70697 9.54781L8.71246 11.5525L13.9731 6.29283C14.3637 5.90234 14.9968 5.9024 15.3873 6.29296Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <span v-else>
                                X
                            </span>
                            Тип документа (@{{doc_type}})
                        </li>
                    </ul>
                </div> --}}

                <div class="form-group">

                    <div class="text-right">
                        <button
                            v-if="kyc_status != 0 && buyer_status != 4 && buyer_status != 8"
                            :disabled="disableScoringButton || selectedArea == '' || ![1,2].includes(Number(gender)) || !doc_type || formatedPinfl.length != 14 "

                            class="btn btn-primary"
                            type="submit"
                            :style="{ cursor: disableScoringButton ? 'not-allowed' : 'pointer' }"
                        >
                            СКОРИНГ <span v-if="freezeTime > 0">(@{{freezeTime}})</span>
                        </button>

                        <button
                            @click.once="workButton"
                            class="btn btn-warning"
                            type="button"
                        >
                            CHECK
                        </button>

                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 d-flex align-items-center">
                        <div class="u-checkbox">
                            <input class="u-checkbox_input" :disabled="!isModerated" id="send_sms_check" type="checkbox" v-model="send_sms">

                            <label class="u-checkbox_label" for="send_sms_check">
                                <span class="u-checkbox_check">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="10" viewBox="0 0 12 10" fill="none">
                                        <path d="M1 5.66667L4.33333 9L11 1" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                {{ __('Отправить СМС уведомление покупателю') }}
                            </label>
                        </div>

                    </div>

                    <div v-for="item in scoringMessagesList" :key="item.id"
                        class="col-md-12 d-flex align-items-center pt-2 pm-2 border-bottom">
                        <span class="w-100"> @{{ item.name }} </span>
                        <div class="badge mr-2 ml-2 text-right" :class="drawBadge(item.state)">
                            @{{ item.text }}
                        </div>
                    </div>

                    <div class="col-md-12 mt-4">
                        <div class="alert alert-danger alert-dismissible fade show" v-for="error in errors">
                            @{{ error }}
                            <button type="button" class="close h-100 d-inline-flex align-items-center justify-content-center" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                                        <path d="M1.00049 0.96875L16.9995 16.9647M1.00049 16.9647L16.9995 0.96875" stroke="currentColor" stroke-width="1.92919" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </button>
                        </div>

                        <div v-for="message in messages" :class="'alert alert-' + message.type + ' alert-dismissible fade show'">
                            @{{ message.text }}
                            <button type="button" class="close h-100 d-inline-flex align-items-center justify-content-center" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                                        <path d="M1.00049 0.96875L16.9995 16.9647M1.00049 16.9647L16.9995 0.96875" stroke="currentColor" stroke-width="1.92919" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                </div>
            </form>



        </div>
        <div id="katm-sroring__error"></div>
        @php
            $scoringResult = \App\Models\ScoringResult::withoutGlobalScopes()
            ->where('user_id', $buyer->id)
            ->orderBy('created_at', 'DESC')
            ->first();
        @endphp
        @if ($scoringResult
            || $buyer->katmDefault->count() > 0
            || $buyer->katmInfoscoreReports()->count() > 0
            || $myid_report)
            <div class="scoring-katm-results mt-4">
                <table class="table">
                    <tr>
                        <th>
                            {{ __('Тип') }}
                        </th>
                        <th>
                            {{ __('Дата формирования') }}
                        </th>
                        <th>
                            {{ __('Отчёт') }}
                        </th>
                    </tr>
                    @if($scoringResult !== null)
                    <tr>
                        <td>
                            {{ __('Скоринг') }}
                        </td>
                        <td>
                            {{ date('d.m.Y H:i', strtotime($scoringResult->created_at)) }}
                        </td>
                        <td>
                            <a href="{{ localeRoute('panel.buyers.scoring.report', ['id' => $buyer->id, 'reportID' => $scoringResult->id]) }}"
                               target="_blank">
                                {{ __('Посмотреть') }}
                            </a>
                        </td>
                    </tr>
                    @endif
                    @foreach($buyer->katmDefault()->orderBy('created_at', 'DESC')->get() as $katm)
                        <tr>
                            <td>
                                {{ __('КАТМ') }}
                            </td>
                            <td>
                                {{ date('d.m.Y H:i', strtotime($katm->created_at)) }}
                            </td>
                            <td>
                                <a href="{{ localeRoute('panel.buyers.report', ['id' => $buyer->id, 'reportID' => $katm->id]) }}"
                                   target="_blank">
                                    {{--                                {{__('panel/buyer.soring_report_pdf')}}--}}
                                    {{ __('Посмотреть') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    @foreach($buyer->katmInfoscoreReports() as $report)
                        <tr>
                            <td>
                                {{ __('КАТМ INFOSCORE') }}
                            </td>
                            <td>
                                {{ date('d.m.Y H:i', strtotime($report->created_at)) }}
                            </td>
                            <td>
                                <a href="{{ \App\Helpers\FileHelper::url($report->path) }}"
                                   target="_blank">
                                    {{ __('Посмотреть') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                        <tr v-if="myIdReport">
                            <td>
                                MyID
                            </td>
                            <td>
                                @{{moment(myIdReport.created_at).format('DD.MM.YYYY HH:mm')}}
                            </td>
                            <td>
                                <a href="{{ localeRoute('panel.buyers.scoring.myid.report', ['id' => $buyer->id]) }}"
                                target="_blank">
                                    {{ __('Посмотреть') }}
                                </a>
                            </td>
                        </tr>
                        <tr v-if="myIdReportLoading">
                            <td> <span class="d-block rounded bg-light p-2"></span></td>
                            <td> <span class="d-block rounded bg-light p-2"></span></td>
                            <td> <span class="d-block rounded bg-light p-2"></span></td>
                        </tr>
                </table>
            </div>
        @endif
        <div class="mt-4">
            <br>
            <table class="table w-100">
                <tr>
                    <td style="text-align: left;">{{__('panel/buyer.status_myId')}}</td>
                    @if($myId!=null)
                        @if($myId->result_code==1)
                            <td style="text-align: left;">{{__('panel/buyer.registration_myId_yes')}}</td>
                        @else
                            <td style="text-align: left;">{{ $myId->result_note }}</td>
                        @endif
                    @else
                        <td style="text-align: left;">{{__('panel/buyer.myId_not_exist')}}</td>
                    @endif
                </tr>
            </table>
        </div>
    </div>
</div>

<!--<script src="{{ asset('/js/jquery.min.js') }}"></script>-->

<script>
    $(document).ready(function () {
        initAllViewers()
    })

    function generate_buyer_personal_files(data) {
        let DomList = ``
        data.reverse().forEach((el, idx, arr) => {
            let last_added_class = ''
            let last_added = ''
            let last_added_style = ''
            if (idx === 0) last_added_class = 'new'
            DomList += `
        <li class="buyer-personal-files ${last_added_class}">
            <a
                href="${el.href}"
                data-imagesrc="${el.imagesrc}"
                data-docpath="${el.doc_path}"
                data-imagelabel="${el.imagelabel}"
            >
                ${el.imagelabel}
            </a>
        </li>
        `
        })
        $('#buyer_personal_files_container').html(DomList)
        initAllViewers()
    }


    var scoring = new Vue({
        el: '#scoring',
        data: {
            loading: false,

            locale: '{{ucfirst(app()->getLocale())}}',
            errors: [],
            messages: [],
            showResults: false,
            api_token: '{{Auth::user()->api_token}}',
            buyer_id: '{{$buyer->id}}',
            gender: '{{$buyer->gender>=1?$buyer->gender:0}}',
            date_start: '{{$buyer->scoring['date_start']}}',
            date_end: '{{$buyer->scoring['date_end']}}',
            sum: '{{$buyer->scoring['sum']}}',
            card: '',
            exp: '',
            card_number: '',
            card_valid_date: '',
            card_token: '',
            sms_сode: '',
            hasConfirmCodeInput: false,

            card_name: @json(@$buyer->cards[0]->public_card_name ?? null),
            card_has_sms_info: @json(@$buyer->cards[0]->sms_info == 0 ? 'ON' : 'OFF' ),
            card_username: '',
            scorings: null,
            balance: null,
            katm_status: -1, // { { $katm_status }}  // статус от КАТМ
            royxat_status: -1,
            gnk_status: -1,
            pinfl_status: -1,
            scoring_status: -1,
            mib_status: -1,

            kyc_status: {{$buyer->kyc_status}},
            scoring_from_server: 0,
            disableCardCheckButton: false,
            katm_method: 'auto',
            doc_type: '{{$buyerPersonals->passport_type}}',

            first_name: '{{$buyer->name}}',
            last_name: '{{$buyer->surname}}',
            patronymic: '{{$buyer->patronymic}}',
            birth_date: '{{$buyerPersonals->birthday_open}}',
            issue_date: '{{$buyerPersonals->passport_date_issue_open}}',
            exp_date: '{{$buyerPersonals->passport_expire_date_open}}',
            // mrz: '',
            valdiationDateErrorSMS: '',
        },
        created() {
            this.card = @json($buyer->cards[0]->id ?? null);
            this.getBalance();
        },
        computed: {

            btnLoading() {
                return this.loading ? 'Загрузка...' : '{{__('panel/buyer.btn_request')}}'
            },
            valdiationDateError() {
                const currentDate = moment();
                const dateStart = moment(this.date_start, 'DD.MM.YYYY')
                const dateEnd = moment(this.date_end, 'DD.MM.YYYY');
                if (moment(dateStart).isAfter(dateEnd)) {
                    return this.valdiationDateErrorSMS = '{{__('panel/buyer.not_valid_date_start')}}';
                }
                if (moment(currentDate).isBefore(dateEnd)) {
                    return this.valdiationDateErrorSMS = '{{__('panel/buyer.not_valid_date_end')}}';
                }
                return false;
            },
            validationCardNumberError() {
                const firstBlog = this.card_number?.slice(0, 4);
                const lastTwoNumbers = this.card_number?.split(' ')[1]?.slice(2,4);
                if (firstBlog === '8600') {
                    if (lastTwoNumbers === '32' || lastTwoNumbers === '08') {
                        return '{{ __('billing/buyer.c_card_error') }}';
                    }
                }
            },
            validationCardExpDateError() {
                const cardDate = this.card_valid_date.split('/');
                const first = cardDate[0];
                const second = cardDate[1];
                const currentYear = new Date().getFullYear().toString().slice(2, 4);
                if (Number(first) > 12) {
                    return '{{ __('billing/buyer.invalid_card_date') }}';
                }
                if (Number(second) < Number(currentYear)) {
                    return '{{ __('billing/buyer.invalid_card_date') }}';
                }
            }
        },
        methods: {
            save() {
                this.message = [];
                this.loading = true;
                axios.post('/api/v3/cards/add',
                    {
                        pan: this.card_number,
                        expiry: this.card_valid_date,
                        buyer_id: {{$buyer->id}},
                    },
                    {
                        headers: {
                            'Content-Language': '{{app()->getLocale()}}',
                            Authorization: `Bearer ${this.api_token}`
                        }
                    }
                ).then(response => {
                    if (response.data.status === 'success') {
                        this.card_token = response.data.card_token;
                        // тут надо ввести смс код
                        this.hasConfirmCodeInput = true;
                        this.loading = false;
                    }
                }).catch(err => {
                    err.response?.data?.error?.forEach((error) => alert(error.text))
                })
            },
            confirm() {
                axios.post('/api/v3/cards/confirm',
                    {
                        pan: this.card_number,
                        expiry: this.card_valid_date,
                        code: this.sms_сode,
                        buyer_id: {{$buyer->id}},
                    },
                    {
                        headers: {
                            'Content-Language': '{{app()->getLocale()}}',
                            Authorization: `Bearer ${this.api_token}`
                        }
                    }
                ).then(response => {
                    if (response.data.status === 'success') {
                        $('#modalCard').modal('hide');
                        alert(response.data.response.message[0].text);
                        window.location.reload();
                    }
                }).catch(err => {
                    err.response?.data?.error?.forEach((error) => alert(error.text))
                })
            },

            selectCard: function () {
                let cards = @json($buyer->cards->toArray());
                for (const k in cards) {
                    let item = cards[k];
                    if (item.id == this.card) {
                        this.card_name = item.public_card_name;
                    }
                }
            },
            getBalance: function () {
                let post = {
                    api_token: this.api_token,
                    buyer_id: this.buyer_id,
                    card_id: this.card,
                };
                this.loading = true;
                axios.post('/api/v1/employee/buyers/balance', post,
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                    .then(response => {
                        setTimeout(() => {
                            this.loading = false;
                        }, 5000)
                        if (response && response.data && response.data.result) {
                            if (response.data.result.balance > 100) {
                                this.balance = response.data.result.balance / 100;
                            } else {
                                this.balance = 0;
                            }
                            this.card_name = response.data.result.owner;
                            this.card_username = response.data.result.phone;
                            scoring.$forceUpdate();
                        }
                    }).catch(e => {
                    setTimeout(() => {
                        this.loading = false;
                    }, 5000)
                    console.log(e);
                });
            },
            checkScoring: function () {
                this.disableCardCheckButton = true;
                let post = {
                    api_token: this.api_token,
                    buyer_id: this.buyer_id,
                    date_start: this.date_start,
                    date_end: this.date_end,
                    sum: this.sum,
                    card_id: this.card,
                    scoring_from_server: this.scoring_from_server,
                };
                axios.post('/api/v1/employee/buyers/scoring-universal', post,
                    {
                        headers: {
                            'Content-Language': '{{app()->getLocale()}}',
                            'Accept': 'application/json',
                        },
                    }).then(response => {

                    if (response.data.status === 'error') {
                        response.data.errors.forEach(item => this.errors.push(item));
                        this.valdiationDateErrorSMS = '';
                    }

                    this.scorings = response.data.result
                    scoring.$forceUpdate();

                }).catch(e => {
                    console.log('message', e.response.message);
                });
                this.showResults = true;
            },

            numberFormat: function (num) {
                return Intl.NumberFormat().format(num);
            },
        },
    });

    let prepare_katm_data = function () {
        let regions = JSON.parse('{!! $buyer->katm_regions !!}'),
            @if(isset($buyer->katmDefault))
            region_id = '{{$buyer->settings->katm_region_id??0}}',
            local_region_id = '{{$buyer->settings->katm_local_region_id??0}}';
        @else
            region_id = 0,
            local_region_id = 0;
        @endif
        if (regions[region_id] == undefined) {
            for (var reg in regions) {
                region_id = reg;
                for (var loc in regions[reg].local_region) {
                    local_region_id = regions[reg].local_region[loc].id;
                    break;
                }
                break;
            }
        }

        //console.dir(regions[region_id].local_region[local_region_id]);
        return {
            request_status: false,
            response_text: '{{__('Ожидание ответа от сервера...')}}',
            errors: [],
            messages: [],
            showResults: false,
            api_token: '{{Auth::user()->api_token}}',
            buyer_id: '{{$buyer->id}}',
            gender: '{{$buyer->gender>=1?$buyer->gender:0}}',
            regions: regions,
            region_id: region_id,
            local_region_id: local_region_id,
            local_regions: regions[region_id] != undefined ? regions[region_id].local_region : 0,
            status: {{$buyer->katm[0]->status??-1}},
            claim_id: '{{$buyer->katm[0]->claim_id??null}}',
            token: '{{$buyer->katm[0]->token??null}}',
            timerId: null,
            pinfl: '{{$buyerPersonals->pinfl}}',
            scoring: '{{$buyerPersonals->scoring}}',
            passport: '{{$buyerPersonals->passport_number}}'.replace(' ', ''),
            dotted: '',
            katms: {!! json_encode($buyer->katm) !!},
            send_sms: 1,
            katm_status: -1,
            royxat_status: -1,
            gnk_status: -1,
            scoringMessagesList: [],
            pinfl_status: -1,
            scoring_status: -1,
            mib_status: -1,
            kyc_status: {{$buyer->kyc_status}},
            buyer_status: '{{ $buyer->status }}',
            scoring_from_server: 0,
            disableScoringButton: false,
            freezeTime: 0,
            infoScoringInterval: null,
            katm_method: 'auto',
            doc_type: '{{$buyerPersonals->passport_type}}',

            first_name: '{{$buyer->name}}',
            last_name: '{{$buyer->surname}}',
            patronymic: '{{$buyer->patronymic}}',
            birth_date: '{{$buyerPersonals->birthday_open}}',
            issue_date: '{{$buyerPersonals->passport_date_issue_open}}',
            exp_date: '{{$buyerPersonals->passport_expire_date_open}}',
            // mrz: '',
            selectedRegion: '{{ $buyer->region }}' !== '' ? '{{ $buyer->region }}' : {{$default_region_id}},
            selectedArea: '{{ $buyer->local_region }}' !== '' ? '{{ $buyer->local_region }}' : '',
        };
    };


    var scoring_katm = new Vue({
        el: '#scoring_katm',
        data: {
            ...prepare_katm_data(),
            myIdReport: null,
            myIdReportLoading: false,
            buyer_personals_types: [],
            buyer_personals_types_loading: false,
            selected_file_type: null,
            image_uploader_loading: false,
            mime_types: ['.jpg', '.jpeg', '.png', '.bmp', '.webp'],
            selected_file: null,
            apiToken: '{{Auth::user()->api_token}}',
            buyerId: '{{$buyer->id}}',
            myId: null,
            // ↓ это все временно
            icons: {
                paperclip: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15.87 6.226L7.741 14.355C6.96 15.136 6.96 16.402 7.741 17.183C8.522 17.964 9.788 17.964 10.569 17.183L18.697 9.055C20.259 7.493 20.259 4.96 18.697 3.398C17.135 1.836 14.602 1.836 13.04 3.398L4.915 11.523C2.57 13.868 2.57 17.67 4.915 20.015C7.26 22.36 11.062 22.36 13.407 20.015L20.589 12.833" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                `,
                pencil: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12.4591 6.96379L14.9444 9.44914M3.98559 15.4364L15.4381 3.98389L20.0177 8.56345L8.56515 20.016H3.98438L3.98559 15.4364Z" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                `,
                trash: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18.7481 9.33297V18.332C18.7481 19.989 17.4051 21.332 15.7481 21.332H8.41505C6.75805 21.332 5.41505 19.989 5.41505 18.332V9.33297M4.08105 5.33497H20.0811M10.7481 2.66797H13.4151M8.66205 10.667V17.332M12.0811 10.667V17.332M15.3721 10.667V17.332" stroke="currentColor" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                `,
            }
        },
        created() {
            this.fetchBuyerPersonalTypes()
            this.fetchMyIdJob()
            this.checkMyId();
        },

        methods: {
            trimOnKeyup(e){
                e.target.value = String(e.target.value).trimStart()
            },
            onDateChange(e, min, max){
                let selectedDate = moment(e.target.value, 'YYYY-MM-DD')

                if (selectedDate > moment(max, 'YYYY-MM-DD'))
                    e.target.value = max
                    return
                if (selectedDate < moment(min, 'YYYY-MM-DD'))
                    e.target.value = min
                    return
            },
            async checkMyId() {
                let checkMyStatus = new FormData()
                checkMyStatus.append('api_token', this.apiToken);
                checkMyStatus.append('user_id', this.buyerId);

                axios.post('/api/v1/recovery/myid-status', checkMyStatus)
                    .then(response => {
                        this.myId = response.data
                    })
            },
            resetFilePicker: function (e) {
                this.selected_file_type = null
                this.selected_file = null
                this.$refs.fileInput.value = null

            },
            previewFiles: function (e) {
                let vueCtx = this
                let img, img_src, file, width, height;
                let _URL = window.URL || window.webkitURL;
                file = e.target.files[0]
                if (!file) return this.resetFilePicker()
                if (this.mime_types.indexOf(`.${file.type.replace('image/', '')}`) === -1) {
                    alert(`Выбран некорректный файл! \n\nВы можете загружать только изображения с расширением  ${mime_types.join(',')} `)
                    return this.resetFilePicker()
                }
                if (file.size > 7340032) {
                    alert(`Максимальный размер файла не должен превышать 7MB! \n\n Размер загруженного файла ${this.formatBytes(file.size)}`)
                    return this.resetFilePicker()
                }
                img_src = _URL.createObjectURL(file)
                img = new Image();
                img.onload = function () {
                    // if (this.width < 1024 || this.height < 720) {
                    //     alert(`Не подходящее разрешение изображения! \n\nРазрешение загруженного вами изображения: ${this.width}x${this.height}  \n\nДопустимое разрешение изображения - не менее 1024x720 пикселей`)
                    //     return vueCtx.resetFilePicker()
                    // }
                    vueCtx.selected_file = {
                        file,
                        url: img_src,
                        type: vueCtx.selected_file_type
                    }

                };
                img.src = img_src;

            },
            fetchBuyerPersonalTypes: function () {
                this.buyer_personals_types_loading = true;

                axios.get(`/api/v1/employee/buyers/get_buyer_personals_types?api_token=${this.api_token}`, {
                    headers: {'Content-Language': '{{app()->getLocale()}}'}
                }).then(response => {
                    this.buyer_personals_types_loading = false;
                    let response_data = response.data.data
                    if (!response_data) return

                    let buyer_types = []
                    for (const key in response_data)
                        buyer_types.push({name: response_data[key], value: key})

                    this.buyer_personals_types = buyer_types

                }).catch((e) => {
                    this.buyer_personals_types_loading = false;
                    console.error(e);
                });
            },
            formatBytes: function (a, b = 2, k = 1024) {
                with (Math) {
                    let d = floor(log(a) / log(k));
                    return 0 == a ? "0 bytes" : parseFloat((a / pow(k, d)).toFixed(max(0, b))) + " " + ["bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"][d]
                }
            },
            postBuyerPersonalImage: function () {
                this.image_uploader_loading = true;
                let formData = new FormData();
                formData.append('file', this.selected_file.file)
                formData.append('buyer_id', this.buyer_id)
                formData.append('type', this.selected_file_type.value)
                axios.post(`/api/v1/employee/buyers/add_additional_photo?api_token=${this.api_token}`, formData, {
                    headers: {
                        'Content-Language': '{{app()->getLocale()}}',
                        'Accept': 'application/json',
                        'Content-Type': 'multipart/form-data',
                    }
                }).then(response => {
                    this.image_uploader_loading = false;
                    if (!response.data.data) return

                    if (response.data.status === 'success') {
                        this.resetFilePicker()
                        this.replaceBuyerFiles(response.data.data)
                    }


                }).catch((e) => {
                    this.image_uploader_loading = false;
                    console.error(e);
                });
            },
            fetchMyIdJob: async function() {
                this.myIdReportLoading = true
                axios.get(`/api/v3/myid/job/report/${this.buyer_id}?api_token=${this.api_token}`,
                {headers: {'Content-Language': window.Laravel.locale}})
                .then(({data}) => {
                    if (data?.status === "success") this.myIdReport = data.data
                    else this.myIdReport = null
                    this.myIdReportLoading = false
                }).catch(e => {
                    this.myIdReportLoading = false
                    console.log('error');
                    console.log(e);
                });
            },
            replaceBuyerFiles: function (data) {
                generate_buyer_personal_files(data)
            },
            viewImage: function (imageSrc, title) {
                initPhotoViewer(imageSrc, title);
            },
            onFileTypeSelected: function () {
                if (this.selected_file_type) this.$refs.fileInput.click();

            },
            changeRegion: function () {
                console.dir(this.regions[this.region_id]);
                this.local_regions = this.regions[this.region_id].local_region;
                for (var loc in this.regions[this.region_id].local_region) {
                    this.local_region_id = this.regions[this.region_id].local_region[loc].id;
                    break;
                }
            },
            getReportKATMLink: function (token) {
                let post = {
                    api_token: this.api_token,
                    buyer_id: this.buyer_id,
                    token: token,
                    region_id: this.region_id,
                    local_region_id: this.local_region_id,
                };
                axios.post('/api/v1/employee/buyers/katm-report', post, {headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                    window.open(response.data.data.link, '_blank');

                    scoring.$forceUpdate();
                }).catch(e => {
                    console.log('error');
                    console.log(e);
                });
            },
            initScoringProcess: function () {



                this.disableScoringButton = true;
                let post = {
                    api_token: this.api_token,
                    katm_method: this.katm_method,
                    buyer_id: this.buyer_id,
                    // mrz: this.mrz,
                    passport: this.passport,
                    pinfl: this.pinfl,
                    first_name: this.first_name,
                    last_name: this.last_name,
                    patronymic: this.patronymic,
                    region_id: this.selectedRegion,
                    local_region_id: this.selectedArea,
                    send_sms: this.send_sms,

                    passport_date_issue: this.issue_date ? moment(this.issue_date, 'YYYY-MM-DD').format('DD.MM.YYYY') : null,
                    passport_expire_date: this.exp_date? moment(this.exp_date, 'YYYY-MM-DD').format('DD.MM.YYYY'): null,
                    passport_type: this.doc_type,
                    gender: this.gender,
                    birth_date: this.birth_date ? moment(this.birth_date, 'YYYY-MM-DD').format('DD.MM.YYYY') : null
                };

                axios.post('/api/v1/employee/buyers/init-scoring', post, {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                    .then(response => {
                        if (response.data.status === 'error') {
                            if (response.data.errors != undefined) {
                                response.data.errors.forEach(item => this.errors.push(item));
                            }
                            this.freezeTime = 10;
                            this.timerId = setInterval(() => {
                                if (this.freezeTime > 0) {
                                    this.freezeTime -= 1;
                                }
                                if (this.freezeTime === 0) {
                                    this.disableScoringButton = false;
                                    clearInterval(this.timerId);
                                    this.timerId = null;
                                }
                            }, 1000);
                            return;
                        }

                        this.timerId = setInterval(() => {
                            this.check();
                        }, 2000);

                    }).catch(e => {
                    console.log('error');
                });

            },
            drawBadge: function (type) {
                switch (type) {
                    case 1: {
                        return 'badge-success'
                    }
                    case 2: {
                        return 'badge-danger'
                    }
                    case 3: {
                        return 'badge-warning'
                    }
                    case 4: {
                        return 'badge-danger'
                    }
                    default: {
                        return '';
                    }
                }
            },
            check: function () {
                let post = {
                    api_token: this.api_token,
                    passport: this.passport,
                    pinfl: this.pinfl,
                    scoring: this.scoring,
                    buyer_id: this.buyer_id,
                    region_id: this.selectedRegion,
                    local_region_id: this.selectedArea,
                };
                axios.post('/api/v1/employee/buyers/check-scoring', post, {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                    .then(response => {

                        this.scoringMessagesList.splice(0);

                        this.scoringMessagesList.push({
                            id: 'check_approve_state',
                            name: '{{ __('scoring.check_approve_state_text') }}',
                            state: response.data.data.check_approve_state,
                            text: response.data.data.check_approve_state_text,
                        });

                        this.scoringMessagesList.push({
                            id: 'final_state',
                            name: '{{ __('scoring.final_state_text') }}',
                            state: response.data.data.final_state,
                            text: response.data.data.final_state_text,
                        });


                        if (response.data.data.total_state != null && response.data.data.total_state != 3) {
                            clearInterval(this.timerId);
                            this.timerId = null;
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        }

                    }).catch(e => {
                    clearInterval(this.timerId);
                    this.timerId = null;
                    console.log('error');
                });
            },
            workButton: async function () {
                alert('Труд — источник всякого богатства! Ваш PM ❤');
            },
        },
        computed: {
            isModerated(){
                return this.kyc_status != 0 && this.buyer_status != 4 && this.buyer_status != 8
            },
            birthDateMin(){ return moment().subtract(130, 'years').format('YYYY-MM-DD') },
            passportExpireMin(){ return moment().format('YYYY-MM-DD') },
            birthDateMax(){ return moment().subtract(16, 'years').format('YYYY-MM-DD') },
            passportExpireMax(){ return moment().add(10, 'years').format('YYYY-MM-DD') },
            scoringValidation(){
                return {
                    'doc_type': ()=> {
                        let errArray = []
                        if (Number(this.doc_type) != 0 && Number(this.doc_type) !=6 ) {
                            errArray.push(false)
                        }
                        return !errArray.includes(false)
                    }
                }
            },
            formatedPassport: {
                get() {
                    return this.passport;
                },
                set(value) {
                    this.passport = value.replaceAll(' ', '');
                },
            },
            formatedPinfl: {
                get() {
                    return this.pinfl;
                },
                set(value) {
                    this.pinfl = value.replaceAll(' ', '');
                },
            },
        },
    });

    function initPhotoViewer(src = '', title = '') {
        const items = [
            {src, title},
        ];
        const options = {index: 0};

        new PhotoViewer(items, options);
    }


    function initAllViewers() {
        const files = $('.buyer-personal-files a');
        const selected_image_container = $('#selected_image_container');

        if (files.length > 0) {
            files.each((index, file) => {
                $(file).click((e) => {
                    e.preventDefault();

                    const path = $(file).attr('data-imagesrc');
                    // const docPath = $(file).attr('data-docpath');
                    const title = $(file).attr('data-imagelabel');

                    const imageSrc = `{{ \App\Helpers\FileHelper::sourcePath() }}${path}`;
                    initPhotoViewer(imageSrc, title);
                });
            });
        }
    }

</script>
