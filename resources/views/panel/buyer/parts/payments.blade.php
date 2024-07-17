<div class="dataTablesSearch" id="dataTablesSearch">
    <div class="row">
        <div class="col">
            <input name="contract" placeholder="Номер договора" type="text" class="form-control" >
        </div>
        <div class="col">
            <select id="type" name="type" class="form-control" >
                <option value="">Все </option>
                <option value="user">Пополнение</option>
                <option value="auto">Списание</option>
                <option value="refund">Возврат</option>
                <option value="user_auto">Досрочное погашение</option>
                <option value="reimbursable">Возмещение расходов</option>
                <option value="upay">Оплаты Upay сервисов</option>
            </select>
        </div>
        <div class="col">
            <select id="payment_system" name="payment_system" class="form-control" >
                <option value="">Все </option>
                <option value="DEPOSIT">DEPOSIT</option>
                <option value="BANK">BANK</option>
                <option value="Autopay">Autopay</option>
                <option value="MIB">MIB</option>
                <option value="UZCARD">UZCARD</option>
                <option value="HUMO">HUMO</option>
                <option value="PNFL">PNFL</option>
                <option value="OCLICK">CLICK</option>
                <option value="PAYME">PAYME</option>
                <option value="APELSIN">APELSIN</option>
                <option value="UPAY">UPAY</option>
                <option value="PAYNET">PAYNET</option>
                <option value="ACCOUNT">Лицевой счет</option>
                <option value="BONUS_ACCOUNT">Бонусный счет</option>
                <option value="Paycoin">Оплаты Upay сервисов</option>
            </select>
        </div>
        <div class="col">
            <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
        </div>
    </div>


</div>

<div class="payments list">
    <table class="table payment-list">
        <thead>
        <tr>
            <th>{{__('panel/payment.contract')}}</th>
            <th>{{__('panel/payment.month')}}</th>
            <th>{{__('panel/payment.user')}}</th>
            <th>{{__('panel/payment.amount')}}</th>
            <th>{{__('panel/payment.type')}}</th>
            <th>{{__('panel/payment.payment_system')}}</th>
            <th>{{__('panel/payment.status')}}</th>
            <th>{{__('panel/payment.created_at')}}</th>

            <th></th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table><!-- /.payments-list -->
</div>
<div class="modal" id="modalRefundConfirm" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <input type="hidden" id="paymentID">

            <div class="modal-header">
                <h5 class="modal-title">{{__('panel/payment.header_refund_confirm')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
<!--                <p>{{__('panel/employee.txt_delete_confirm')}}</p>-->
                <input type="text" id="password" name="password">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                <button type="submit" onclick="refund()" class="btn btn-primary" id="refundSubmit">{{__('app.btn_refund')}}</button>
            </div>

        </div>
    </div>
</div>



<div class="modal" id="modalBankAmountConfirm" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <input type="hidden" id="paymentID">

            <div class="modal-header">
                <h5 class="modal-title">{{__('panel/payment.header_bank_amount_confirm')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="text" id="password" name="password">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                <button type="submit" onclick="bank_add()" class="btn btn-primary">{{__('app.btn_add')}}</button>
            </div>

        </div>
    </div>
</div>


<hr>

<div>
    <div class="row">
        <div class="label">
            <p>{{__('panel/buyer.txt_amount')}}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-2">
            <select id="type_amout" name="type_amout" class="form-control">
                <option value="BANK">{{__('panel/buyer.txt_bank_amount')}}</option>
                <option value="MIB">{{__('panel/buyer.txt_mib_amount')}}</option>
                <option value="Autopay">{{__('panel/buyer.txt_autopay_amount')}}</option>
            </select>

        </div>

        <div class="col-2">
            <input type="text" id="bank_amount" name="bank_amount"> {{__('app.currency')}}
        </div>

        <div class="col-2">
            <button type="submit" onclick="confirmBankAmount()" class="btn btn-primary">{{__('app.btn_add')}}</button>
        </div>
    </div>

    <hr>

</div>

<div id="transaction">
    <div class="row">
        <div class="col-3 label">
            <p>{{__('panel/buyer.txt_check_transaction')}}</p>
        </div>
        <div class="col-4">
            <button
                v-on:click.once="checkTransaction"
                :disabled="disableCardCheckButton"
                class="btn btn-orange"
                :style="{ display: disableCardCheckButton ? 'none' : 'block' }"
            >
                {{__('app.btn_check')}}
            </button>

        </div>
    </div>

    <div class="row">

        <div class="transaction-results" v-if="showResults">

            <table class="table">
                <thead>
                <tr>
                    <th>№</th>
                    <th>{{__('panel/payment.transaction')}}</th>
                    <th>uuid</th>
                    <th>card_id</th>
                    <th>{{__('panel/payment.amount')}}</th>
                    <th>{{__('panel/payment.created_at')}}</th>
                    <th>{{__('panel/payment.card_number')}}</th>
                    <th>{{__('panel/payment.status')}}</th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <template v-for="(transaction, index) in transactions" :key="index">
                    <tr :style="{ backgroundColor: `${transaction.lost === 1 ? '#f3ccd5' : ''}` }">
                        <td class="">@{{ ++index }}</td>
                        <td >@{{ transaction.payment_id }}</td>
                        <td >@{{ transaction.uuid }}</td>
                        <td >@{{ transaction.card_id }}</td>
                        <td class="yes">@{{ numberFormat(transaction.amount/100) }}  {{__('app.currency')}}</td>
                        <td class="yes">@{{ transaction.created_at }}</td>
                        <td class="yes">@{{ transaction.card }}</td>
                        <td v-if="transaction.state === 4 ">
                            успешно
                        </td>
                        <td v-if="transaction.state === 21 " >
                            возврат
                        </td>
                        <td v-if="transaction.state === 50 "
                            :style="{ backgroundColor: `${transaction.state === 50 ? '#f3ccd5' : ''}` }"
                        >
                            не успешно
                        </td>

                        <td v-if="transaction.lost === 1"  >
                            <button type="submit" v-on:click.once="setTransaction(transaction.payment_id)" class="btn btn-primary">{{__('panel/buyer.btn_set')}}</button>

                        </td>
                        <td v-if="transaction.lost === 1" >
                            <button type="submit" v-on:click.once="addTransaction(transaction.payment_id)" class="btn btn-success">{{__('panel/buyer.btn_add')}}</button>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>


        {{-- <div class="col-2">
            <button type="submit" onclick="checkTransaction()" class="btn btn-primary">{{__('app.btn_add')}}</button>
        </div> --}}
    </div>

    <hr>

</div>

<div class="loading"><img src="{{asset('images/loader.gif')}}"></div>



<script>
    let paymentsTable;

    $(document).ready(function () {
        //Data tables init
        if($('.payments .payment-list').length > 0){

            paymentsTable = $('.payments .payment-list').dataTable( {
                serverSide: true,
                pageLength: 15,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.payments.list')}}', {
                        api_token: Cookies.get('api_token'),
                        search: paymentsTable.fnSettings().oPreviousSearch.sSearch,
                        user_id: {{$buyer->id}},
                        orderBy: 'created_at desc, id desc',
                        list_type: 'data_tables',
                        transaction_id: $('#dataTablesSearch input[name="transaction"]').val(),
                        contract_id: $('#dataTablesSearch input[name="contract"]').val(),
                        type: $('#dataTablesSearch select[name="type"]').val(),
                        payment_system: $('#dataTablesSearch select[name="payment_system"]').val(),
                        offset: paymentsTable.fnSettings()._iDisplayStart,
                        limit: paymentsTable.fnSettings()._iDisplayLength,
                    }, function(res) {
                        //res = JSON.parse(res);
                        callback({
                            recordsTotal: res.data.recordsTotal,
                            recordsFiltered: res.data.recordsTotal,
                            data: res.data.data
                        });
                        loading(false);
                    });
                },
                "language": {
                    "url": "/assets/json/ru.lang"
                },
                "ordering": false,
                initComplete: function ( settings, json) {

                }
            } );

            $('#dataTablesSearch button').click(function(){
                paymentsTable.DataTable().draw();
            })

        }
    });

    var transaction = new Vue({
        el: '#transaction',
        data: {
            locale: '{{ucfirst(app()->getLocale())}}',
            errors: [],
            messages: [],
            showResults: false,
            disableCardCheckButton: false,
            api_token: '{{Auth::user()->api_token}}',
            buyer_id: '{{$buyer->id}}',
            transactions: null,
            password: null,
        },

        methods: {
            checkTransaction: function () {
                loading(true);
                if(this.password === null){
                    const password = window.prompt('Вверите код');
                    this.password = password;
                }

                if(this.password) {
                    this.disableCardCheckButton = true;

                    axios.post('/api/v1/pay/check-cards-transactions' + '?api_token=' + Cookies.get('api_token'), {
                        buyer_id: {{$buyer->id}},
                        password:  this.password,

                    }).then(response => {
                        //console.log(response.data.data);
                        if (response.data.status === 'success') {
                            //alert(response.data.response.message[0].text);
                            this.transactions = response.data.data;
                            this.showResults = true;
                            paymentsTable.DataTable().ajax.reload();

                        }else{
                            alert(response.data.response.message[0].text);

                        }
                    })
                } else {
                    alert('Доступ запрещен. Неверный пароль!')
                }

            },
            // + пополнить ЛС
            addTransaction: function (transaction_id) {
                loading(true);
                axios.post('/api/v1/pay/set-cards-transactions' + '?api_token=' + Cookies.get('api_token'), {
                    buyer_id: {{$buyer->id}},
                    transaction_id: transaction_id,
                    password:  this.password,
                    type: 'user',

                }).then(response => {
                    if (response.data.status === 'success') {
                        this.transactions = response.data.data;
                        paymentsTable.DataTable().ajax.reload();
                        this.checkTransaction();

                    }else{
                        alert(response.data.response.message[0].text);
                    }

                })
            },

            // добавить транзакцию в бд
            setTransaction: function (transaction_id) {
                loading(true);
              axios.post('/api/v1/pay/set-cards-transactions' + '?api_token=' + Cookies.get('api_token'), {
                    buyer_id: {{$buyer->id}},
                    transaction_id: transaction_id,
                    password:  this.password,
                    type: 'auto',

                }).then(response => {
                    if (response.data.status === 'success') {
                        this.transactions = response.data.data;
                        this.checkTransaction();
                        paymentsTable.DataTable().ajax.reload();

                    }else{
                        alert(response.data.response.message[0].text);
                    }

                })
            },


            numberFormat: function (num) {
                return Intl.NumberFormat().format(num);
            },
        },
    });

    //Confirm news delete
    function confirmRefund(id) {
        $('#paymentID').val(id);
        $('#modalRefundConfirm #refundSubmit').removeAttr('disabled')
        $('#modalRefundConfirm').modal('show');
    }

    //Confirm news delete
    function confirmBankAmount() {
        amount = $('#bank_amount').val();
        if(amount.length==0){
            $('#bank_amount').focus();
            return false;
        }
        $('#modalBankAmountConfirm').modal('show');
    }

    // refund
    function refund(){
        loading(true);
        $('#modalRefundConfirm #refundSubmit').attr('disabled', 'disabled')

        axios.post('/api/v1/pay/refund' + '?api_token=' + Cookies.get('api_token'), {
            payment_id: $('#modalRefundConfirm #paymentID').val(),
            password: $('#modalRefundConfirm #password').val(),

        }).then(response => {
                if (response.data.status === 'success') {
                    alert(response.data.response.message[0].text);
                    paymentsTable.DataTable().ajax.reload();
                }else{
                    alert(response.data.response.message[0].text);
                    paymentsTable.DataTable().ajax.reload();
                }
               $('#modalRefundConfirm').modal('hide');
            $('#modalRefundConfirm #refundSubmit').removeAttr('disabled')
            })
    }



    // bank_add
    function bank_add(){
        loading(true);

       axios.post('/api/v1/pay/bank-amount-add' + '?api_token=' + Cookies.get('api_token'), {
            amount: $('#bank_amount').val(),
            type: $('#type_amout').val(),
            password: $('#modalBankAmountConfirm #password').val(),
            buyer_id: {{$buyer->id}},
        }).then(response => {
            if (response.data.status === 'success') {
                alert(response.data.response.message[0].text);

            }else{
                alert(response.data.response.message[0].text);

            }
            window.location.reload();

        })
    }



    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

</script>
