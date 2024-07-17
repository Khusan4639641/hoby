<div class="offer order-block">
    <a name="offer"></a>

    <div class="row align-items-center">
        <div class="col-12 col-md">
            <div class="h2">
                {{__('offer.header2')}} №{{$order->contract->id}}
            </div>
        </div>
        <div class="col-12 col-md text-md-right">
            <a href="#" class="btn btn-outline-primary">{{__('offer.btn_download_offer')}}</a>
            <a href="#" class="btn btn-outline-primary">{{__('offer.btn_download_act')}}</a>
        </div>
    </div><!-- /.row -->

    <div class="partner-buyer">
        <div class="partner">
            <div class="title">{{__('panel/contract.partner')}}</div>
            <div class="photo">
                @if($order->contract->partner->avatar)
                    <div class="preview"
                         style="background-image: url('/storage/{{$order->contract->partner->avatar->path}}')"></div>
                @else
                    <div class="preview dummy"></div>
                @endif
            </div>
            <div class="caption">
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.partner')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->contract->partner->fio}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.address')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->partner->company->address}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.legal_address')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->partner->company->legal_address}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.inn')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->partner->company->inn}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.payment_account')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->partner->company->payment_account}}</div>
                    </div>
                </div>

                <a class="more"
                   href="{{localeRoute('panel.partners.show', $order->partner)}}">{{__('app.btn_more')}}</a>
            </div>
        </div>
        <div class="buyer">
            <div class="title">{{__('panel/contract.buyer')}}</div>
            <div class="photo">
                @if($order->buyer->avatar)
                    <div class="preview"
                         style="background-image: url('/storage/{{$order->contract->buyer->avatar->path}}')"></div>
                @else
                    <div class="preview dummy"></div>
                @endif
            </div>
            <div class="caption">
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.buyer')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->buyer->fio}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.address')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->buyer->addressRegistration->string}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            ID
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->buyer->id}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.phone')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->buyer->phone}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.rating')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">0</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.bonuses')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">0</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.installment_period')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->contract->period}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.installment_amount')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->contract->total}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="label">
                            {{__('panel/contract.total_debt')}}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="value">{{$order->contract->totalDebt}}</div>
                    </div>
                </div>
                <a class="more"
                   href="{{localeRoute('panel.buyers.show', $order->buyer)}}">{{__('app.btn_more')}}</a>
            </div>
        </div>
    </div>
    <hr>
    <div class="order-data">
        <table class="table2 table-order">
            <thead>
            <tr>
                <th>№</th>
                <th>{{__('panel/contract.product_name')}}</th>
                <th>{{__('panel/contract.product_qty')}}</th>
                <th>{{__('panel/contract.product_price')}}</th>
                <th>{{__('panel/contract.product_sum')}}</th>
                <th>{{__('panel/contract.product_nds')}}</th>
                <th>{{__('panel/contract.product_nds_sum')}}</th>
                <th>{{__('panel/contract.product_nds_total')}}</th>
            </tr>
            </thead>
            <tbody>
            @php
                $total = 0;
            @endphp
            @isset($order->products)

                @foreach($order->products as $product)
                    @php
                        $sum = $product->amount * $product->price;
                        $nds_sum = $order->nds*$sum;
                        $nds_total = $nds_sum + $sum;
                        $total += $nds_total;
                    @endphp
                    <tr>
                        <td>{{$product->id}}</td>
                        <td>{{$product->name}}</td>
                        <td>{{$product->amount}}</td>
                        <td>{{$product->price}}</td>
                        <td>{{$sum}}</td>
                        <td>{{$order->nds*100}}</td>
                        <td>{{$nds_sum}}</td>
                        <td>{{$nds_total}}</td>
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
        <table class="order-info">
            <tr>
                <td class="label">{{__('panel/contract.order_delivery_method')}}</td>
                <td></td>
            </tr>
            <tr>
                <td class="label">{{__('panel/contract.order_delivery_address')}}</td>
                <td> -</td>
            </tr>
            <tr>
                <td class="label">{{__('panel/contract.order_payment_method')}}</td>
                <td></td>
            </tr>
            <tr>
                <td class="label">{{__('panel/contract.bonuses')}}</td>
                <td>0</td>
            </tr>
        </table>
        <hr class="thin">
        <div class="order-total">
            <div class="installment">
                <span>{{(__('panel/contract.installment_conditions'))}}</span>
                <span>{!! __('panel/contract.installment_conditions_text', ['total' => $order->total, 'period' => $order->contract->period])!!}</span>
            </div>
            <div class="total">
                <span>{{__('panel/contract.order_total')}}</span>
                <span>{{$order->total}}</span>
            </div>
        </div>
        <hr>
    </div>
</div>
