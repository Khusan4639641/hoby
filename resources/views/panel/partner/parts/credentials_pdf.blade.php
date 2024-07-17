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
            margin: 2cm 1.4cm;
            font-family: 'TNRoman';
            /*font-style: normal;*/
            /*font-weight: normal;
            font-style: normal;*/
            letter-spacing: 0px;
        }

        p {
            margin: 0;
            padding: 0;
        }
    </style>

</head>

<body>
<div class="container">
    <div class="body">
        <p>Hurmatli hamkor {{ $partner->name }} > {{ $partner->brand }}, resus ekotizimiga xush kelibsiz!
            Sizga resusNasiya platformasi bilan ishlash uchun avtorizatsiya ma'lumotlarini taqdim etamiz.</p>
        <br>
        <p>Уважаемый партнер {{ $partner->name }} > {{ $partner->brand }}, рады приветствовать Вас в экосистеме resus!
            Предоставляем Вам данные по авторизации для  работы с платформой resusNasiya.</p>
        <br>
        <p>Личный кабинет / Shaxsiy kabinet: merchant.resusnasiya.uz</p>
        <p>Логин ID / Login ID: {{ $login }}</p>
        <p>Пароль  / Parol: {{ $password }}</p>
    </div>
</div>

</body>
</html>
