@extends('templates.panel.app')

@section('title', __('panel/contract.contract_n') . $contract->id)
@section('title_date', $contract->created_at)
@section('class', 'contracts show')

@section('center-header-control')
    <span class="status-{{ $contract->status }}">{{ $contract->status_caption }}</span>
@endsection

@section('content')
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

                <div class="col-md-4">
                    <div class="lead">{{__('billing/order.title_upload_act')}}</div>
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
                                    @foreach($contract->acts as $index => $file)
                                        <a target="_blank"
                                           class="btn btn-outline-primary btn-block"
                                           href="{{ \App\Helpers\FileHelper::url($file->path) }}"
                                        >
                                            {{__('billing/contract.act_view')}} - {{ $index + 1 }}
                                        </a>
                                    @endforeach
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
                </div>
                {{-- </div><!-- /#imei --> --}}
                <div class="col-md-4">
                    <div class="lead">{{__('billing/order.title_upload_imei')}}</div>
                    <div v-if="category==1" class="imei" id="imei">
                        <div v-for="item in imei.message" :class="'alert alert-'+ item.type">
                            @{{item.text}}
                        </div>

                        <form v-if="imei.status != 1 && imei.status != 3" action="" @submit="uploadImei">
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
                                <div v-if="imei.path" class="col-12 mb-3">
                                    <a
                                        target="_blank"
                                        class="btn btn-outline-primary btn-block"
                                        :href="imei.path"
                                    >
                                        {{__('billing/contract.imei_view')}}
                                    </a>
                                </div>
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
                    </div><!-- /#imei -->
                </div>

                <!-- /#cancel_act -->
                <div class="col-md-4">
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
        </div><!-- /#cancel_act -->

        <script>

        @php
            $imeiPath = '';
            if ($contract->imei) {
                $imeiPath = \App\Helpers\FileHelper::url($contract->imei->path);

                /*if ($contract->doc_path == 1) {
                    $imeiPath = \App\Helpers\FileHelper::url($contract->imei->path);
                } else {
                    $imeiPath = 'https://cabinet.test.uz/storage/'. $contract->imei->path;
                }*/
            }

            $cancelActPath = '';
             if ($contract->cancelAct) {
                 $cancelActPath = \App\Helpers\FileHelper::url($contract->cancelAct->path);

                /*if ($contract->doc_path == 1) {
                    $cancelActPath = \App\Helpers\FileHelper::url($contract->cancelAct->path);
                } else {
                    $cancelActPath = 'https://cabinet.test.uz/storage/'. $contract->cancelAct->path;
                }*/
            }
        @endphp

        var act = new Vue({
            el: '#act',
            data: {
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

                category: '{{ $category }}',

                cancel_act: {
                    status: '{{$contract->cancel_act_status}}',
                    new2: null,
                    message: [],
                    cancel_reason: '{{$contract->cancel_reason}}',
                    path2: '{{ $cancelActPath }}',
                },

            },
            methods: {
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

        </script>
    </div>



    @php
        //<div class="buttons">
        //<a href="#" class="btn btn-outline-primary">{{__('panel/contract.btn_download_contract')}}</a>
        //<a href="#" class="btn btn-outline-primary">{{__('panel/contract.btn_invoice')}}</a>
        //<a href="#" class="btn btn-primary">{{__('panel/contract.btn_confirm')}}</a>
        //<a href="#" class="btn btn-danger">{{__('panel/contract.btn_decline')}}</a>
    //</div>
    @endphp

@endsection
