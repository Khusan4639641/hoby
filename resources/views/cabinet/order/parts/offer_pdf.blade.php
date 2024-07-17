<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{__('katm.report_heading')}}</title>
</head>
<body>
    <div class="offer order-block">

        <div class="title">
            {{__('offer.header')}} № {{$order->contract->id}}
        </div>


        <div class="row">
            <div class="col-12 col-md">
                <div class="info">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>{{__('offer.total')}}</td>
                            <td>{{$order->contract->total}} {{__('app.currency')}}</td>
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

            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="part">
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
                    </td>

                    <td class="part">
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
                    </td>
                </tr>
            </table>

        </div><!-- /.participants -->

        <div class="hr"></div>
        <div class="offer-text">{{__('offer.txt_offer_1')}}</div>
        <div class="hr"></div>

        <div class="products">
            <table cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>{{__('offer.product_name')}}</th>
                        <th class="d-none d-md-table-cell">{{__('offer.unit')}}</th>
                        <th><span class="d-none d-md-inline">{{__('offer.amount')}}</span></th>
                        <th class="d-none d-md-table-cell">{{__('offer.price')}}</th>
                        <th class="d-none d-md-table-cell">{{__('offer.price_sell')}}</th>
                        {{--<th class="d-none d-md-table-cell">{{__('offer.nds')}}</th>--}}
                        <th class="d-none d-md-table-cell">{{__('offer.nds_total')}} ({{$nds*100}}%)</th>
                        <th><span class="d-none d-md-inline">{{__('offer.nds_price')}}</span></th>
                    </tr>
                </thead>
                <tbody>

                @for($i = 0; $i < count($order->products); $i++)
                    <tr>
                        <td>{{$i+1}}</td>
                        <td>{{$order->products[$i]->name }}</td>
                        <td class="d-none d-md-table-cell">{{__('offer.piece')}}</td>
                        <td class="amount">x {{$order->products[$i]->amount }}</td>
                        <td class="d-none d-md-table-cell">{{round($order->products[$i]->price_discount/1.15, 2) }}</td>
                        <td class="d-none d-md-table-cell">{{round($order->products[$i]->price_discount/1.15, 2) }}</td>
                        <td class="d-none d-md-table-cell">{{round($order->products[$i]->price_discount*0.15, 2) }}</td>
                        <td>
                            <div class="total">
                                {{$order->products[$i]->price_discount}}
                            </div>
                        </td>
                    </tr>
                @endfor
                </tbody>
            </table>
        </div>

        <div class="hr"></div>
        <div class="offer-results">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="part">
                        <div class="caption">{{__('offer.conditions')}}</div>
                        <div class="offer-condition">
                            {{$order->contract->total}} {{__('offer.to_pay')}}
                            <div class="period">
                                &mdash; {{$order->contract->period}} {{__('offer.months')}}
                            </div><!-- ./period -->
                        </div><!-- /.offer-condition -->
                    </td>

                    <td class="part">
                        <div class="caption">{{__('offer.offer_total')}}</div>
                        <div class="offer-total">{{$order->contract->total}}</div>
                    </td>
                </tr>
            </table>
        </div><!-- /.offer-results -->

        <div class="hr"></div>


        <div class="payments">
            <table width="100%" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>{{__('offer.payment_date')}}</th>
                        <th>{{__('offer.payment_total')}}</th>
                        <th>{{__('offer.payment_balance')}}</th>
                    </tr>
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
        <div class="hr"></div>

        <div class="offer-text">{!! __('offer.txt_offer_2') !!}</div>


    </div>

    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px }
        .offer .hr {
            color: transparent;
            background-color: transparent;
            margin: 2rem 0;
            height: 4px;
            border: 0;
            border-bottom: 4px solid #f8f8f8;
        }

        .offer table td {
            vertical-align: top;
        }

        .offer .title {
            background: #00A193;
            color: #fff;
            margin: -2rem -2rem 2rem;
            padding: 2rem;
            border-radius: 0.5rem 0.5rem 0 0;
            font-weight: bold;
            font-size: 1.5rem;
        }

        @media (max-width: 575px) {
            .offer .title {
                margin-left: -1rem;
                margin-right: -1rem;
                padding: 1rem;
            }
        }

        .offer .pdf {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .offer .pdf .btn {
            margin-bottom: 1rem;
        }

        .offer .info {
            background: #f2f2f2;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 3rem;
        }

        .offer .info table {
            width: 100%;
        }

        .offer .info table td {
            padding: .25rem;
        }

        .offer .info table tr td:first-child {
            color: rgba(0, 0, 0, 0.6);
        }

        .offer .participants {
            display: flex;
        }

        .offer .participants .part {
            width: 50%;
        }

        .offer .participants .lead {
            font-size: 1.125rem;
        }

        .offer .participants .lead:after {
            display: none;
        }

        .offer .participants table {
            margin-bottom: 1rem;
        }

        .offer .participants table td {
            padding: .25rem 0 .25rem;
        }

        .offer .participants table table tr td:first-child {
            width: 130px;
            color: rgba(0, 0, 0, 0.6);
        }

        @media (max-width: 575px) {
            .offer .participants table td {
                font-size: 0.875rem;
            }
        }

        .offer .offer-text {
            line-height: 1.5;
        }

        .offer .products table, .offer .payments table {
            width: 100%;
        }

        .offer .products table th, .offer .payments table th {
            padding: 1rem;
            background: #f2f2f2;
            font-size: 0.75rem;
            font-weight: 400;
        }

        .offer .products table th:first-child, .offer .payments table th:first-child {
            border-radius: 0.25rem 0 0 0.25rem;
        }

        .offer .products table th:last-child, .offer .payments table th:last-child {
            border-radius: 0 0.25rem 0.25rem 0;
        }

        @media (max-width: 575px) {
            .offer .products table th, .offer .payments table th {
                padding: .5rem .25rem;
            }
        }

        .offer .products table td, .offer .payments table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 575px) {
            .offer .products table td, .offer .payments table td {
                padding: .5rem .25rem;
                font-size: 0.875rem;
            }
        }

        .offer .products table tr:last-child td, .offer .payments table tr:last-child td {
            border-bottom: none;
        }

        .offer .products table .total, .offer .payments table .total {
            font-weight: bold;
        }

        .offer .payments table td {
            padding: .5rem;
        }

        .offer .offer-results {
            display: flex;
        }

        .offer .offer-results .part {
            width: 50%;
        }

        .offer .offer-results .caption {
            color: rgba(0, 0, 0, 0.6);
            font-size: 0.875rem;
            margin-bottom: .25rem;
        }

        .offer .offer-results .offer-total {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .offer .offer-results .offer-condition .period {
            font-weight: bold;
        }
    </style>
</body>

</html>
