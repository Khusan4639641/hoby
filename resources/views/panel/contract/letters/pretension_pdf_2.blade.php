<!DOCTYPE html>
<html>

<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>

        @page {
            margin: 0;
        }

        @font-face {
            font-family: 'TNRoman';
            src: url('{{url('/assets/fonts/pdf/times.ttf')}}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'TNRoman';
            src: url('{{url('/assets/fonts/pdf/timesbd.ttf')}}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @font-face {
            font-family: 'TNRoman';
            src: url('{{url('/assets/fonts/pdf/timesbi.ttf')}}') format('truetype');
            font-weight: bold;
            font-style: italic;
        }
        @font-face {
            font-family: 'TNRoman';
            src: url('{{url('/assets/fonts/pdf/timesi.ttf')}}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }

        body {
            margin: 0 1.4cm;
            font-family: 'TNRoman';
            /*font-style: normal;*/
            /*font-weight: normal;
            font-style: normal;*/
            letter-spacing: 0px;
        }

        .main {
            /*font-size: 14.5pt*/
            font-size: 12.5pt;
        }

        p {
            margin: 0;
            padding: 0;
        }

        .d-flex {
            display: flex;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .justify-content-around {
            justify-content: space-around;
        }

        .font-weight-bold {
            font-weight: bold;
            font-family: 'TNRoman';
        }

        .pretension-page {
            height: 1000px;
            /*width: 720px;*/
            padding: 50px 35px 0 40px;
            /*font-size: 17px;*/
        }

        .pretension-page p {
            /*line-height: 28px;*/
            line-height: 1.2;

        }

        .pretension-page .header .date-number {
            align-items: center;
            font-weight: bold;
            font-family: 'TNRoman';
            margin-bottom: 10px;
            /*font-size: 14.5pt*/
            font-size: 13pt
        }

        .pretension-page .header .date-number span:last-child {
            margin-right: 30px;
        }

        .pretension-page .header .head {
            margin-bottom: 30px;
            /*display: flex;*/
            /*justify-content: space-between;*/
            /*align-items: center;*/
        }

        .pretension-page .header .head hr.top {
            margin-bottom: 0;
            /*margin-top: 10px;*/
            border-top: solid 1px #000;
        }

        .pretension-page .header .head hr {
            margin: 1px 0;
            border-top: solid 3px #000;
        }

        .pretension-page .header .head hr.bottom {
            margin-top: 0;
            /*margin-bottom: 10px;*/
            border-top: solid 1px #000;
        }

        .pretension-page .header span.font-weight-bold {
            /*font-size: 38px;*/
            font-size: 23.5pt;
            font-weight: bold;
            font-family: 'TNRoman';
        }

        .pretension-page .header span.company-info {
            width: 40%;
            /*font-size: 11pt;*/
            font-size: 9.5pt;
            font-weight: bold;
            font-family: 'TNRoman';
            /*line-height: 26px;*/
        }

        .pretension-page .header .buyer-info {
            width: 40%;
            margin-left: auto;
            text-align: right;
            /*font-size: 14pt;*/
            font-size: 11pt;
            line-height: 1.2;
        }

        .pretension-page .header p {
            margin: 0 0 20px 0;
        }

        .pretension-page .header hr {
            margin: 10px 0;
        }

        .pretension-page .main p {
            text-align: justify;

            /*text-indent: 30px;*/
            /*margin: 0 0 7px 0;*/
        }

        .pretension-page .main p:last-child {
            /*margin: 10px 0 0 0;*/
            /*text-indent: 30px;*/
        }

        .pretension-page .main h3 {
            /*margin: 14px 0;*/
            font-size: 14.5pt
        }

        .footer {
            /*font-size: 14.5pt;*/
            /*font-size: 13pt;*/
            font-size: 12.5pt
        }

        .pretension-page .footer-child {
            margin: 0 0 0 30px;
            /*font-size: 14.5pt*/
            /*font-size: 13pt*/
            font-size: 12.5pt
        }

        .container-podpis {
            position: relative;
            float: left;
            margin-left: 90px;
        }

        .podpis-pechat {
            display: inline-block;
            height: auto;
            position: absolute;
            top: -40px;
            left: -95px;
        }

        .ceo-position {
            float: left;
        }

        .ceo-name {
            text-align: right;
            float: right;
        }

        .call-center {
            font-style: italic;
            font-family: 'TNRoman';
            font-size: 11pt;
            margin-top: 50px;
            clear: both;
        }
    </style>

</head>

<body>

@php
    $buyerFIO = $contract->buyer->fio
@endphp

<div class="container">
    <div class="pretension-page">
        <section class="header">
            <div class="text-center head">
                <span class="font-weight-bold">
                    {{ __('panel/contract.gravure', [
                        "ru" => $contract->generalCompany->name_ru,
                        "uz" => $contract->generalCompany->name_uzlat,
                    ]) }}
                </span>
                <hr class="top">
                <hr>
                <hr class="bottom">
                <span class="company-info">
                    {{ __('panel/contract.general_company_info', [
                        "address" => $contract->generalCompany->address,
                        "settlement_account" => $contract->generalCompany->settlement_account,
                        "mfo" => $contract->generalCompany->mfo,
                        "inn" => number_format($contract->generalCompany->inn, 0, "", " "),
                        "oked" => $contract->generalCompany->oked,
                    ]) }}
                </span>
            </div>
            <div class="d-flex justify-content-between">
                <div class="date-number">
                    <div>№ {{ $contract->recover->id ?? '__' }}</div>
                    <div>
                        {{ __('panel/contract.letter_to_residency_date', [ 'date' => date("d.m.Y") ]) }}
                    </div>
                </div>
                <div class="buyer-info">
                    <span>{{ $buyerFIO }}</span>
                    <br>
                    <span class="buyer-address">
                    {{ $contract->buyer->addressRegistration->address ?? '' }}
                    </span>
                </div>
            </div>
            <div style="clear: both"></div>
        </section>

        <section class="main" style="margin-top: -80px;">

            <h2 class="text-center" style="font-weight: bold;">{{ __('panel/contract.title') }}</h2>
            {!! __('panel/contract.body_2', [
                'buyerFIO' => '<span class="font-weight-bold">' . $buyerFIO . '</span>',
                'contract_id' => '<span class="font-weight-bold">' . $contract->id . '</span>',
                'contract_created_at' => '<span class="font-weight-bold">' . date('d.m.Y', strtotime($contract->created_at)) . '</span>' ,
                'now' => '<span class="font-weight-bold">' . date("d.m.Y") . '</span>',
                'debts_amount' => '<span class="font-weight-bold">' . number_format($contract->debts_amount, 2, ',', ' ') . '</span>',
                'phone' => '<span class="font-weight-bold">' . $contract->buyer->phone . '</span>',

                'total_max_autopay_post_cost' => '<span class="font-weight-bold">' . $amounts['total_max_autopay_post_cost'] . '</span>',
                // 'total_max_percent_fix_max' => '<span class="font-weight-bold">' . $amounts['total_max_percent_fix_max'] . '</span>',

                'real_expired_days_minus_one' => '<span class="font-weight-bold">' . $contract->real_expired_days_minus_one . '</span>',
                'real_expired_days_minus_one_ru' => '<span class="font-weight-bold">' . $contract->real_expired_days_minus_one_ru . '</span>',
                'general_company_name_uz' => '<span>' . $contract->generalCompany->name_uzlat . '</span>',
                'general_company_name_ru' => '<span>' . $contract->generalCompany->name_ru . '</span>',
            ]) !!}
        </section>

        <section class="footer">
            <div class="footer-child">
                <p class="font-weight-bold">
                    {{ __('cabinet/profile.work_company') }}:
                    <span style="font-weight: normal">
                        {{ __('panel/contract.gravure', [
                            "ru" => $contract->generalCompany->name_ru,
                            "uz" => $contract->generalCompany->name_uzlat,
                        ]) }}
                    </span></p>
                <p class="font-weight-bold">АКБ «Капиталбанк</p>
                <p>
                    <span class="font-weight-bold">{{__('panel/contract.payment_account')}}: </span>
                    <span>{{ $contract->generalCompany->settlement_account }}</span>
                </p>
                <p><span>
                    в ОПЕРУ АКБ «Kapitalbank»
                </span></p>
                <p>
                    <span class="font-weight-bold">{{__('offer.seller_mfo')}}: </span>
                    <span class="mr-8">{{ $contract->generalCompany->mfo }};</span>
                    <span class="font-weight-bold">{{__('offer.seller_inn')}}: </span>
                    <span class="mr-8">{{ $contract->generalCompany->inn }};</span>
                    <span class="font-weight-bold">ОКЭД: </span>
                    <span>{{ $contract->generalCompany->oked }};</span>
                </p>
            </div>
            <p style="text-indent: 30px;">{{ __('panel/contract.payment_note') }}</p>
            <div>
                <br>
                <br>
                <div class="d-flex justify-content-between" style="font-size: 12pt;">
                    <div class="ceo-position">
                        <span class="font-weight-bold">
                            {{ __('panel/contract.director') }}
                        </span>
                        <span class="font-weight-bold">
                            {{ __('panel/contract.gravure', [
                                "ru" => $contract->generalCompany->name_ru,
                                "uz" => $contract->generalCompany->name_uzlat,
                            ]) }}
                        </span>
                    </div>
                    <div class="container-podpis">
                        <img width="300" class="podpis-pechat" src="{{ \App\Helpers\FileHelper::url($contract->generalCompany->stamp) }}" alt="podpis-pechat" />
                    </div>
                    <div class="ceo-name">
                        <span class="font-weight-bold">
                            {{ __('panel/contract.general_company_director', [
                                "ru" => $contract->generalCompany->director_ru,
                                "uz" => $contract->generalCompany->director_uzlat,
                            ]) }}
                        </span>
                    </div>
                </div>
            </div>
        </section>
        <p class="call-center">Call Center: {{ $help_phone }}</p>
    </div>
</div>

</body>

</html>
