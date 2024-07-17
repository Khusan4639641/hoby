<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{__('offer.header')}}</title>
</head>
<body>
<div class="offer order-block">
    <div class="title">
        <h4>СПЕЦИФИКАЦИЯ №{{ $order->contract->prefix_act }} от {{ $order->created_at }}г.</h4>
        <p class="subtitle">к Оферте согласно Генеральному договору о сотрудничестве №{{ $order->company->uniq_num ?? '__' }} от {{ date( 'd.m.Y', strtotime( $order->company->date_pact ) ) }} г.</p>
    </div>

    <p>
        ООО «ARHAT GRAVURE», именуемое в дальнейшем «Покупатель», в лице Директора Тожиева П.М., действующего на
        основании Устава с одной стороны и {{ $order->company->name }} , именуемое в дальнейшем «Продавец», в лице
        Директора {{ $order->partner->fio }}, действующего на основании Устава с другой стороны, вместе именуемые
        Стороны, заключили
        настоящую Спецификацию на Товар (далее - спецификация) о нижеследующем:
    </p>
    <br>
    <p>Поставщик обязуется передать, а Покупатель принять и оплатить следующий Товар:</p>

    {{--        <div class="row">
                <div class="col-12 col-md">
                    <div class="info">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>{{__('offer.total')}}</td>
                                <td>{{$order->contract->order->partner_total}} {{__('app.currency')}}</td>
                            </tr>
                            <tr>
                                <td>{{__('offer.date_offer')}}</td>
                                <td>{{$order->contract->confirmed_at}}</td>
                            </tr>
                            --}}{{--<tr>
                                <td>{{__('offer.shipping_price')}}</td>
                                <td>{{$order->shipping_price}}</td>
                            </tr>--}}{{--
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
                                    <td>{{__('offer.company')}}</td>
                                    <td>{{$order->company->name}}</td>
                                </tr>
                                <tr>
                                    <td>{{__('offer.seller')}}</td>
                                    <td>{{$order->partner->fio}}</td>
                                </tr>
                                @if($order->company->address != null)
                                    <tr>
                                        <td>{{__('offer.seller_address')}}</td>
                                        <td>{{$order->company->address}}</td>
                                    </tr>
                                @endif
                                @if($order->company->inn != null)
                                    <tr>
                                        <td>{{__('offer.seller_inn')}}</td>
                                        <td>{{$order->company->inn}}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>{{__('offer.seller_id')}}</td>
                                    <td>{{$order->company->id}}</td>
                                </tr>

                            </table>

                        </td>


                        <td class="part">
                            <div class="lead">{{__('offer.buyer')}}</div>
                            <table>
                                <tr>
                                    <td>{{__('offer.buyer')}}</td>
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
                    </tr>
                </table>

            </div><!-- /.participants -->

            <div class="hr"></div>
            <div class="offer-text">{{__('offer.txt_offer_vendor_1')}}</div>
            <div class="hr"></div>--}}

    {{--{{ dd($order->products) }}--}}


    <div class="products">
        <table cellpadding="0" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>№</th>
                <th>{{__('offer.product_name')}}</th>
                <th class="d-none d-md-table-cell">Единица измерения</th>
                <th><span class="d-none d-md-inline">Количество</span></th>
                <th class="d-none d-md-table-cell">Цена</th>
                <th class="d-none d-md-table-cell">Сумма</th>
                <th class="d-none d-md-table-cell">НДС %</th>
                <th class="d-none d-md-table-cell">Сумма НДС (15%)</th>
                <th><span class="d-none d-md-inline">Всего с НДС</span></th>

            </tr>
            </thead>
            <tbody>
            @php
                if( isset($order->partnerSettings) && $order->partnerSettings->nds ){
                    $nds = $order->partnerSettings->nds ? 1 : 0;
                    $nds_title = $order->partnerSettings->nds ? 15 : 0;
                }
            @endphp
            @for($i = 0; $i < count($order->products); $i++)
                @php
                    $nds_price = $order->partnerSettings->nds ? round($order->products[$i]->price_discount/1.15, 2) : round($order->products[$i]->price_discount, 2);
                    $nds_total = $order->partnerSettings->nds ? round($order->products[$i]->price_discount/1.15, 2) * $order->products[$i]->amount : round($order->products[$i]->price_discount, 2) * $order->products[$i]->amount;
                    $nds_sum = $order->partnerSettings->nds ? round($order->products[$i]->price_discount * $order->products[$i]->amount / 1.15 * 0.15, 2) : 0;
                    $nds_title = $order->partnerSettings->nds ? 15 : 0;
                @endphp
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $order->products[$i]->name }}</td>
                    <td class="d-none d-md-table-cell">Шт</td>
                    <td class="amount">x {{ $order->products[$i]->amount }}</td>
                    <td class="d-none d-md-table-cell">
                        {{ $nds_price }}
                    </td>
                    <td class="d-none d-md-table-cell">
                        {{ $nds_total }}
                    </td>
                    <td class="d-none d-md-table-cell">{{ $nds_title }}</td>
                    <td class="d-none d-md-table-cell">
                        {{ $nds_sum  }}
                    </td>
                    <td>
                        <div class="total">
                            {{ $order->products[$i]->price_discount * $order->products[$i]->amount }}
                        </div>
                    </td>
                </tr>
            @endfor
            </tbody>
            <tfoot>
            <tr>
                <td></td>
                <td colspan="8" style="font-weight: bold;">
                    {!! __('account.products_total', [
                        'total' => $order->partner_total
                    ]) !!}
                </td>
            </tr>
            </tfoot>
        </table>
    </div>

    {{--    <div class="hr"></div>
        <div class="offer-results">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>


                    <td class="part">
                        <div class="caption">{{__('offer.offer_total')}}</div>
                        <div class="offer-total">{{$order->contract->order->partner_total}}</div>
                    </td>
                </tr>
            </table>
        </div><!-- /.offer-results -->

        <div class="hr"></div>

        <div class="offer-text">{!! __('offer.txt_offer_2') !!}</div>--}}

    <br>
    <p>Сумма спецификации: {{ Str::ucfirst(num2str($order->partner_total)) }}</p>
    <br>
    <p>Сумма Спецификации включает в себя стоимость Товара, упаковки, доставки до склада Покупателя.</p>

</div>

<div class="qr-code" style="width:100px">
    {!! $qrcode !!}
</div>

<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 8px
    }

    table {
        font-family: DejaVu Sans, sans-serif;
        font-size: 8px;
    }

    .offer table td {
        vertical-align: top;
    }

    .offer .title {
        font-size: 8px;
        font-weight: bold;
        line-height: 20px;
        text-align: center;
        margin-bottom: 8px;
    }

    .offer .title .subtitle {
        margin: 0;
    }

    .offer .title h4 {
        margin: 0;
    }

    .header-bottom p {
        margin: 5px 0 0 0;
        padding: 0;
    }

    ol {
        padding-left: 14px;
        margin: 5px 0;
    }

    @media (max-width: 575px) {
        .offer .title {
            margin-left: -1rem;
            margin-right: -1rem;
            padding: 1rem;
        }
    }
    .offer .info table {
        width: 100%;
    }
    .participants .part table {
        margin: 0 auto;
    }
    .participants table {
        margin-bottom: 1rem;
    }
    .participants table table tr td {
        padding-top: 12px;
    }

    .participants table table tr td:first-child {
        color: rgba(0, 0, 0, 0.6);
    }

    .offer .payments.h-80px {
        height: 100px;
        position: relative;
    }

    .offer .payments.h-60px {
        height: 80px;
        position: relative;
    }

    .offer .products table {
        text-align: center;
    }

    .offer .payments table {
        text-align: center;
    }

    .offer .payments table.first-half {
        font-family: DejaVu Sans, sans-serif !important;
        position: absolute;
        top: 0;
        left: 0;
        text-align: center;
        margin-bottom: 8px;
        /*width: 100%;*/
    }

    .offer .payments table.second-half {
        font-family: DejaVu Sans, sans-serif !important;
        text-align: center;
        position: absolute;
        top: 0;
        right: 0;
        margin-bottom: 8px;
    }

    .offer .products table tfoot tr td:last-child {
        text-align: left;
        padding-left: 1rem;
        font-weight: 400;
    }

    .offer .products table th, .offer .payments table th {
        font-family: DejaVu Sans, sans-serif !important;
        padding: 0;
        background: #f2f2f2;
        font-size: 9px;
        font-weight: 400;
    }

    .offer .products table td {
        /*padding: 1rem;*/
        border: 1px solid rgba(0, 0, 0, 0.15);
    }

    .offer .payments table td {
        border: 1px solid rgba(0, 0, 0, 0.15);
    }

    .offer .products table .total, .offer .payments table .total {
        font-weight: bold;
    }
</style>

</body>

</html>
