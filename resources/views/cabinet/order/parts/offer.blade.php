
<div class="offer order-block">
    <a name="offer"></a>

    <div class="title">
        {{__('offer.header')}} № {{$order->contract->id}}
    </div>


    <div class="row">
        <div class="col-12 col-md mb-4 mb-md-0">
            <div class="pdf">
                <a href="{{$offer_pdf}}" class="btn btn-outline-primary">{{__('offer.btn_download_offer')}}</a>
                <a href="{{$account_pdf}}" class="btn btn-outline-primary">{{__('offer.btn_download_act')}}</a>
            </div><!-- /.pdf -->
        </div>
        <div class="col-12 col-md">
            <div class="info">
                <table>
                    <tr>
                        <td>{{__('offer.total')}}</td>
                        <td>{{$order->contract->total}} {{__('app.currency')}}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.status')}}</td>
                        <td>{{$order->contract->status_caption}}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.date_offer')}}</td>
                        <td>{{$order->contract->confirmed_at}}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.shipping_price')}}</td>
                        <td>{{$order->shipping_price}} {{__('app.currency')}}</td>
                    </tr>
                </table>
            </div><!-- /.info -->
        </div>
    </div><!-- /.row -->


    <div class="participants">

        <div class="row">
            <div class="col-12 col-md">
                <div class="lead">{{__('offer.seller')}}</div>
                <table>
                    <tr>
                        <td>{{__('offer.seller_name')}}</td>
                        <td>{{ env('test_COMPANY_NAME') }}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.seller_address')}}</td>
                        <td>{{ env('test_ADDRESS') }}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.seller_inn')}}</td>
                        <td>{{ env('test_INN') }}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.seller_oked')}}</td>
                        <td>{{ env('test_OKED') }}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.seller_mfo')}}</td>
                        <td>{{ env('test_MFO') }}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.seller_bank_account')}}</td>
                        <td>{{ env('test_INVOICE') }}</td>
                    </tr>
                </table>
            </div>

            <div class="col-12 col-md">
                <div class="lead">{{__('offer.buyer')}}</div>
                <table>
                    <tr>
                        <td>{{__('offer.buyer_name')}}</td>
                        <td>{{$order->buyer->fio}}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.buyer_address')}}</td>
                        <td>{{$order->buyer->addressRegistration->string}}</td>
                    </tr>
                    <tr>
                        <td>{{__('offer.buyer_id')}}</td>
                        <td>{{$order->buyer->id}}</td>
                    </tr>
                </table>
            </div>

        </div><!-- /.row -->
    </div><!-- /.participants -->

    <hr>
    <div class="offer-text">{{__('offer.txt_offer_1')}}</div>
    <hr>

    <div class="products">
        <table>
            <thead>
                <th>№</th>
                <th>{{__('offer.product_name')}}</th>
                <th class="d-none d-md-table-cell">{{__('offer.unit')}}</th>
                <th><span class="d-none d-md-inline">{{__('offer.amount')}}</span></th>
                <th class="d-none d-md-table-cell">{{__('offer.price')}}</th>
                <th class="d-none d-md-table-cell">{{__('offer.nds')}}</th>
                <th><span class="d-none d-md-inline">{{__('offer.nds_price')}}</span></th>
            </thead>
            <tbody>

            @for($i = 0; $i < count($order->products); $i++)
                <tr>
                    <td>{{$i+1}}</td>
                    <td>{{$order->products[$i]->name }}</td>
                    <td class="d-none d-md-table-cell">{{__('offer.piece')}}</td>
                    <td class="amount">x {{$order->products[$i]->amount }}</td>
                    <td class="d-none d-md-table-cell">{{round($order->products[$i]->price/1.15, 2) }}</td>
                    <td class="d-none d-md-table-cell">{{$nds*100}} %</td>
                    <td>
                        <div class="total">
                            {{$order->products[$i]->price}}
                        </div>
                    </td>
                </tr>
            @endfor
            </tbody>
        </table>
    </div>

    <hr>
    <div class="row offer-results">
        <div class="col-12 col-sm mb-3 mb-sm-0">
            <div class="caption">{{__('offer.conditions')}}</div>
            <div class="offer-condition">
                {{$order->contract->total}} {{__('offer.to_pay')}}
                <div class="period">
                    &mdash; {{$order->contract->period}} {{__('offer.months')}}
                </div><!-- ./period -->
            </div><!-- /.offer-condition -->
        </div>

        <div class="col-12 col-sm">
            <div class="caption">{{__('offer.offer_total')}}</div>
            <div class="offer-total">{{$order->contract->total}}</div>
        </div>
    </div><!-- /.offer-results -->

    <hr>


    <div class="payments">
        <table>
            <thead>
            <th>№</th>
            <th>{{__('offer.payment_date')}}</th>
            <th>{{__('offer.payment_total')}}</th>
            <th>{{__('offer.payment_balance')}}</th>

            </thead>
            <tbody>
            @foreach($order->contract->schedule as $index => $payment)
                <tr>
                    <td>{{$index + 1}}</td>
                    <td>{{$payment->date}}</td>
                    <td>{{$payment->total}}</td>
                    <td>{{$payment->balance}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <hr>

    <div class="offer-text">{!! __('offer.txt_offer_2') !!}</div>
    <hr>

    <div class="text-md-right d-flex justify-content-between flex-wrap d-md-block">
        <a href="{{$offer_pdf}}" class="btn btn-outline-primary mb-2 mb-md-0">{{__('offer.btn_download_offer')}}</a>
        <a href="{{$account_pdf}}" class="btn btn-outline-primary">{{__('offer.btn_download_act')}}</a>
    </div>

</div>
