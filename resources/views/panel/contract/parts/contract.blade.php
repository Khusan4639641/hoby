<style>
    .status {
        padding: 5px;
        border: 1px solid #ccc;
        text-align: center;
        border-radius: 8px;
        color: #ccc;
    }

    .activated {
        border-color: var(--green);
        color: var(--green);
    }

    .cancelled {
        border-color: red;
        color: #ff0000;
    }
</style>

<div class="partner-buyer">
    <div class="partner">
        <div class="title mt-3">{{__('panel/contract.partner')}}</div>
        {{--        <div class="photo">--}}
        {{--            @if($contract->partner->avatar)--}}
        {{--                <div class="preview"--}}
        {{--                     style="background-image: url('/storage/{{$contract->partner->avatar->path}}')"></div>--}}
        {{--            @else--}}
        {{--                <div class="preview dummy"></div>--}}
        {{--            @endif--}}
        {{--        </div>--}}
        <div class="caption">
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.partner')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{@$contract->partner->fio}} </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.company')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{@$contract->company->name}} </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.address')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{@$contract->company->address}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.legal_address')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{@$contract->company->legal_address}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.inn')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{@$contract->company->inn}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.payment_account')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{@$contract->company->payment_account}}</div>
                </div>
            </div>

            @if($contract->partner)
                <a class="more"
                   href="{{localeRoute('panel.partners.show', $contract->company)}}">{{__('app.btn_more')}}</a>
            @endif

        </div>
    </div><!-- /.partner -->

    <div class="buyer">
        <div class="title mt-3">{{__('panel/contract.buyer')}}</div>
        {{--        <div class="photo">--}}
        {{--            @if($contract->buyer->avatar)--}}
        {{--                <div class="preview" style="background-image: url('/storage/{{$contract->buyer->avatar->path}}')"></div>--}}
        {{--            @else--}}
        {{--                <div class="preview dummy"></div>--}}
        {{--            @endif--}}
        {{--        </div>--}}
        <div class="caption">
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.buyer')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->buyer->fio}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.address')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{@$contract->buyer->addressRegistration->country}}
                        , {{@$contract->buyer->addressRegistration->city}}
                        , {{@$contract->buyer->addressRegistration->address}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        ID
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->buyer->id}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.phone')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->buyer->phone}}</div>
                </div>
            </div>


            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.installment_period')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->period}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.installment_amount')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{number_format($contract->total, 2, '.', ' ')}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.total_debt')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">
                        @if( in_array($contract->status, $contract->ACTIVE_STATUSES, true) )
                            {{ number_format($contract->debt_sum, 2, '.', ' ') }}
                        @else
                            0.00
                        @endif
                    </div>
                </div>
            </div>
            <a href="{{ isset($contract->buyer->personals->latest_id_card_or_passport_photo->path) ? \App\Helpers\FileHelper::sourcePath() . $contract->buyer->personals->latest_id_card_or_passport_photo->path : ''}}">{{__('panel/contract.selfie_with_document')}}</a>
            <br>
            <a class="more" href="{{localeRoute('panel.buyers.show', $contract->buyer)}}">{{__('app.btn_more')}}</a>
        </div>
    </div><!-- /.buyer -->
</div>

<div class="order-data">
    <table class="table-order">
        <thead>
        <tr>
            <th>№</th>
            <th>{{__('billing/order.lbl_product_category')}}</th>
            <th>{{__('panel/contract.product_name')}}</th>
            <th>{{__('panel/contract.product_qty')}}</th>
            <th>{{__('panel/contract.product_price')}}</th>
            {{--            <th>{{__('panel/contract.product_nds')}}</th>--}}
            <th>{{__('panel/contract.product_nds_sum')}}</th>
            <th>{{__('panel/contract.product_sum')}}</th>
            <th>{{__('panel/contract.product_nds_total')}}</th>
            <th>{{__('panel/contract.deposit')}}</th>
            <th>{{__('panel/contract.status')}}</th>
        </tr>
        </thead>
        <tbody>
        @isset($contract->order->products)
            @foreach($contract->order->products as $product)
                <tr>
                    <td>{{$product->id}}</td>
                    <td>{{$product->category }}</td>
                    <td>{{$product->original_name ?: $product->name}}</td>
                    <td>{{$product->amount}}</td>
                    <td>{{number_format($product->source_price,2,'.',' ')}}</td>
                    <td>{{number_format($product->total_nds_sum,2,'.',' ')}}</td>
                    <td>{{number_format($product->source_total_sum,2,'.',' ')}}</td>
                    <td>{{number_format($product->total_sum,2,'.',' ')}}</td>
                    <td> {{ number_format($contract->deposit, 2, '.', ' ') }} </td>
                    <td>
                        @if($product->status == 1 )
                            <div class="status activated">
                                {{ 'Активен' }}
                            </div>
                        @else
                            <div class="status cancelled">
                                {{ 'Отменен' }}
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="8">
                    {{__('billing/catalog.txt_empty_list')}}
                </td>
            </tr>
        @endisset
        </tbody>
    </table>
    @if($contract->order->shipping_code)
        <table class="order-info">
            <tr>
                <td class="label">{{__('panel/contract.order_delivery_method')}}</td>
                <td>{{__('shipping/'.strtolower($contract->order->shipping_code).'.name')}}</td>
            </tr>
            <tr>
                <td class="label">{{__('panel/contract.order_delivery_address')}}</td>
                <td>{{$contract->order->shipping_address}}</td>
            </tr>
        </table>
    @endif


    <div class="order-total mb-2">
        <div class="installment">
            <span>{{(__('panel/contract.installment_conditions'))}}</span>
            <span>{!! __('panel/contract.installment_conditions_text', ['total' => number_format($contract->total,2,'.',' '), 'period' => $contract->period])!!}</span>
        </div>
        <div class="total">
            <span>{{__('panel/contract.order_total')}}</span>
            <span>{{number_format($contract->total,2,'.',' ')}}</span>
        </div>
    </div>
</div>

<hr>

<div class="container row justify-content-between p-0 m-0 mt-2 mb-2">
    <button class="col-3 btn btn-orange text-left btn-block text-center dropdown-toggle m-0" type="button"
            data-toggle="collapse" data-target="#collapseExample"
            aria-expanded="false" aria-controls="collapseExample">
        {{__('cabinet/order.header_payments_schedule')}}
    </button>

    @if(Auth::user()->hasRole('admin') && $contract->status === \App\Models\Contract::STATUS_ACTIVE)
        <button
            class="col-3 btn btn-danger"
            type="button"
            data-toggle="modal"
            data-target="#partialCancellationModal"
        >
            Отменить товары
        </button>
    @endif

    @if(isset($collcost) || isset($autopayDebitHistory))
        <div class="col-8 p-0 m-0">
            <table class="table-second">
                <thead>
                <tr>
                    @if(isset($collcost))
                        <th>1% от всей оставшейся задолженности по контракту</th>
                        <th>Фиксированная сумма по взысканию</th>
                        <th>Остаток оплаты за взыскание</th>
                    @endif
                    @if(isset($autopayDebitHistory))
                        <th>Фиксированная сумма за Autopay (3%)</th>
                        <th>Остаток оплаты за Autopay</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                <tr>
                    @if(isset($collcost))
                        <td>{{$collcost->persent}}</td>
                        <td>{{$collcost->fix}}</td>
                        <td>{{$collcost->balance ?? ''}}</td>
                    @endif
                    @if(isset($autopayDebitHistory))
                        <td>{{$autopayDebitHistory->percent ?? ''}}</td>
                        <td>{{$autopayDebitHistory->balance ?? ''}}</td>
                    @endif
                </tr>
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="collapse mt-3" id="collapseExample">
    <table class="table-schedule">
        <thead>
        <tr>
            <th>{{__('panel/contract.number')}}</th>
            <th>{{__('panel/contract.date')}}</th>
            <th>{{__('panel/contract.pay')}}</th>
            <th>{{__('panel/contract.balance')}}</th>
            <th>{{__('panel/contract.status')}}</th>
        </tr>
        </thead>
        <tbody>
        @isset($contract->schedule)
            @php
                $i = 1;
            @endphp
            @foreach($contract->schedulesOrderedByPaymentDateAndId as $schedule)
                <tr @if($schedule->status == 2) class="expired" @endif>
                    <td>{{$i++}}</td>
                    <td>{{$schedule->date}}</td>
                    <td class="pay">{{number_format($schedule->total,2,'.',' ')}}</td>
                    <td>{{number_format($schedule->balance,2,'.',' ')}}</td>
                    <td>
                        @switch($schedule->status)
                            @case(0)
                            -
                            @break
                            @case(1)
                            <div class="paid">{{__('panel/contract.paid')}} {{$schedule->status_date}}</div>
                            @break
                            @case(2)
                            {{__('panel/contract.expired')}}
                            @break
                        @endswitch
                    </td>
                </tr>
            @endforeach
        @endisset
        </tbody>
    </table>
</div>

<!-- Partial Cancellation Modal -->
<div class="modal fade" id="partialCancellationModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Частичная отмена договора</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <table class="table table-striped table-borderless">
                    <thead>
                    <tr>
                        <th>№</th>
                        <th>{{__('billing/order.lbl_product_category')}}</th>
                        <th>{{__('panel/contract.product_name')}}</th>
                        <th>{{__('panel/contract.product_qty')}}</th>
                        <th>{{__('panel/contract.product_price')}}</th>
                        <th>{{__('panel/contract.product_sum')}}</th>
                        <th>{{__('panel/contract.product_nds_total')}}</th>
                        <th>{{ __('app.txt_select') }}</th>
                        <th>Кол-во товаров на отмену</th>
                    </tr>
                    </thead>
                    <tbody>
                    @isset($contract->order->products)
                        @foreach($contract->order->products as $product)
                            @if($product->status === 1 && $product->amount > 0)
                                <tr>
                                    <td>{{$product->id}}</td>
                                    <td>{{$product->category }}</td>
                                    <td>{{$product->original_name ?: $product->name}}</td>
                                    <td>{{$product->amount}}</td>
                                    <td>{{number_format($product->source_price,2,'.',' ')}}</td>
                                    <td>{{number_format($product->source_total_sum,2,'.',' ')}}</td>
                                    <td>{{number_format($product->total_sum,2,'.',' ')}}</td>
                                    <td>
                                        <input
                                            type="checkbox"
                                            value="{{ $product->id }}"
                                            style="width: 18px; height: 18px"
                                            @change="onSelectProduct($event, {{ $product->amount }})"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            class="form-control"
                                            type="number"
                                            max="{{ $product->amount }}"
                                            min="1"
                                            value="{{ $product->amount }}"
                                            data-product-id="{{ $product->id }}"
                                            disabled
                                            onkeydown="return false"
                                            @input="onTypeAmount($event, {{ $product->id }})"
                                        />
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9">
                                {{__('billing/catalog.txt_empty_list')}}
                            </td>
                        </tr>
                    @endisset
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary close-button"
                    data-dismiss="modal"
                >
                    {{__('app.btn_cancel')}}
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="isLoading || !hasProductsForCancel"
                    @click.prevent="onPartlyCancel"
                >
                    {{__('app.btn_save')}}
                </button>
            </div>
        </div>

        <div :class="{ loading: true, active: isLoading }"><img src="{{asset('images/media/loader.svg')}}"></div>
    </div>
</div>
<!-- Partial Cancellation Modal -->

<!-- Partial Cancellation JavaScript -->
<script>
const $modal = $('#partialCancellationModal');

new Vue({
    el: '#partialCancellationModal',
    data: () => ({
        api_token: Cookies.get('api_token'),
        isLoading: false,
        selectedProducts: [],
        contract_id: '{{ $contract->id }}',
    }),
    computed: {
        hasProductsForCancel() {
            return this.selectedProducts.length;
        },
    },
    methods: {
        onSelectProduct(e, amount) {
            const isChecked = e.target.checked;
            const id = e.target.value;
            const $amountInput = $(`input[data-product-id=${id}]`);

            if (isChecked) {
                $amountInput.removeAttr('disabled');
                this.selectedProducts.push({
                    id: Number(id),
                    amount: $amountInput.val(),
                });
            } else {
                $amountInput.attr('disabled', 'true');
                this.removeProduct(id);
            }
        },
        removeProduct(id) {
            this.selectedProducts = this.selectedProducts.filter((product) => product.id !== Number(id));
        },
        onTypeAmount(e, id) {
            if (!this.selectedProducts.length) return;

            const amount = e.target.value;

            this.selectedProducts = this.selectedProducts.map((product) => {
                if (Number(product.id) === id) {
                    return {
                        ...product,
                        amount: Number(amount),
                    };
                }

                return product;
            });
        },
        async onPartlyCancel(e) {
            const requestData = {
                products: this.selectedProducts,
                contract_id: Number(this.contract_id),
                external_id: String(Date.now()),
            };

            const headers = {
                Authorization: `Bearer ${this.api_token}`,
            };

            try {
                this.isLoading = true;

                await axios.post('/api/v3/admin/contract/partly-cancel', requestData, { headers });

                $('.close-button').click()

                window.location.reload();
            } catch (e) {
                $('.close-button').click()
                e.response?.data?.error.forEach(element => polipop.add({
                    content: element.text,
                    title: 'Ошибка !',
                    type: 'error',
                }));
            } finally {
                setTimeout(() => this.isLoading = false, 500)
            }
        },
    },
});
</script>
<!-- Partial Cancellation JavaScript -->




