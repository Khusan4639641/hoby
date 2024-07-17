@extends('templates.panel.app')

@section('title', $buyer->fio)
@section('class', 'buyers show')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.buyers.index')}}"><img
            src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('content')
<style>
    .buyers.show .center-body .main-info .settings .part {
        display: inline-flex;
        flex-direction: column;
        justify-content: center;
    }
    .buyers.show .center-body .main-info .settings .part > .part:first-child {
        padding-bottom: .5rem;
    }
    .buyers.show .center-body .main-info .settings .part > .part:last-child {
        padding-top: calc(.5rem - 1px);
        border-top: 1px solid var(--orange);
    }
</style>
    @php
        $buyerPersonals = $buyer->personals ?? new \App\Models\BuyerPersonal();
    @endphp
    {{--@if(! $buyer->phonesEquals)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{__('panel/buyer.error_phone_not_equals')}}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif --}}
    <div id="status">
        <template v-for="message in messages">
            <div :class="'alert alert-' + message.type + ' alert-dismissible fade show'">
                @{{ message.text }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </template>
        <div class="alert alert-danger alert-dismissible fade show" v-for="item in errors.system">
            @{{ item }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="top">
            <div>
                <label>{{__('panel/buyer.status')}}</label>
                <div class="d-flex">
                    <span :class="'mr-2 status status-' + status + ' btn'">
                        @{{status_caption}}
                    </span>
                    @if($buyer->black_list == 1)
                        <span class="status status-13 btn">
                            {{__('user.status_13')}}
                        </span>
                    @endif
                </div>
            </div>
            <div class="buttons">
                {{-- <a class="btn btn-link edit" href="{{localeRoute('panel.buyers.edit', $buyer)}}">{{__('panel/buyer.btn_edit_data')}}</a>--}}
                <button v-if="kyc_status == 0 && status != 8" type="button" @click="kycModerate()"
                        class="btn btn-outline-primary">{{__('panel/buyer.btn_kyc_moderate')}}</button>

                <button v-if="status != 4 && kyc_status!=0 && status != 8" data-toggle="modal" data-target="#dontVerifyModal"
                        type="button" class="btn btn-outline-danger">{{__('panel/buyer.btn_dont_verify')}}</button>

                {{--<button v-if="status != 8 && status == 4" type="button" @click="changeStatus('8', '{{__('user.status_8')}}')" class="btn btn-outline-danger">{{__('app.btn_block')}}</button>--}}
            </div><!-- /.buttons -->

            <!-- Modal -->
            <div class="modal fade" id="dontVerifyModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{__('panel/buyer.txt_reason')}}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            {{--<div v-if="status == 2"> --}}
                            <label>{{__('panel/buyer.verify_message')}}</label>
                            <select class="form-control" v-model="verify_message">
                                <option disabled value="">{{__('app.btn_choose')}}</option>
                                 @foreach($reasons as $reason)
                                    <option value="{{$reason}}">{{$reason}}</option>
                                 @endforeach
                            </select>
                            {{--</div> --}}
                            <br>
                            <div v-if="message" v-for="item in message" :class="'alert alert-' + item.type">
                                @{{ item.text }}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                {{__('app.btn_close')}}
                            </button>
                            <button {{--v-if="status == 2"--}} :disabled="verify_message === ''" @click="dontVerify()" class="btn btn-outline-danger">
                                {{__('panel/buyer.btn_dont_verify')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.top -->

        <h3 v-if="showDebtInfo" class="alert alert-warning d-flex justify-content-between mt-3" style="font-size: 16px">@{{ debtInfo }} <span @click="showDebtInfo = !showDebtInfo">&times;</span></h3>

        <div class="main-info">
            @if($buyer->user->avatar)
                <div class="preview" style="background-image: url('/storage/{{$buyer->user->avatar->path}}');"></div>
            @else
                <div class="preview dummy"></div>
            @endif
            <div class="">
                <div class="id">ID {{$buyer->id}}</div>
                <div class="name">
                    {{ $buyer->fio }}
                    {{ $buyer->birth_date ? date("d.m.Y", strtotime($buyer->birth_date)) : null }}
                </div>
                <div class="phone">{{$buyer->phone}}</div>

                <div class="settings " v-if="status != 3">
                    <div class="part rating">
                        <div class="part rating">
                            <span class="label">{{__('panel/buyer.limit_')}}</span>
                            <span class="value" id="buyer_balance">
                                @if ($buyer && $buyer->settings && $buyer->settings->limit)
                                    {{ number_format($buyer->settings->limit, 2, '.', ' ') . ' сум' }}
                                @else
                                    0
                                @endif
                            </span>
                        </div>
                        <div class="part rating border-left-0 pl-0">
                            <span class="label">{{__('panel/buyer.mini_limit')}}</span>
                            <span class="value">
                                @if ($buyer && $buyer->settings && $buyer->settings->mini_limit)
                                    {{ number_format($buyer->settings->mini_limit, 2, '.', ' ') . ' сум' }}
                                @else
                                    0
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="part rating">
                        <div class="part rating">
                            <span class="label">{{__('panel/buyer.current_limit')}}</span>
                            <span class="value" id="buyer_balance">
                                @if ($buyer && $buyer->settings && $buyer->settings->balance)
                                    {{ number_format($buyer->settings->balance, 2, '.', ' ') . ' сум' }}
                                @else
                                    0
                                @endif
                            </span>
                        </div>
                        <div class="part rating border-left-0 pl-0">
                            <span class="label">{{__('panel/buyer.current_mini_limit')}}</span>
                            <span class="value">
                                @if ($buyer && $buyer->settings && $buyer->settings->mini_balance)
                                    {{ number_format($buyer->settings->mini_balance, 2, '.', ' ') . ' сум' }}
                                @else
                                    0
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="part zcoin">
                        <span class="label">{{__('panel/buyer.zcoin')}}</span>
                        <span class="value">
                            @if ($buyer && $buyer->settings && $buyer->settings->zcoin)
                                {{ number_format($buyer->settings->zcoin, 2, '.', ' ') }}
                            @else
                                0
                            @endif
                        </span>
                    </div>
                    {{--
                    <div class="part installment_period">
                        <span class="label">{{__('panel/buyer.installment_period')}}</span>
                        <span class="value">{{ @$buyer->settings->period}}</span>
                    </div> --}}
                    <div class="part limit">
                        <span class="label">{{__('panel/buyer.personal_account')}}</span>
                        <span class="value" id="buyer_limit">
                            @if ($buyer && $buyer->settings && $buyer->settings->personal_account)
                                {{ number_format($buyer->settings->personal_account, 2, '.', ' ') }}
                            @else
                                0
                            @endif
                        </span>
                    </div>
                    <div class="part total_debt">
                        <span class="label">{{__('panel/buyer.total_debt')}}</span>
                        <span class="value">
                            @if ( $buyer && $buyer->totalDebt )
                                {{ number_format($buyer->totalDebt, 2, '.', ' ') . ' сум' }}
                            @else
                                0
                            @endif
                        </span>
                    </div>
                </div><!-- /.settings -->
                <div class="alert alert-danger" v-if="status==3">
                    <p>
                        <b>@{{ verified_by }}</b>:<br>
                        @{{ verify_message }}
                    </p>
                </div>
            </div>
        </div>
    </div>


    <ul class="nav nav-tabs mt-3" id="buyerTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-page="scoring" data-toggle="tab" href="#scoringTab" role="tab"
               aria-selected="true">{{__('panel/buyer.tab_scoring')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-page="detail_info" data-toggle="tab" href="#detail_infoTab" role="tab"
               aria-selected="false">{{__('panel/buyer.detail_info')}}</a>
        </li>

        {{--<li class="nav-item" role="presentation">
            <a class="nav-link" data-page="orders" data-toggle="tab" href="#ordersTab" role="tab" aria-selected="false">{{__('panel/buyer.tab_orders')}}</a>
        </li>--}}
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-page="contracts" data-toggle="tab" href="#contractsTab" role="tab"
               aria-selected="false">{{__('panel/buyer.tab_contracts')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-page="payments" data-toggle="tab" href="#paymentsTab" role="tab"
               aria-selected="false">{{__('panel/buyer.tab_payments')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-page="records" data-toggle="tab" href="#recordsTab" role="tab"
               aria-selected="false">{{__('panel/buyer.tab_records')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-page="messages" data-toggle="tab" href="#messagesTab" role="tab"
               aria-selected="false">{{__('panel/buyer.tab_messages')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-page="kyc_historyTab" data-toggle="tab" href="#kyc_historyTab" role="tab"
               aria-selected="false">{{__('panel/buyer.tab_kyc_history')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-page="cards" data-toggle="tab" href="#cardsTab" role="tab"
               aria-selected="true">{{__('panel/buyer.tab_cards')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-page="cards_pnfl" data-toggle="tab" href="#cards_pnflTab" role="tab"
               aria-selected="true">{{__('panel/buyer.tab_cards_uzcard')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-page="change_phone" data-toggle="tab" href="#change_phone_tab" role="tab"
               aria-selected="true">{{__('panel/buyer.change_phone')}}</a>
        </li>
    </ul>

    <div class="tab-content" id="buyerTabsContent">
        <div class="tab-pane fade show active scoring" id="scoringTab" role="tabpanel" aria-labelledby="scoring-tab">
            @include('panel.buyer.parts.scoring')
        </div>

        <div class="tab-pane detail-info" id="detail_infoTab" role="tabpanel" aria-labelledby="detail_info-tab">
            @include('panel.buyer.parts.detail')
        </div>

        {{--<div class="tab-pane" id="ordersTab" role="tabpanel" aria-labelledby="orders-tab">
            @include('panel.buyer.parts.orders')
        </div>--}}
        <div class="tab-pane" id="contractsTab" role="tabpanel" aria-labelledby="contracts-tab">
            @include('panel.buyer.parts.contracts')
        </div>

        <div class="tab-pane" id="paymentsTab" role="tabpanel" aria-labelledby="payments-tab">
            @include('panel.buyer.parts.payments')
        </div>
        <div class="tab-pane" id="recordsTab" role="tabpanel" aria-labelledby="records-tab">
            @include('panel.buyer.parts.records')
        </div>
        <div class="tab-pane" id="messagesTab" role="tabpanel" aria-labelledby="messages-tab">
            @include('panel.buyer.parts.message')
        </div>
        <div class="tab-pane" id="kyc_historyTab" role="tabpanel" aria-labelledby="kyc_history-tab">
            @include('panel.buyer.parts.kyc_history')
        </div>
        <div class="tab-pane" id="cardsTab" role="tabpanel" aria-labelledby="cards-tab">
            @include('panel.buyer.parts.cards')
        </div>
        <div class="tab-pane" id="cards_pnflTab" role="tabpanel" aria-labelledby="cards_pnfl-tab">
            @include('panel.buyer.parts.cards_pnfl')
        </div>
        <div class="tab-pane" id="change_phone_tab" role="tabpanel" aria-labelledby="change_phone-tab">
            @include('panel.buyer.parts.change_phone')
        </div>
    </div>

    <script>
    let time = null;
    var status = new Vue({
        el: '#status',
        data: {
            apiToken: Cookies.get('api_token'),
            buyerId: {{$buyer->id}},
            debtInfo: [],
            showDebtInfo: false,
            message: [],
            errors: [],
            messages: [],
            status: {{$buyer->status}},
            status_caption: '{{$buyer->status_caption}}',
            loading: false,
            pinfl: '{{$buyerPersonals->pinfl}}',
            verify_message: '{{$buyer->verify_message??null}}',
            verified_by: '{{$verified_by??null}}',
            kyc_status: {{$buyer->kyc_status}},
        },
        methods: {
            async getDebtInfo() {
                try {
                    const { data } = await axios.get('/api/v1/employee/buyers/show_overdue_contracts?buyer_id', {
                        params: {
                            buyer_id: this.buyerId,
                        },
                        headers: {
                            Authorization: `Bearer ${Cookies.get('api_token')}`,
                            'Content-Language': '{{app()->getLocale()}}',
                        }
                    })
                    if (data.status === 'success' && data.message.length) {
                        this.showDebtInfo = true
                        this.debtInfo = data.message
                        return
                    }
                }catch(err) {
                    console.log(err);
                }
            },
            dontVerify() {
                if (this.verify_message != null) {
                    axios.post('/api/v1/employee/buyers/action/status', {
                            api_token: this.apiToken,
                            status: 3,
                            verify_message: this.verify_message,
                            id: {{$buyer->id}},
                            verified_by: {{Auth::user()->id}}
                        },

                        { headers: { 'Content-Language': '{{app()->getLocale()}}' } }).then(response => {
                        if (response.data.status === 'success') {
                            this.status = 4;
                            this.status_caption = '{{__('user.status_3')}}';

                            location.reload();
                        } else {
                            this.message = response.data.response.message;
                        }
                    });
                }
            },
            changeStatus(status = null, caption = null) {
                if (status != null) {
                    this.loading = true;
                    axios.post('/api/v1/employee/buyers/action/status', {
                            api_token: Cookies.get('api_token'),
                            status: status,
                            verify_message: this.verify_message,
                            id: {{$buyer->id}}
                        },
                        { headers: { 'Content-Language': '{{app()->getLocale()}}' } }).then(response => {
                        if (response.data.status === 'success') {
                            this.status = status;
                            this.status_caption = caption;
                            this.message = response.data.response.message;
                        }
                        this.loading = false;
                    });
                }
            },
            checkPinfl: function (e) {
                this.messages = [];
                this.errors = [];

                if (this.pinfl != '') {
                    axios.post('/api/v1/employee/buyers/check-pinfl',
                        {
                            api_token: Cookies.get('api_token'),
                            buyer_id: {{$buyer->id}},
                            pinfl: this.pinfl,
                        }, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                        .then(response => {
                            response.data.response.message.forEach(element => this.messages.push(element));
                        })
                        .catch(e => {
                            this.errors.push(e);
                        });
                }
            },
            kycModerate: function (e) {
                this.messages = [];
                this.errors = [];
                var buyer_id = '{{$buyer->id}}';
                console.log('kyc moderate' + ' buyer_id: ' + buyer_id);
                if (buyer_id > 0) {
                    axios.post('/api/v1/employee/buyers/kyc-moderate',
                        {
                            api_token: Cookies.get('api_token'),
                            buyer_id: buyer_id,
                        }, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                        .then(response => {
                            if (response.data.status == 'success') {
                                this.kyc_status = response.data.data.kyc_status;
                                location.reload();
                            } else {
                                response.data.response.message.forEach(element => this.messages.push(element));
                            }
                            console.log(response.data);
                        })
                        .catch(e => {
                            this.errors.push(e);
                        });
                }
            },
            manuallyClickModerate() {
                document.addEventListener('keypress', (event) => {
                    if (event.keyCode === 119) {
                        this.kycModerate();
                    }
                });
            },
        },
        created: function () {
            this.getDebtInfo();
            this.checkPinfl();
            console.log('kyc_status: ' + this.kyc_status);
        },
        mounted() {
            this.manuallyClickModerate();
        },
    });


    $('#buyerTabs a').click(function (e) {

        e.preventDefault();
        $(this).tab('show');
    });

    // store the currently selected tab in the hash value
    $('#buyerTabs > li > a').on('shown.bs.tab', function (e) {
        var id = $(e.target).attr('href').substr(1);
        if (history.pushState) {
            history.pushState(null, null, '#' + id);
        } else {
            location.hash = '#' + id;
        }
    });

    // on load of the page: switch to the currently selected tab
    var hash = window.location.hash;
    $('#buyerTabs a[href="' + hash + '"]').tab('show');

    form_data = new FormData();
    $('#input__file').on('change', function (event) {
        var image = document.getElementById('image');
        image.src = URL.createObjectURL(event.target.files[0]);
        form_data.append('image', $('#input__file')[0].files[0]);
        // console.log($('#button_tag_id').classList)
        // $('#button_tag_id').classList.remove('')
        // $('#button_tag_id').classList.add('')
        $('#button_tag_id').removeClass('d-none');
        $('#button_tag_id').addClass('d-flex');


    });

    function clickButton() {
        $('#input__file').click();
    }

    function sendData() {
        form_data.append('user_id', $('#user_id').val());
        form_data.append('definition', $('#definition_id').val());
        form_data.append('phone', $('#input_phone_id').val());
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        });
        clearTimeout(time);
        time = setTimeout(function () {
            $.ajax({
                type: 'POST',
                url: '/{{app()->getLocale()}}/panel/change/phone-number',
                data: form_data,
                contentType: false,
                processData: false,
                success: function (obj) {
                    if (obj.status == 200 && obj.message == 'success') {
                        window.location.href = "{{url()->current()}}#kyc_historyTab";

                        window.location.reload();
                    } else {
                        var html = '<div class="alert alert-danger alert-dismissible w-100" role="alert">' +
                            '   <strong>' + obj.message + '</strong>' +
                            '       <button class="close" type="button" data-dismiss="alert" aria-label="Close">' +
                            '           <span aria-hidden="true">×</span>' +
                            '       </button>';
                        '</div>';

                        $('#alert_tag_id').html(html);
                    }

                },
            });
        }, 500);
    }

    $('#phone')

        .keydown(function (e) {

            var key = e.which || e.charCode || e.keyCode || 0;
            $phone = $(this);


            // Don't let them remove the starting '('
            if ($phone.val().length === 1 && (key === 8 || key === 46)) {
                $phone.val('+');
                return false;
            }
            // Reset if they highlight and type over first char.
            else if ($phone.val().charAt(0) !== '+') {
                $phone.val('+');
            }


            // Auto-format- do not expose the mask as the user begins to type
            if (key !== 8 && key !== 9) {
                if ($phone.val().length === 4) {
                    $phone.val($phone.val() + '-');
                }
                if ($phone.val().length === 7) {
                    $phone.val($phone.val() + '-');
                }
                if ($phone.val().length === 11) {
                    $phone.val($phone.val() + '-');
                }
                if ($phone.val().length === 14) {
                    $phone.val($phone.val() + '-');
                }
            }


            // Allow numeric (and tab, backspace, delete) keys only
            return (key == 8 ||
                key == 9 ||
                key == 46 ||
                (key >= 48 && key <= 57) ||
                (key >= 96 && key <= 105));
        })

        .keyup(function () {

            if ($(this).val().length === 17) {

                $('#input_phone_id').val($(this).val().replace(new RegExp('-', 'g'), ''));


            } else {
                $('#input_phone_id').val($(this).val().replace(new RegExp('-', 'g'), ''));

            }
        })

        .bind('focus click', function () {
            $phone = $(this);

            if ($phone.val().length === 0) {
                $phone.val('+998');
            } else {
                var val = $phone.val();
                $phone.val('').val(val); // Ensure cursor remains at the end
            }
        })

        .blur(function () {
            $phone = $(this);

            if ($phone.val() === '+998') {
                $phone.val('');
            }
            if ($phone.val().length <= 16) {
                $phone.val('');
                $('#input_phone_id').val('');
            }
        });

    </script>
@endsection
