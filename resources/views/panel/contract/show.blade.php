@extends('templates.panel.app')

@section('title', __('panel/contract.contract_n') . $contract->id)
@section('print_act')
    <a href="{{ localeRoute('panel.generateAct', [$contract->id, 'contract_pdf'] ) }}" target="_blank" class="btn btn-outline-danger btn-sm" style='font-size: 18px; border-radius: .5rem'> {{ __('panel/contract.generate_act') }} </a>
    <a href="{{ localeRoute('panel.generateAct', [$contract->id, 'contract_pdf_qr'] ) }}" target="_blank" class="btn btn-outline-danger btn-sm" style='font-size: 18px; border-radius: .5rem'> {{ __('panel/contract.generate_act_qr') }} </a>
    <a href="{{ localeRoute('panel.downloadAct') }}/{{ $contract->id }}" target="_blank" class="btn btn-outline-info btn-sm" style='font-size: 18px; border-radius: .5rem'> {{ __('panel/contract.print_act') }} </a>
    @if (Auth::user()->hasRole('admin') && ($contract->status == 1 || $contract->status == 3 || $contract->status == 4) )
        <button type="button" data-toggle="modal" data-target="#cancelContractModal" class="btn btn-outline-danger btn-sm" style='font-size: 18px; border-radius: .5rem'> {{ __('panel/contract.cancel_contract') }} </button>
    @endif

@endsection
@section('title_date', $contract->created_at)
@section('class', 'contracts show')

@section('center-header-control')
    <span class="status-{{ $contract->status }}">{{ $contract->status_caption }}</span>
@endsection


@section('content')
<style>
    .btn.processing {
        cursor: disabled;
        position: relative;
        padding-right: 2rem;
        pointer-events: none;

    }
    .btn .spinner {
        width: 1rem;
        height: 1rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        display: none;
        position: absolute;
        top: calc(50% - 0.5rem);
    }
    .btn.processing .spinner {
        display: inline-block;
    }
</style>
    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <a class="nav-link active" id="nav-contract-tab" data-toggle="tab" href="#nav-contract" role="tab"
               aria-controls="nav-contract" aria-selected="true">{{__('panel/contract.header_contract')}}</a>
            {{--<a class="nav-link" id="nav-insurance-tab" data-toggle="tab" href="#nav-insurance" role="tab" aria-controls="nav-insurance" aria-selected="false">{{__('panel/insurance.header_insurance')}}</a>--}}
            <a class="nav-link" id="nav-insurance-tab" data-toggle="tab" href="#nav-lawsuit" role="tab"
               aria-controls="nav-lawsuit" aria-selected="false">{{__('panel/lawsuit.header_lawsuit')}}</a>
        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="nav-contract" role="tabpanel" aria-labelledby="nav-contract-tab">
            @include('panel.contract.parts.contract')
        </div>


        <div class="tab-pane fade" id="nav-lawsuit" role="tabpanel" aria-labelledby="nav-lawsuit-tab">
            @include('panel.contract.parts.lawsuit')
        </div>


      <div class="verify-act mt-3" id="act">
            <div v-for="item in act.message" :class="'alert alert-'+ item.type">
                @{{item.text}}
            </div>

            <div class="row">
                {{-- </div><!-- /#act --> --}}

                <div class="col-md-3">
                    <div class="lead">{{__('billing/order.title_upload_act')}}</div>
                    <div v-for="item in act.message" :class="'alert alert-'+ item.type">
                        @{{item.text}}
                    </div>
                    <form v-if="act.status != 1 && act.status != 3" action="" @submit="uploadAct">
                        <div class="alert alert-info" v-if="act.status == 2">
                            {{__('billing/contract.act_status_2')}}
                        </div>

                        <div class="form-row">
                            <div class="form-group col-12">
                                <component is="style">
                                    .custom-file-label:after {
                                    content: "{{__('app.btn_choose_file')}}";
                                    }
                                </component>

                                <div class="custom-file">
                                    <input class="custom-file-input" @change="updateFiles"
                                           accept=".png, .jpg, .jpeg, .gif" name="act" type="file" id="act">
                                    <label class="custom-file-label" for="act">
                                        <span v-if="act.new && act.new.name">@{{ act.new.name }}</span>
                                        <span v-else>{{__('app.btn_choose_file')}}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group col-12">
                                <button class="btn btn-orange btn-block">{{__('app.btn_upload')}}</button>
                            </div>

                        </div>
                        <!-- /.form-row -->
                    </form>
                    <div v-else>
                        <div class="alert alert-info" v-if="act.status == 1">
                            {{__('billing/contract.act_status_1')}}
                        </div>
                        <div class="alert alert-success" v-if="act.status == 3">
                            {{__('panel/contract.act_status_3')}}
                        </div>

                        <div class="row">
                            @if(count($contract->acts) > 0)
                                <div class="col-12 mb-3">
                                    <a
                                        target="_blank"
                                        class="btn btn-outline-primary btn-block links-for-viewer"
                                        href="{{ \App\Helpers\FileHelper::url($contract->acts->last()->path) }}"
                                        data-imagesrc="{{ $contract->acts->last()->path }}"
                                        data-imagelabel="{{__('panel/contract.'.$contract->acts->last()->type)}}"
                                    >
                                        {{__('billing/contract.act_view')}}
                                    </a>
                                </div>
                            @endif
                            <div v-if="act.status == 1" class="col-6">
                                <button v-on:click="changeActStatus(2)"
                                        type="button"
                                        class="btn btn-outline-danger btn-block"
                                >
                                    {{__('panel/contract.btn_cancel_act')}}
                                </button>
                            </div>
                            <div v-if="act.status == 1" class="col-6">
                                <button v-on:click="changeActStatus(3)"
                                        type="button"
                                        class="btn btn-outline-success btn-block"
                                >
                                    {{__('panel/contract.btn_submit_act')}}
                                </button>
                            </div>
                        </div>
                    </div>
                    <a v-if="signedActLink" :href="signedActLink">{{ __('panel/contract.signed_act_link') }}</a>
                </div>
                {{-- </div><!-- /#client-photo --> --}}
                <div class="col-md-3">
                    <div class="lead">{{__('billing/order.title_upload_client_photo')}}</div>
                    <div class="client_photo" id="client_photo">
                        <div v-for="item in client_photo.message" :class="'alert alert-'+ item.type">
                            @{{item.text}}
                        </div>

                        <form v-if="client_photo.status != 1 && client_photo.status != 3" action="" @submit="uploadClientPhoto">
                            <div class="alert alert-info" v-if="client_photo.status == 2">
                                {{__('billing/contract.client_photo_status_2')}}
                            </div>

                            <div class="form-row">
                                <div class="form-group col-12">
                                    <component is="style">
                                        .custom-file-label:after {
                                        content: "{{__('app.btn_choose_file')}}";
                                        }
                                    </component>

                                    <div class="custom-file">
                                        <input class="custom-file-input" @change="updateClientPhotoFiles"
                                               accept=".png, .jpg, .jpeg, .gif" name="client_photo" type="file"
                                               id="client_photo">
                                        <label class="custom-file-label" for="client_photo">
                                            <span v-if="client_photo.new && client_photo.new.name">@{{ client_photo.new.name }}</span>
                                            <span v-else>{{__('app.btn_choose_file')}}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-12">
                                    <button class="btn btn-orange btn-block">{{__('app.btn_upload')}}</button>
                                </div>

                            </div>
                            <!-- /.form-row -->
                        </form>
                        <div v-else>

                            <div class="alert alert-info" v-if="client_photo.status == 3">
                                {{__('billing/contract.client_photo_status_3')}}
                            </div>
                            <div class="alert alert-success" v-if="client_photo.status == 1">
                                {{__('billing/contract.client_photo_status_1')}}
                            </div>

                            <div class="row">

                                @if(count($contract->clientPhotos) > 0)
                                    <div class="col-12 mb-3">
                                        <a
                                            target="_blank"
                                            class="btn btn-outline-primary btn-block links-for-viewer"
                                            href="{{ \App\Helpers\FileHelper::url($contract->clientPhotos->last()->path) }}"
                                            data-imagesrc="{{ $contract->clientPhotos->last()->path }}"
                                            data-imagelabel="{{__('panel/contract.'.$contract->clientPhotos->last()->type)}}"
                                        >
                                            {{__('billing/contract.client_photo_view')}}
                                        </a>
                                    </div>
                                @endif

                                <div v-if="client_photo.status == 3" class="col-6">
                                    <button
                                        v-on:click="changeClientPhotoStatus(2)"
                                        type="button"
                                        class="btn btn-outline-danger btn-block"
                                    >
                                        {{__('panel/contract.btn_cancel_client_photo')}}
                                    </button>

                                </div>

                                <div v-if="client_photo.status == 3" class="col-6">
                                    <button
                                        v-on:click="changeClientPhotoStatus(1)"
                                        type="button"
                                        class="btn btn-outline-success btn-block"
                                    >
                                        {{__('panel/contract.btn_submit_client_photo')}}
                                    </button>

                                </div>

                            </div>
                        </div>
                    </div>
                </div><!-- /#client_photo -->

                {{-- </div><!-- /#imei --> --}}
                <div class="col-md-3">
                    <div class="lead">{{__('billing/order.title_upload_imei')}}</div>
                    <div class="imei" id="imei">
                        <div v-for="item in imei.message" :class="'alert alert-'+ item.type">
                            @{{item.text}}
                        </div>
                        <form v-if="(imei.status != 1 && imei.status != 3) && category == 1" action="" @submit="uploadImei">
                            <div class="alert alert-info" v-if="imei.status == 2">
                                {{__('billing/contract.imei_status_2')}}
                            </div>

                            <div class="form-row">
                                <div class="form-group col-12">
                                    <component is="style">
                                        .custom-file-label:after {
                                        content: "{{__('app.btn_choose_file')}}";
                                        }
                                    </component>

                                    <div class="custom-file">
                                        <input class="custom-file-input" @change="updateImeiFiles"
                                               accept=".png, .jpg, .jpeg, .gif" name="imei" type="file" id="imei">
                                        <label class="custom-file-label" for="imei">
                                            <span v-if="imei.new && imei.new.name">@{{ imei.new.name }}</span>
                                            <span v-else>{{__('app.btn_choose_file')}}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-12">
                                    <button class="btn btn-orange btn-block">{{__('app.btn_upload')}}</button>
                                </div>

                            </div>
                            <!-- /.form-row -->
                        </form>
                        <div v-else>

                            <div class="alert alert-info" v-if="imei.status == 3">
                                {{__('billing/contract.imei_status_3')}}
                            </div>
                            <div class="alert alert-success" v-if="imei.status == 1">
                                {{__('panel/contract.imei_status_1')}}
                            </div>

                            <div class="row">
                                @if(count($contract->imeis) > 0)
                                    <div class="col-12 mb-3">
                                        <a
                                            target="_blank"
                                            class="btn btn-outline-primary btn-block links-for-viewer"
                                            href="{{ \App\Helpers\FileHelper::url($contract->imeis->last()->path) }}"
                                            data-imagesrc="{{ $contract->imeis->last()->path }}"
                                            data-imagelabel="{{__('panel/contract.'.$contract->imeis->last()->type)}}"
                                        >
                                            {{__('billing/contract.imei_view')}}
                                        </a>
                                    </div>
                                @endif
                                <div v-if="imei.status == 3" class="col-6">
                                    <button
                                        v-on:click="changeImeiStatus(2)"
                                        type="button"
                                        class="btn btn-outline-danger btn-block"
                                    >
                                        {{__('panel/contract.btn_cancel_imei')}}
                                    </button>
                                </div>
                                <div v-if="imei.status == 3" class="col-6">
                                    <button
                                        v-on:click="changeImeiStatus(1)"
                                        type="button"
                                        class="btn btn-outline-success btn-block"
                                    >
                                        {{__('panel/contract.btn_submit_imei')}}
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /#imei -->

                <div class="col-md-3">
                    <div v-if="contract.status == 10" class="row">
                        <div class="col-12 mb-3">
                            <button
                                v-on:click="changeContractStatus(5)"
                                type="button"
                                class="btn btn-outline-danger btn-block"
                            >
                                {{__('panel/contract.btn_cancel_contract_name')}}
                            </button>
                        </div>
                        <div class="col-6">
                            <button
                                data-toggle="modal"
                                data-target="#dontVerifyModal"
                                type="button"
                                class="btn btn-outline-danger"
                            >
                                {{__('panel/contract.btn_cancel_contract')}}
                            </button>

                        </div>
                        <div class="col-6">
                            <button v-on:click="changeContractStatus(1)"
                                    class="btn btn-success btn-block"
                            >
                                {{__('panel/contract.btn_submit_contract')}}
                            </button>

                        </div>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="dontVerifyModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                     aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"
                                    id="exampleModalLabel">{{__('panel/buyer.txt_reason_denay')}}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">

                                <label>{{__('panel/buyer.verify_message')}}</label>
                                <select v-model="contract.text" name="denay_reasons" class="form-control"
                                        multiple="true" @change="selectReason">
                                    @foreach($denay_reasons as $key => $reason)
                                        {{--}}<option value="{{1 . $key}}">{{$reason}}</option>--}}
                                        <option value="{{$reason}}">{{$reason}}</option>
                                    @endforeach
                                </select>

                            </div>
                            <div class="modal-body">
                                <label>{{__('panel/buyer.text_message')}}</label>
                                <textarea id="text" name="text" rows="4" cols="50">

                                </textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                        data-dismiss="modal">{{__('app.btn_close')}}</button>
                                <button type="button" @click="changeContractStatus(10)"
                                        class="btn btn-outline-danger">{{__('panel/buyer.send_back_for_revision')}}</button>

                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- Modal -->

            <!-- /#cancel_act -->
            {{--}}<div class="col-md-3">
                <div class="verify-act mt-0 p-0" id="cancel_act">

                    <div class="lead">{{__('billing/order.title_cancel_act')}}</div>
                    <div v-for="item in cancel_act.message" :class="'alert alert-'+ item.type">
                        @{{item.text}}
                    </div>


                    <div class="alert alert-info" v-if="cancel_act.status == 1">
                        {{__('billing/contract.act_status_1')}}
                    </div>
                    <div class="alert alert-info" v-if="cancel_act.status == 2">
                        {{__('billing/contract.cancel_act_status_2')}} @{{ cancel_act.cancel_reason}}
                    </div>
                    <div class="alert alert-info" v-if="cancel_act.status == 3">
                        {{__('billing/contract.cancel_act_status_3')}}
                    </div>

                    <div class="row">
                        <div v-if="cancel_act.path2" class="col-12">
                            <a
                                target="_blank"
                                class="btn btn-outline-primary btn-block"
                                :href="cancel_act.path2"
                            >
                                {{__('billing/contract.act_view')}}
                            </a>
                        </div>
                        <div v-if="cancel_act.status == 1" class="col-6">
                            <button
                                type="button"
                                class="btn btn-outline-success"
                                v-on:click="changeCancelActStatus(3)"
                            >
                                {{__('panel/contract.btn_submit_act')}}
                            </button>
                        </div>
                        <div v-if="cancel_act.status == 1" class="col-6">
                            <button
                                v-on:click="changeCancelActStatus(2)"
                                type="button"
                                class="btn btn-outline-danger"
                            >
                                {{__('panel/contract.btn_cancel_act')}}
                            </button>
                        </div>
                    </div>

                    <input
                        v-if="cancel_act.status == 1"
                        v-model="cancel_act.cancel_reason"
                        type="text"
                        placeholder="укажите причину отказа"
                    >
                </div>
            </div>
        </div>
    </div>--}}
            <!-- /#cancel_act -->

            <script>
                const STORAGE_DOMAIN = @json(Config::get('test.sftp_file_server_domain'));
                const signedContract = @json($signedContract);
                const signedActLink  = signedContract ? `${STORAGE_DOMAIN}storage/${signedContract}` : null;

                // TODO: Refactoring
                @php
                    use App\Helpers\FileHelper;$imeiPath = '';
                    if ($contract->imei) {
                        $imeiPath = FileHelper::url($contract->imei->path);
                    }
                    $cancelActPath = '';
                    if ($contract->cancelAct) {
                        $cancelActPath = FileHelper::url($contract->cancelAct->path);
                    }

                    $clientPhotoPath = '';
                    if ($contract->clientPhoto) {
                        $clientPhotoPath = FileHelper::url($contract->clientPhoto->path);
                    }
                @endphp

            var act = new Vue({
                    el: '#act',
                    data: {
                        signedActLink,
                        contract: {
                            status: {{ $contract->status }},
                            new: null,
                            message: [],
                            text: '',
                        },
                        act: {
                            status: {{ $contract->act_status }},
                            new: null,
                            message: [],
                            path: '{{$contract->act?'/storage/'.$contract->act->path:null}}',
                        },
                        imei: {
                            status: '{{ $contract->imei_status }}',
                            new: null,
                            message: [],
                            path: '{{ $imeiPath }}',
                        },

                        client_photo: {
                            status: '{{ $contract->client_status }}',
                            new: null,
                            message: [],
                            path: '{{ $clientPhotoPath }}',  // ??
                        },

                        category: '{{ $category }}',

                        cancel_act: {
                            status: '{{$contract->cancel_act_status}}',
                            new2: null,
                            message: [],
                            cancel_reason: `{{$contract->cancel_reason}}`,
                            path2: '{{ $cancelActPath }}',
                        },

                    },
                    methods: {
                        selectReason(e) {
                            const reason = e.target.value;
                            const textArea = document.querySelector('#text');
                            textArea.innerHTML += reason + '\n';
                        },
                        changeContractStatus(status = null) {
                            this.contract.text = document.querySelector('#text').value;
                            if (status === 5) {
                                this.contract.text = 'wrong name';
                            }

                            this.loading = true;
                            confirm('Вы уверены?');

                            if (status != null) {
                                axios.post('/api/v1/contracts/contract-cancel', {
                                    contract_status: status,
                                    cancel_reason: this.contract.text,
                                    api_token: '{{Auth::user()->api_token}}',
                                    id: '{{$contract->id}}',
                                }, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                                    .then(response => {
                                        if (response.data.status === 'success') {
                                            console.log(response.data);
                                            this.contract.status = status;
                                            location.reload();
                                        } else {
                                            this.contract.message = response.data.response.message[0].text;
                                        }

                                        this.loading = false;
                                        act.$forceUpdate();
                                    });
                            }
                        },
                        changeActStatus(status = null) {
                            this.loading = true;
                            if (status != null) {
                                axios.post('/api/v1/contracts/act-status', {
                                    act_status: status,
                                    api_token: '{{Auth::user()->api_token}}',
                                    id: '{{$contract->id}}',
                                }, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                                    .then(response => {
                                        if (response.data.status === 'success') {
                                            this.act.status = status;
                                        } else {
                                            this.act.message = response.data.response.message;
                                        }

                                        this.loading = false;
                                        act.$forceUpdate();
                                    });
                            }
                        },

                        changeCancelActStatus(status = null) {
                            this.loading = true;
                            if (status != null) {
                                axios.post('/api/v1/contracts/cancel-act-status', {
                                    cancel_act_status: status,
                                    cancel_reason: this.cancel_act.cancel_reason,
                                    api_token: '{{Auth::user()->api_token}}',
                                    id: '{{$contract->id}}',
                                }, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                                    .then(response => {
                                        if (response.data.status === 'success') {
                                            console.log(this.cancel_act.cancel_reason);
                                            this.cancel_act.status = status;
                                        } else {
                                            this.cancel_act.message = response.data.response.message;
                                        }

                                        this.loading = false;
                                        act.$forceUpdate();
                                    });
                            }
                        },

                        uploadAct(e) {
                            e.preventDefault();

                            this.loading = true;
                            this.act.message = [];

                            if (this.act.new != null) {
                                formData = new FormData();
                                formData.append('api_token', '{{Auth::user()->api_token}}');
                                formData.append('id', '{{$contract->id}}');
                                formData.append('act', this.act.new);
                                formData.append('act_status', 3);

                                axios.post('/api/v1/contracts/upload-act', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                                    .then(response => {
                                        if (response.data.status === 'success') {
                                            this.act.status = 3;
                                            this.act.path = '/storage/' + response.data.data.path;
                                        } else {
                                            this.act.status = 0;
                                            this.act.new = null;
                                            this.act.message = response.data.response.message;
                                        }

                                        this.loading = false;
                                        app.$forceUpdate();
                                    });
                            } else {
                                this.act.message.push({
                                    'type': 'danger',
                                    'text': '{{__('app.btn_choose_file')}}',
                                });
                            }

                            this.loading = false;

                        },

                        updateFiles(e) {
                            let files = e.target.files;

                            if (files.length > 0)
                                this.act.new = files[0];

                            if (this.act.old) {
                                this.files_to_delete.push(this.act.old);
                            }
                        },

                        /** модерация фото клиента с товаром */
                        changeClientPhotoStatus(status = null) {
                            this.loading = true;
                            if (status != null) {
                                axios.post('/api/v1/contracts/client-photo-status', {
                                    client_status: status,
                                    api_token: '{{Auth::user()->api_token}}',
                                    id: '{{$contract->id}}',
                                }, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                                    .then(response => {
                                        if (response.data.status === 'success') {
                                            this.client_photo.status = status;
                                        } else {
                                            this.client_photo.message = response.data.response.message;
                                        }

                                        this.loading = false;
                                        act.$forceUpdate();
                                    });
                            }
                        },

                        uploadClientPhoto(e) {
                            e.preventDefault();

                            this.loading = true;
                            this.client_photo.message = [];

                            if (this.client_photo.new != null) {
                                formData = new FormData();
                                formData.append('api_token', '{{Auth::user()->api_token}}');
                                formData.append('id', '{{$contract->id}}');
                                formData.append('client_photo', this.client_photo.new);
                                formData.append('client_status', 3);

                                axios.post('/api/v1/contracts/upload-client-photo', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                                    .then(response => {
                                        if (response.data.status === 'success') {
                                            this.client_photo.status = 3;
                                            this.client_photo.path = '/storage/' + response.data.data.path;
                                        } else {
                                            this.client_photo.status = 2;
                                            this.client_photo.new = null;
                                            this.client_photo.message = response.data.response.message;
                                        }

                                        this.loading = false;
                                        act.$forceUpdate();
                                    });
                            } else {
                                this.client_photo.message.push({
                                    'type': 'danger',
                                    'text': '{{__('app.btn_choose_file')}}',
                                });
                            }

                            this.loading = false;

                        },

                        updateClientPhotoFiles(e) {
                            let files = e.target.files;

                            if (files.length > 0)
                                this.client_photo.new = files[0];

                            if (this.client_photo.old) {
                                this.files_to_delete.push(this.client_photo.old);
                            }

                        },

                        /** добавление фото imei */

                        changeImeiStatus(status = null) {
                            this.loading = true;
                            if (status != null) {
                                axios.post('/api/v1/contracts/imei-status', {
                                    imei_status: status,
                                    api_token: '{{Auth::user()->api_token}}',
                                    id: '{{$contract->id}}',
                                }, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                                    .then(response => {
                                        if (response.data.status === 'success') {
                                            this.imei.status = status;
                                        } else {
                                            this.imei.message = response.data.response.message;
                                        }

                                        this.loading = false;
                                        act.$forceUpdate();
                                    });
                            }
                        },

                        uploadImei(e) {
                            e.preventDefault();

                            this.loading = true;
                            this.imei.message = [];

                            if (this.imei.new != null) {
                                formData = new FormData();
                                formData.append('api_token', '{{Auth::user()->api_token}}');
                                formData.append('id', '{{$contract->id}}');
                                formData.append('imei', this.imei.new);
                                formData.append('imei_status', 3);

                                axios.post('/api/v1/contracts/upload-imei', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                                    .then(response => {
                                        if (response.data.status === 'success') {
                                            this.imei.status = 3;
                                            this.imei.path = '/storage/' + response.data.data.path;
                                        } else {
                                            this.imei.status = 2;
                                            this.imei.new = null;
                                            this.imei.message = response.data.response.message;
                                        }

                                        this.loading = false;
                                        act.$forceUpdate();
                                    });
                            } else {
                                this.imei.message.push({
                                    'type': 'danger',
                                    'text': '{{__('app.btn_choose_file')}}',
                                });
                            }

                            this.loading = false;

                        },

                        updateImeiFiles(e) {
                            let files = e.target.files;

                            if (files.length > 0)
                                this.imei.new = files[0];

                            if (this.imei.old) {
                                this.files_to_delete.push(this.imei.old);
                            }

                        },

                    },
                });


                function initPhotoViewer(src = '', title = '') {
                    const items = [
                        { src, title },
                    ];
                    const options = { index: 0 };

                    new PhotoViewer(items, options);
                }

                const files = document.querySelectorAll('.links-for-viewer');

                if (files.length > 0) {
                    files.forEach(item => {
                        item.addEventListener('click', e => {
                            e.preventDefault();
                            const path = item.getAttribute('data-imagesrc');
                            const title = item.getAttribute('data-imagelabel');
                            const imageSrc = `{{ \App\Helpers\FileHelper::sourcePath() }}${path}`;
                            initPhotoViewer(imageSrc, title);
                        })
                    })
                }

            </script>
        </div>

        @if($manager)
            <div class="row manager" style="background: #F2F2F2;padding: 20px;border-radius: 5px;margin: 20px 0px;">
                <duv class="col">
                    <span style="font-size: 24px;font-weight: bold;">Ответственный менеджер:</span> <span style="font-size: 24px;">{{ $manager->name." ".$manager->surname }}</span>
                </duv>
            </div>
        @endif

      <div id = "test" >
        <div id="file_history" v-if="files.length > 0 ">
          <div class="panel-group" >
            <div class="panel panel-primary">
              <div class="panel-heading"> <h3>{{__('contract.files_history')}}</h3> <button class="btn btn-outline-success btn-sm" onclick="exportTable()">Скачать EXCEL</button></div>
              <div class="panel-body">
                <table class="table table-bordered" >
                  <thead class="text text-success">
                  <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Тип файла</th>
                    <th>Имя сотрудника</th>
                    <th>Статус</th>
                    <th>Ссылка</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for='(file, index) in files'>
                    <td>
                      <span>@{{ index+1 }}</span>
                    </td>
                    <td>
                      <span>@{{ file.created_date }}</span>
                    </td>
                    <td>
                      <span>@{{ file.type }}</span>
                    </td>
                    <td>
                      <span>@{{ file.fullname }}</span>
                    </td>
                    <td>
                      <span>@{{ file.status_file }}</span>
                    </td>
                    <td>
                      <a :href='file.url' target="_blank">@{{ file.name }}</a>
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
          <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
          <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" ></script>
          <script type="text/javascript"  src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js" ></script>
        <script >
          let responses = []

          function statusFile(row){
            let statusFiles = [
              {id: 0, type: '{{__('contract.file_status_new')}}'},
              {id: 1, type: '{{__('contract.file_status_cancel')}}'},
              {id: 2, type: '{{__('contract.file_status_accept')}}'}
            ], result

            statusFiles.forEach(function ( status ) {
              if(status.id === row.status_file) {
                result = status.type;
              }
            })
            return result;
          }

          function fileType(row){
              let typeFiles = [
                  {id: 'act', type: '{{__('contract.file_type_act')}}'},
                  {id: 'imei', type: '{{__('contract.file_type_imei')}}'},
                  {id: 'client_photo', type: '{{__('contract.file_type_photo_client')}}'}
              ], result

              typeFiles.forEach(function ( status ) {
                  if(status.id === row.type) {
                      result = status.type;
                  }
              })
              return result;
          }
            let exportData = [['Дата', 'Тип файла', 'Имя сотрудника', 'Статус', 'Ссылка']];
            let app = new Vue({
              el: '#file_history',
              data: {
                  files:[]
              },
              methods: {
                  addHistory: function () {
                      let formData = new FormData()
                          formData.append('api_token', '{{Auth::user()->api_token}}')
                          formData.append('id', '{{$contract->id}}')

                      axios.post('/api/v1/contracts/show-history-files', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                          .then(response => {
                              response.data.forEach(function (info) {
                                  info.status_file = statusFile(info)
                                  info.type = fileType(info)
                                  exportData.push([
                                      info.created_date,
                                      info.fullname,
                                      info.type,
                                      info.status_file,
                                      info.url,
                                  ])
                                  app.files.push(info)
                              })
                          })
                  }
              },
              mounted: function (){
                  this.addHistory()
                },
          });
          function exportTable() {
              const wb = XLSX.utils.book_new();
              const ws = XLSX.utils.aoa_to_sheet(exportData);
              ws['!autofilter'] = { ref: "A1:E1" };
              XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
              XLSX.writeFile(wb, "filesHistory_contract_{{$contract->id}}.xlsx");
          }
        </script>
      </div>
    @if ( Auth::user()->hasRole('admin') && ($contract->status == 1 || $contract->status == 3 || $contract->status == 4) )
        <!-- CONTRACT CANCEL Modal -->
        <div class="modal fade" id="cancelContractModal" tabindex="-1" aria-labelledby="cancelContractModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="cancelContractForm">

                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cancelContractModalLabel">{{__('panel/contract.cancel_contract_title')}}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>{{__('panel/contract.password_for_contract_cancel')}}</label>
                                <input required type="text" id="cancelContractPassword" autocomplete="false" v-model="cancel_contract_password" class="form-control modified">
                                <div class="invalid-feedback">
                                    Неправильный пароль
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button"  class="btn btn-secondary" data-dismiss="modal">{{__('app.btn_close')}}</button>
                            <button type="submit"  class="btn btn-outline-danger">{{__('panel/contract.cancel_contract')}}
                                <div class="spinner-border spinner ml-2" role="status">
                                    <span class="sr-only">Загрузка...</span>
                                </div>
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
        <!-- // CONTRACT CANCEL Modal -->

        <script>
            $(document).ready(function () {
                $('#cancelContractPassword').on('keyup', function (e) {
                    if ($(this).hasClass('is-invalid')) $(this).removeClass('is-invalid')
                })
                $('#cancelContractForm').on('submit', function (e) {
                    e.preventDefault()
                    cancelContract()
                })
            })
            async function cancelContract(){
                    const btn = $('#cancelContractForm button[type="submit"]')
                    btn.addClass('processing')
                    const formData = new FormData()
                    const cancel_password = $('#cancelContractPassword')
                    formData.append('api_token', window.globalApiToken)
                    formData.append('contract_id', '{{ $contract->id }}')
                    formData.append('password', cancel_password.val())
                    try {
                        const {data: resp} = await axios.post("{{ localeRoute('panel.cancelContract') }}", formData,  {headers: {'Content-Language':window.Laravel.locale} })
                        console.log(resp, 'fdsfds')
                        if (resp.status === 'success') {
                            $('#cancelContractModal').modal('hide')
                            window.location.reload()
                            polipop.add({content: "{{__('panel/contract.contract_successfully_cancelled')}}", title: `Успешно`, type: 'success'});
                        }
                    } catch (err) {
                        console.error(err)
                        if (err?.response?.data?.errors?.password?.length) {
                            cancel_password.next('.invalid-feedback').html(err.response.data.errors.password[0])
                            cancel_password.addClass('is-invalid')
                        }

                    } finally {
                        btn.removeClass('processing')
                    }
                }
        </script>
    @endif
@endsection
