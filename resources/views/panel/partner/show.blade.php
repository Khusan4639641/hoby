@extends('templates.panel.app')

@section('title', __('panel/partner.header_partner_view'))
@section('class', 'partners show')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.partners.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection


@section('content')
    <style>
        table.reason-list{
            width: 100%;
            display: block;
            overflow-x: hidden;
            overflow-y:auto;
            height: 250px;
            font-size: 14px;
        }
        table.reason-list>tbody>tr>td{
            border: 1px solid black;
            padding: 0px 10px;
        }
    </style>
<div id="partnerPage">
    <div class="organization">
        @if($partner->logo != null)
            <div class="preview" style="background-image: url({{env('SFTP_FILE_SERVER_DOMAIN') . "storage/" . $partner->logo->path}}); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
        @else
            <div class="preview dummy"></div>
        @endif

        <div class="info w-100">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="id">ID {{$partner->id}}</p>
                    <p class="name">{{$partner->brand??$partner->name}}</p>
                    <p class="brand">{!! $partner->brand?$partner->name:"" !!}</p>
                </div>
                <div class="info__company">
                    <p><span>Ответственный менеджер: </span> {{ $partner->manager->fio ?? __('panel/partner.not_found') }}</p>
                    <p><span>Торговая компания: </span> {{ $partner->generalCompany->name_ru ?? __('panel/partner.not_found') }}</p>
                </div>
            </div>
            <div class="description">{!! $partner->description !!}</div>
        </div>
    </div>

    <div class="row params">

        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_3')}}</div></td>
            <td><div class="value">{{@$partner->settings->markup_3}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_6')}}</div></td>
            <td><div class="value">{{@$partner->settings->markup_6}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_9')}}</div></td>
            <td><div class="value">{{@$partner->settings->markup_9}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_12')}}</div></td>
            <td><div class="value">{{@$partner->settings->markup_12}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.nds')}}</div></td>
            <td><div class="value">{{@$partner->settings->use_nds}}</div></td>
        </div>

        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.discount_3')}}</div></td>
            <td><div class="value">
                    {{@$partner->settings->discount_3}}%
                </div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.discount_6')}}</div></td>
            <td><div class="value">
                    {{@$partner->settings->discount_6}}%
                </div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.discount_9')}}</div></td>
            <td><div class="value">
                    {{@$partner->settings->discount_9}}%
                </div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.discount_12')}}</div></td>
            <td><div class="value">
                    {{@$partner->settings->discount_12}}%
                </div></td>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6">
            <div class="lead">{{__('panel/partner.txt_law_info')}}</div>
            <table class="table">
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_inn')}}</div></td>
                    <td><div class="value">{{$partner->inn}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_address')}}</div></td>
                    <td><div class="value">{{$partner->address}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_legal_address')}}</div></td>
                    <td><div class="value">{{$partner->legal_address}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_bank_name')}}</div></td>
                    <td><div class="value">{{$partner->bank_name}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_payment_account')}}</div></td>
                    <td><div class="value">{{$partner->payment_account}}</div></td>
                </tr>
            </table>
        </div><!-- /.col-12 col-md-6 -->

        <div class="col-12 col-md-6">
            <div class="lead">{{__('panel/partner.txt_contact_info')}}</div>
            <table class="table">
                <tr>
                    <td><div class="caption">{{__('panel/partner.fio')}}</div></td>
                    <td><div class="value">{{@$partner->user->fio}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.phone')}}</div></td>
                    <td><div class="value">{{@$partner->user->phone}}</div></td>
                </tr>
            </table>
        </div><!-- /.col-12 col-md-6 -->
    </div><!-- /.row -->

    <div id="confirm">

        @php
            $user = \Illuminate\Support\Facades\Auth::user();
        @endphp

        @if($user->hasPermission('modify-partner'))
            <div class="form-controls">
                <button type="button" v-if="status == 0" @click="confirm" class="btn btn-success">{{__('app.btn_unblock')}}</button>
                <button type="button" v-if="status == 0" @click="showModal = true" class="btn btn-outline-success">История блокировки</button>
                <button type="button" v-if="status == 1" @click="showPopup = true" class="btn btn-outline-danger">{{__('app.btn_block')}}</button>
                <button type="button" v-if="status == 1" @click="resend" class="btn btn-outline-success">{{__('panel/partner.btn_resend_password')}}</button>

                <a href="{{localeRoute('panel.partners.edit', $partner)}}" class="btn btn-primary ml-lg-auto">
                    {{__('panel/partner.btn_edit_data')}}
                </a>
            </div>
        @endif
        <div class="mt-3"><strong>{{__('panel/partner.status')}}</strong>: {{ $partner->status == 0 ? (__('panel/partner.blocked') . " ($partner->block_reason) $partner->block_date") : __('panel/partner.active') }}</div>
    </div>

    <div  v-show="showPopup" class="overlay">
        <form class="my-modal" style="width: 600px" @submit.prevent="block">
            <h5 class="my-modal__title"> <?php echo e(__('panel/partner.title_block')); ?></h5>
            <div id="reasons">
                <div id="reasons_history">
                    <div class="panel-group" >
                        <div class="panel panel-primary">
                            <div class="panel-body">
                                <table class="reason-list">
                                    <tbody>
                                    <tr v-for='(reason, index) in reasons'>
                                        <td>
                                            <span><input name='reason[]' :datatype="reason.type" onclick="requiredInput()" :value="reason.id"  type="checkbox"></span>
                                        </td>
                                        <td>
                                            <span>@{{ reason.name }}</span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <textarea id="reason_text" v-model="blockReasonVal" onkeyup = 'checkInput(this)'
                      style="width: 100%; height: 100px; resize: none;display: none;"></textarea>
            <div class="my-modal__footer text-right">
                <button class="btn btn-danger mr-1" type="button" @click="showPopup = false"><?php echo e(__('app.btn_cancel')); ?></button>
                <button class="btn btn-primary" id="submit" :disabled="blockReasonVal.length < 4" type="submit"><?php echo e(__('panel/buyer.send')); ?></button>
            </div>

        </form>
    </div>
    <div  v-show="showModal" class="overlay">
        <form class="my-modal" style="width: 1000px" @submit.prevent="block">
            <h5 class="my-modal__title"> История блокировки </h5>
            <div id="reasons">
                <div id="reasons_history">
                    <div class="panel-group" >
                        <div class="panel panel-primary">
                            <div class="panel-body">
                                <table class="reason-list">
                                    <thead>
                                        <tr>
                                            <th style="text-align: center;vertical-align: middle;">№</th>
                                            <th style="text-align: center;vertical-align: middle;width: 100px">Дата</th>
                                            <th style="text-align: center;vertical-align: middle;">Тип действия</th>
                                            <th style="text-align: center;vertical-align: middle;">Причина блокировки/разблокировки</th>
                                            <th style="text-align: center;vertical-align: middle;">Ф.И.О администратора</th>
                                            <th style="text-align: center;vertical-align: middle;">Ф.И.О закреплённого менеджера</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for='(reason, index) in history' style="border-bottom: 1px #cccccc solid">
                                        <td style="text-align: center;">
                                            <span>@{{ index+1 }}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span>@{{ reason.data }}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span>@{{ reason.type }}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span>@{{ reason.comment }}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span>@{{ reason.admin }}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span>@{{ reason.manager }}</span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <div class="my-modal__footer text-right">
                <button class="btn btn-danger mr-1" type="button" @click="showModal = false"><?php echo e(__('app.btn_close')); ?></button>
            </div>

        </form>
    </div>
</div>
    <script>
        var confirm = new Vue({
            el: '#partnerPage',
            data: {
                errors: [],
                messages: [],
                api_token: '{{Auth::user()->api_token}}',
                partner_id: '{{$partner->id}}',
                status: '{{$partner->status}}',
                showPopup: false,
                showModal: false,
                blockReasonVal: '',
                reasons: [],
                history: [],
            },
            methods: {
                confirm() {
                    this.errors = [];
                    this.messages = [];

                    axios.post('/api/v1/employee/partners/action/confirm', {
                        api_token: this.api_token,
                        partner_id: this.partner_id,
                    },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        if (response.data.status === 'success') {
                            this.status = 1;
                            response.data.response.message.forEach(element => this.messages.push(element.text));
                            location.reload()
                        }
                    })
                },

                resend() {
                    this.errors = [];
                    this.messages = [];

                    axios.post('/api/v1/employee/partners/action/resend', {
                        api_token: this.api_token,
                        partner_id: this.partner_id
                    },{ headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        if (response.data.status === 'success') {
                            this.status = 1;
                            response.data.response.message.forEach(element => this.messages.push(element.text));

                            const encodedPdf = response.data?.data?.pdf;
                            if(!encodedPdf) {
                                return
                            }
                            const dataURL = URL.createObjectURL(b64toBlob(encodedPdf, 'application/pdf'));

                            const temporaryLink = document.createElement('a')
                            temporaryLink.href = dataURL
                            temporaryLink.download = `Смена пароля партнёра №${this.partner_id}`
                            document.body.append(temporaryLink);
                            temporaryLink.click();
                            temporaryLink.detach()

                        }
                    })
                },

                block() {
                    this.messages = [];
                    if(this.blockReasonVal){
                        let checkId = this.reasons[this.reasons.length-1].id
                        if(sendReasons.indexOf(checkId)===-1) {
                            this.blockReasonVal=''
                        }
                    }
                    axios.post('/api/v1/employee/partners/action/block', {
                        api_token: this.api_token,
                        partner_id: this.partner_id,
                        block_reason: this.blockReasonVal,
                        block_reasons_id: sendReasons,
                    },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        if (response.data.status === 'success') {
                            this.status = 0;
                            response.data.response.message.forEach(element =>  polipop.add({content: element.text, title: '{{__('panel/contract.successfully_saved')}}', type: 'success'}));
                            this.showPopup = false;
                            location.reload()
                        }
                    })
                },
                addRow: function() {
                    axios.post('/api/v1/employee/partners/action/show-reasons', {
                        api_token: '{{Auth::user()->api_token}}'
                    },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        response.data.forEach(element => this.reasons.push({name:element.name,id:element.id,type:element.position}))
                    })
                    return this.reasons;
                },
                showHistory: function() {
                    axios.post('/api/v1/employee/partners/action/show-history', {
                        api_token: '{{Auth::user()->api_token}}',
                        partner_id: '{{$partner->id}}'
                    },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        this.history = response.data
                    })
                    return this.history;
                }
            },
            created: function(){
                this.addRow()
                this.showHistory()
            }
        });
        let sendReasons = []
        function requiredInput() {
            let inputCheckTop = false
            let inputCheckBottom = false
            let textareaButton = false
            let textarea = document.querySelector("#reason_text")
            let reasons = []
            document.querySelectorAll("input[name='reason[]']").forEach(function (el) {
                if(el.getAttribute("datatype") === 'top')
                {
                    if(el.checked) {
                        inputCheckTop = true
                        reasons.push(Number(el.value))
                    }
                } else {
                    if(el.checked) {
                        inputCheckBottom = true
                        reasons.push(Number(el.value))
                        textarea.setAttribute('style',"width: 100%; height: 100px; resize: none;display: block;")
                    }else{
                        textarea.setAttribute('style',"width: 100%; height: 100px; resize: none;display: none;")
                        textarea.value = ""
                    }
                }
            })
            sendReasons = reasons
            if(textarea.value.length>4){
                textareaButton = true
            }
            buttonDisabled(inputCheckTop)
            if(inputCheckBottom){
                buttonDisabled(textareaButton)
            }
            if(textarea.value.length>4){
                buttonDisabled(true)
            }
        }
        function buttonDisabled($arg) {
            let button = document.querySelector("button#submit")
            if($arg) {
                button.removeAttribute("disabled")
            } else {
                button.setAttribute("disabled",true)
            }
        }
        function checkInput($arg) {
            if($arg.value.length < 4) {
                requiredInput()
            } else {
                buttonDisabled(true)
            }
        }
        function b64toBlob(b64Data, contentType='', sliceSize=512) {
            const byteCharacters = atob(b64Data);
            const byteArrays = [];

            for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
                const slice = byteCharacters.slice(offset, offset + sliceSize);

                const byteNumbers = new Array(slice.length);
                for (let i = 0; i < slice.length; i++) {
                    byteNumbers[i] = slice.charCodeAt(i);
                }

                const byteArray = new Uint8Array(byteNumbers);
                byteArrays.push(byteArray);
            }

            const blob = new Blob(byteArrays, {type: contentType});
            return blob;
        }
    </script>
@endsection
