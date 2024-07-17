<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{__('offer.header')}}</title>
</head>
<body>
<div class="offer order-block">

    <div class="title">Оферта № {{$order['contract']['id']}}</div>

    <header>
        <div class="region">г.Ташкент</div>
        <div class="date">{{ $order['contract']['date'] }} г.</div>
    </header>

    <div class="offer-text">
        ООО «ARHAT GRAVURE», на основании поступившей заявки и принятой Покупателем Публичной оферты, предлагает к
        приобретению, гражданину(ке) {{ $buyer->fio }}
        ( {{ \App\Helpers\EncryptHelper::decryptData($buyer->personals->passport_number) ?: '_______' }}
        от {{ \App\Helpers\EncryptHelper::decryptData($buyer->personals->passport_date_issue) ?: '_______' }}г.
        ),
        проживающий по адресу: {{ $buyer->addressRegistration->string??'---' }}, перечень
        товаров (далее – Товар) согласно нижеследующей таблице:
    </div>
    <ol>
        <li>Спецификация на Товар:</li>
    </ol>

    {{--        <div class="row">
                <div class="col-12 col-md">
                    <div class="info">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>{{__('offer.total')}}</td>
                                <td>{{$order['price']['total'] ?? 126000}} {{__('app.currency')}}</td>
                            </tr>
                            <tr>
                                <td>{{__('offer.date_offer')}}</td>
                                <td>{{$order['contract']['date']}}</td>
                            </tr>
                            <tr>
                                <td>{{__('offer.shipping_price')}}</td>
                                <td>{{$order['price']['shipping'] ?? 9000}} {{__('app.currency')}}</td>
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
                                <tr>
                                    <td>{{__('offer.seller_reg_nds')}}</td>
                                    <td>{{ env('NDS') }}</td>
                                </tr>
                            </table>
                        </td>

                        <td class="part">
                            <div class="lead">{{__('offer.buyer')}}</div>
                            <table>
                                <tr>
                                    <td>{{__('offer.buyer_name')}}</td>
                                    <td>{{$buyer->fio}}</td>
                                </tr>
                                <tr>
                                    <td>{{__('offer.buyer_address')}}</td>
                                    <td>{{@$buyer->addressRegistration->string}}</td>
                                </tr>
                                <tr>
                                    <td>{{__('offer.buyer_id')}}</td>
                                    <td>{{$buyer->id}}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </div><!-- /.participants -->

            <div class="hr"></div>
            <div class="offer-text">{{__('offer.txt_offer_1')}}</div>
            <div class="hr"></div>--}}

    <div class="products">
        <table cellpadding="0" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>№</th>
                <th>Наименование и описание Товара (цвет и иные характеристики)</th>
                {{--                <th class="d-none d-md-table-cell">{{__('offer.unit')}}</th>--}}
                <th><span class="d-none d-md-inline">Ед. изм. (шт. или комплект) Шт.</span></th>
                {{--                <th class="d-none d-md-table-cell">{{__('offer.price')}}</th>--}}
                {{--                <th class="d-none d-md-table-cell">{{__('offer.nds')}}</th>--}}
                <th>
                    <span class="d-none d-md-inline">
                        Общая стоимость с учетом рассрочки
                        (в соответствии с условиями Договора за единицу Товара с НДС)
                    </span>
                </th>
            </tr>
            </thead>
            <tbody>

            @for($i = 0; $i < count($order['products']); $i++)
                <tr>
                    <td>{{$i+1}}</td>
                    <td>{{$order['products'][$i]['name'] }}</td>
                    {{--                    <td class="d-none d-md-table-cell">{{__('offer.piece')}}</td>--}}
                    <td class="amount">{{$order['products'][$i]['amount'] }}</td>
                    {{--                    <td class="d-none d-md-table-cell">{{round($order['products'][$i]['price']/1.15, 2) }}</td>--}}
                    {{--                    <td class="d-none d-md-table-cell">{{round($order['products'][$i]['price']/1.15  *  0.15, 2) }}</td>--}}
                    <td>
                        <div class="total">
                            {{$order['products'][$i]['price']}}
                        </div>
                    </td>
                </tr>
            @endfor
            </tbody>
            <tfoot>
            <tr>
                <td></td>
                <td colspan="6" style="font-weight: bold">
                    {!! __('account.products_total', [
                        'total' => $order['price']['total'] + $order['price']['deposit']
                    ]) !!}
                </td>
            </tr>
            </tfoot>
        </table>
    </div>

    <ol start="2">
        <li>Товары предлагаются на условиях оплаты в рассрочку согласно нижеприведенному графику платежей:</li>
    </ol>

    {{--    <div class="hr"></div>
        <div class="offer-results">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="part">
                        <div class="caption">{{__('offer.conditions')}}</div>
                        <div class="offer-condition">
                            {{$order['price']['total'] ?? 1260000}} {{__('offer.to_pay')}}
                            <div class="period">
                                &mdash; {{$period}} {{__('offer.months')}}
                            </div><!-- ./period -->
                        </div><!-- /.offer-condition -->
                    </td>

                    <td class="part">
                        <div class="caption">{{__('offer.offer_deposit')}}</div>
                        <div class="offer-total"> {{$order['price']['deposit'] ?? 9000}}</div>
                    </td>

                    <td class="part">
                        <div class="caption">{{__('offer.offer_total')}}</div>
                        <div
                            class="offer-total"> {{/*$order['price']['total'] ??*/ 1260000 + /*$order['price']['deposit'] ??*/ 9000}}</div>
                    </td>

                </tr>
            </table>
        </div><!-- /.offer-results -->

        <div class="hr"></div>--}}


    <div class="payments">
        <table width="100%" cellpadding="0" cellspacing="0">
            <thead>
            <tr>
                <th>№</th>
                <th>{{__('offer.payment_date')}}</th>
                <th>Сумма Платежа ( в сум, с НДС)</th>
                <th>Остаток платежа</th>
            </tr>
            </thead>
            <tbody>
            @php
                $total = $order['price']['total'];
            @endphp

            @foreach($order['contract']['payments'] as $index => $payment)
                @php
                    $total -= $payment['total'];
                    if($total < 0) $total = 0;
                @endphp
                <tr>
                    <td>{{$index + 1}}</td>
                    <td>{{$payment['date']}}</td>
                    <td>{{$payment['total']}}</td>
                    <td>{{(int)$total}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{--    <div class="hr"></div>--}}

    {{--    <div class="offer-text">{!! __('offer.txt_offer_2') !!}</div>--}}
    <ol start="3">
        <li>Настоящая оферта направлена в личный кабинет зарегистрированного пользователя
            в {{$order['contract']['date']}}
        </li>
        <li>Настоящая оферта является неотъемлемой частью Публичной оферты заключенной
            между Продавцом и
            Покупателем {{ $order['contract']['date'] }} и является ее дополняющим звеном.
        </li>
        <li>Принятие данной оферты подтверждается следующими совершенными
            Покупателем действиями: по запросу Продавца Покупателю направлено SMS-сообщение с уникальным кодом – «Аналог
            собственноручной подписи» - с запросом на подтверждение согласия на заключение Договора
            и предоставленного графика платежей посредством отправки. В случае согласия Покупателя с условиями
            электронной Заявки Продавца, Покупателю необходимо сообщить уникальный код Продавцу, и он в свою очередь
            вводит предоставленный уникальный код в Платформу «test» тем самым подтверждая факт заключения договора.
            В соответствии со ст.370 Гражданского кодекса РУз, отправка SMS с уникальным кодом, подтверждающим согласие
            Клиента со всеми условиями заключаемого договора, считается акцептом, и, соответственно, электронный договор
            купли-продажи товара на платформе «test» (Сделка) считается заключенным между Продавцом и Покупателем
            (Клиентом).
        </li>
        <li>Приняв данную Оферту, Покупатель выражает полное согласие с информацией, приведенной в Спецификации
            настоящей Оферты в п. 1,
            в том числе с ценой и со стоимостью и условиями рассрочки.
        </li>
        <li>Акцептом данной Оферты, помимо действий, указанных в п. 5 настоящей Оферты, также считается и факт
            получения Покупателем Товара по Акту приема передачи, составленному уполномоченными представителями Сторон.
        </li>
    </ol>

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
    }

    header {
        height: 20px;
    }

    header .region {
        float: left;
    }

    header .date {
        float: right;
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

    .act table {
        text-align: center;
    }

    .act table td {
        border: 1px solid rgba(0, 0, 0, 0.15);
        box-sizing: border-box;
    }
</style>

</body>

</html>
