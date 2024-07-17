<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{__('account.header')}}</title>
</head>
<body>


<div class="offer order-block">

    <h1>
        {{__('account.header')}} {{$order->created_at}}
    </h1>


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
                </table>
            </div><!-- /.info -->
        </div>
    </div><!-- /.row -->

    <div class="participants">


        <div class="lead">{{__('offer.seller')}}</div>
        <table width="100%" cellpadding="0" cellspacing="0">
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

        <div class="lead">{{__('offer.buyer')}}</div>
        <table width="100%" cellpadding="0" cellspacing="0">
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


    </div><!-- /.participants -->

    <div class="hr"></div>
    <div class="offer-text">{{__('account.txt_1', ['vendor' => $order->company->name,'buyer' => $order->buyer->fio, 'offer' => $order->id])}}</div>

    <div class="products">
        <table cellpadding="0" cellspacing="0">

            <tbody>

            @for($i = 0; $i < count($order->products); $i++)
                <tr>
                    <td>{{$i+1}}</td>
                    <td>{{$order->products[$i]->name }}</td>
                    <td class="amount">{{$order->products[$i]->amount }} {{__('offer.piece')}}</td>

                    <td>
                        <div class="total">
                            @if($order->partner->settings->nds)
                                {{round($order->products[$i]->price*$order->products[$i]->amount,2) }}
                            @else
                                {{round(($order->products[$i]->price*$order->products[$i]->amount*(1+$nds)),2) }}
                            @endif
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
                        <span class="period">
                            &mdash; {{$order->contract->period}} {{__('offer.months')}}
                        </span><!-- ./period -->
                    </div><!-- /.offer-condition -->
                </td>
            </tr>
            <tr>
                <td class="part">
                    <div class="caption" style="margin-top: 2rem;">{{__('offer.offer_total')}}</div>
                    <div class="offer-total">{{$order->contract->total}}</div>
                </td>
            </tr>
        </table>
    </div><!-- /.offer-results -->

    <div class="hr"></div>

    <div class="lead">{{__('account.schedule')}}</div>
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

    <div class="offer-text">{!! __('account.txt_2') !!}</div>

    <div class="participants">


        <div class="lead">{{__('account.txt_vendor')}}</div>
        <table width="100%" cellpadding="0" cellspacing="0">

            <tr>
                <td colspan="2" >{{__('account.from_name')}}</td>
            </tr>
            <tr>
                <td colspan="2">{{$order->company->name}} {{__('account.proxy')}} {{$order->company->created_at}}</td>
            </tr>
            <tr>
                <td width="150px">{{__('account.employee_fio')}}</td>
                <td class="bordered"></td>
            </tr>

            <tr>
                <td>{{__('account.sign')}}</td>
                <td class="bordered"></td>
            </tr>
        </table>

        <div class="lead">{{__('account.txt_buyer')}}</div>
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td>{{__('offer.buyer_name')}}</td>
                <td>{{$order->buyer->fio}}</td>
            </tr>
            <tr>
                <td>{{__('offer.buyer_address')}}</td>
                <td>{{$order->buyer->addressRegistration->string}}</td>
            </tr>
            <tr>
                <td>{{__('account.passport')}}</td>
                <td>{{\App\Helpers\EncryptHelper::decryptData($order->buyer->personals->passport_number)}}</td>
            </tr>
            <tr>
                <td>{{__('account.phone')}}</td>
                <td>{{$order->buyer->phone}}</td>
            </tr>
            <tr>
                <td>{{__('account.sign')}}</td>
                <td class="bordered"></td>
            </tr>
        </table>


    </div><!-- /.participants -->
</div>

<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px }
    .offer .hr {
        color: transparent;
        background-color: transparent;
        margin: 2rem 0;
        height: 0;
        border: 0;
        border-bottom: 2px solid #f8f8f8;
    }

    .offer table td {
        vertical-align: top;
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


    .lead {
        font-size: 1.125rem;
        margin-bottom: 1rem;
    }

    .offer .participants .lead:after {
        display: none;
    }

    .offer .participants table {
        margin-bottom: 1rem;
        width: 100%;
    }

    .offer .participants table td {
        padding: .25rem .5rem;
        font-size: .75rem;
    }

    .offer .participants table td.bordered {
        border-bottom: 1px solid #ccc;
    }

    .offer .participants table table tr td:first-child {
        width: 130px;
        color: rgba(0, 0, 0, 0.6);
    }


    .offer .offer-text {
        line-height: 1.5;
        margin-bottom: 2rem;
    }

    .offer .products table, .offer .payments table {
        width: 100%;
    }

    .offer .products table th, .offer .payments table th {
        padding: 1rem;
        background: #f2f2f2;
        font-size: 0.75rem;
        font-weight: 400;
        padding: .5rem .25rem;
    }

    .offer .products table th:first-child, .offer .payments table th:first-child {
        border-radius: 0.25rem 0 0 0.25rem;
    }

    .offer .products table th:last-child, .offer .payments table th:last-child {
        border-radius: 0 0.25rem 0.25rem 0;
    }


    .offer .products table td, .offer .payments table td {
        padding: .5rem .25rem;
        font-size: 0.75rem;
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
        font-size: 1.25rem;
        font-weight: bold;
    }

    .offer .offer-results .offer-condition .period {
        font-weight: bold;
    }
</style>

<script>

    window.print();

</script>
</body>

</html>
