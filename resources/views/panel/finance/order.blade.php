@extends('templates.panel.app')

@section('title', __('panel/finance.detail_header', ['number' => $order->id, 'date' => $order->created_at]))
@section('class', 'finances order')

@section('content')

    <div class="order-block">
        <div class="row stats">
            <div class="col item-wrapper">
                <div class="payed">
                    <div class="caption">{{__('panel/finance.paid')}}</div>
                    <div class="value"></div>
                </div>
            </div>
            <div class="col item-wrapper">
                <div class="partner-credit">
                    <div class="caption">{{__('panel/finance.partner_credit')}}</div>
                    <div class="value" id="orderCredit">{{$order->credit}}</div>
                </div>
            </div>
            <div class="col item-wrapper">
                <div class="partner-debt">
                    <div class="caption">{{__('panel/finance.partner_debt')}}</div>
                    <div class="value">{{$order->debit}}</div>
                </div>
            </div>
            <div class="col item-wrapper">
                <div class="commission-charged">
                    <div class="caption">{{__('panel/finance.commission_charged')}}</div>
                    <div class="value"></div>
                </div>
            </div>
            <div class="col item-wrapper">
                <div class="commission-paid">
                    <div class="caption">{{__('panel/finance.commission_paid')}}</div>
                    <div class="value"></div>
                </div>
            </div>
            <div class="col item-wrapper">
                <div class="total-sum">
                    <div class="caption">{{__('panel/finance.total_sum')}}</div>
                    <div class="value">{{$order->total}}</div>
                </div>
            </div>
        </div>
        <div class="add-receipt" id="receipt">
            <div class="lead">{{__('panel/finance.add_receipt')}}</div>
            <div v-if="messages.length">
                <div class="alert alert-success" v-for="message in messages">@{{ message }}</div>
            </div>
            <div class="alert alert-danger" v-for="item in errors.system">@{{ item }}</div>
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label>{{__('panel/finance.lbl_sum')}}</label>
                        <input v-model="amount" name="amount" type="text"
                               :class="'form-control' + (errors.amount?' is-invalid':'')">
                        <div class="error" v-for="item in errors.amount">@{{ item }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label>{{__('panel/finance.lbl_contractor')}}</label>
                        <select v-model="receipt_type" name="receipt_type"
                               :class="'form-control' + (errors.receipt_type?' is-invalid':'')">
                            <option value="from_insurance">{{__('panel/finance.from_insurance')}}</option>
                            <option value="to_insurance">{{__('panel/finance.to_insurance')}}</option>
                            <option value="to_supplier">{{__('panel/finance.to_supplier')}}</option>
                        </select>
                        <div class="error" v-for="item in errors.receipt_type">@{{ item }}</div>
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label>{{__('panel/finance.lbl_date')}}</label>
                        <date-picker v-model="date" value-type="format" type="date"
                                     format="DD.MM.YYYY"></date-picker>
                        <div class="error" v-for="item in errors.date">@{{ item }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button v-on:click="send()" type="button" class="btn btn-primary">{{__('app.btn_add')}}</button>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="receipt-list mt-4" id="receipt-list">
            <div class="lead">{{__('panel/finance.receipt_table', ['number' => $order->contract->id])}}</div>
            <table class="table">
                <thead>
                <tr>
                    <th>№</th>
                    <th>{{__('panel/finance.lbl_receipt_date')}}</th>
                    <th>{{__('panel/finance.lbl_receipt_transaction')}}</th>
                    <th>{{__('panel/finance.lbl_receipt_amount')}}</th>
                    <th>{{__('panel/finance.lbl_receipt_type')}}</th>
                </tr>
                </thead>
                <tbody v-for="(item, index) in list" :key="item.id">
                    <tr>
                        <td>@{{ index + 1 }}</td>
                        <td>@{{ item.created_at }}</td>
                        <td>@{{ item.id }}</td>
                        <td :class="'amount ' + (item.amount > 0 ? 'green' : 'red')">@{{ item.amount }}</td>
                        <td>@{{ item.receipt_type }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @if($order->contract)

        @include('panel.finance.parts.contract')
        @include('panel.finance.parts.schedule')

    @endif
    <script>
        var receipt = new Vue({
            el: '#receipt',
            data: {
                errors: {},
                messages: [],
                amount: '',
                receipt_type: 'from_insurance',
                date: '',
                order_id: '{{$order->id}}',
                contract_id: '{{$order->contract->id}}',
                api_token: '{{Auth::user()->api_token}}'
            },
            methods: {

                send: function () {

                    let amount, type;

                    switch (this.receipt_type) {
                        case "from_insurance":
                            amount = '';
                            type = "insurance";
                            break;
                        case "to_insurance":
                            amount = '-';
                            type = "insurance";
                            break;
                        case "to_supplier":
                            amount = '-';
                            type = "supplier";
                            break;
                    }

                    axios.post('/api/v1/finance/orders/add-receipt', {
                        api_token: this.api_token,
                        amount: amount + this.amount,
                        receipt_type: type,
                        action: this.receipt_type,
                        date: this.date,
                        order_id: this.order_id,
                        contract_id: this.contract_id,
                    },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            if (response.data.status === 'success') {
                                this.clearForm();
                                this.updateReceiptList();
                                if(response.data.data['credit'])
                                    $('#orderCredit').text(response.data.data['credit']);
                                this.errors = {};
                            } else {
                                this.errors = response.data.response.errors;
                            }
                        })
                        .catch(e => {
                            this.errors.system = [];
                            this.errors.system.push(e);
                        })

                },

                clearForm: function () {
                    this.amount = '';
                    this.receipt_type = 'from_insurance';
                    this.date = '';
                },

                updateReceiptList: function () {
                    receiptList.updateList();
                },
            }
        });

        var receiptList = new Vue({
            el: '#receipt-list',
            data: {
                list: null,
                order_id: '{{$order->id}}',
                contract_id: '{{$order->contract->id}}',
                api_token: '{{Auth::user()->api_token}}'
            },
            methods: {
                updateList() {
                    if (!this.loading) {
                        this.loading = true;
                        axios.post('/api/v1/finance/orders/list-receipt',
                            {
                                api_token: this.api_token,
                                order_id: this.order_id,
                            },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                        ).then(response => {
                            if (response.data.status === 'success') {
                                this.list = response.data.data;
                            }
                            this.loading = false;
                        })
                    }
                }
            },
            created: function () {
                this.updateList();
            }
        })
    </script>

@endsection
