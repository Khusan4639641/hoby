<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://www.jqueryscript.net/css/jquerysctipttop.css">
    <title>Document</title>
    <style>
        p, h1, h2, h3 h4, h5 {
            margin: 0;
            padding: 0;
        }

        html,body {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
            font-size: 14pt;
            text-align: justify;
            line-height: 40px;
            /*margin: 2rem;*/
        }

        .content-text{
            text-align: right;
            margin-bottom: 50px;
        }

        .container{
            margin: 3rem 4rem 3rem 5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form{
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .content{
            width: 550px;
        }

        .right {
            text-align: right;
        }

        .btn-orange{
            padding: 13px 20px;
            color:#fff;
            background: #FF7643;
            outline: none;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
        }

        .btn-orange:hover{
            box-shadow: 0 0 3px #FF7643;
            transition: all 300ms ease-in;
        }

        .form-input{
            background: #ccc;
            color: #fff;
            width: 300px;
            /*padding: 5px;*/
            margin-right: 10px;
            border-radius: 5px;
        }

        ::-webkit-file-upload-button {
            background: #FF7643;
            border: none;
            padding: 13px;
            border-radius: 5px;
            color: #fff;
        }

        form h3{

        }

        .feedback__label{
            padding: 10px 20px;
            border: 1px solid #ccc;
            margin-right: 10px;
            border-radius: 5px;
            cursor: pointer;

        }

        .feedback__label:hover{
            background: #ccc;
            color: #000;
            cursor: pointer;
            transition: all 300ms linear;
            box-shadow: 0 0 3px #ccc;
        }

        @page {
            size: auto;
            margin: 30px;
        }

        @media print {
            body {
                font-size: 14pt;
                font-family: "Times New Roman";
            }
            form {
                display: none !important;
            }
            .alert{
                display: none;
            }
        }

        .alert-danger{
            background-color: rgba(255, 0, 0, 0.86);
            color: #fff;
            width: 100%;
            padding: 20px;
            text-align: center;
        }

        .row{
            display: flex;
            align-items: center;
        }
        .print-btn{
            padding: 10px 20px;
            margin-top: 10px;
            background-color: #40DC75;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

    </style>

</head>

<body>
        @if(Session::has('message'))
            <h3 class="alert alert-danger text-center">{{ session('message') }}</h3>
{{--            <h3 class="alert alert-danger text-center">{{ __('billing/order.' + session('message')) }}</h3>--}}
        @endif
        <section class="container" id="page-content">
            <div class="content">
                <h3 class="content-text">
                    Директору {{ $contract->generalCompany->name_ru ?? '' }}
                    <br>
                    {{ $contract->generalCompany->director_ru ?? '' }}
                </h3>
                <div>
                    <p>Я {{ $user->name }}&nbsp{{ $user->surname }}&nbsp{{ $user->patronymic }}</p>
                    <p>(Паспорт {{ \App\Helpers\EncryptHelper::decryptData($contract->buyer->personals->passport_number) }})</p>
                    <p>Номер телефона / ID  @if(isset($user->phone)) +{{ preg_replace('/[^0-9]/', '', $user->phone) }} @else {{ $user->id }} @endif</p>
                    <p>прошу Вас отменить договор №{{ $contract->id}} от {{$contract->confirmed_at}}г по</p>
                    <p>следующей причине: </p><p style="word-break: break-word">{{ $contract->contract_cancellation_reason }}</p>
                </div>

                <div>
                    <p>Покупатель: {{ $user->name }}&nbsp{{ $user->surname }}&nbsp{{ $user->patronymic }} __________ (подпись)</p>

                    <p>Партнер: {{$contract->company->name}} __________ (подпись)</p>

                    <p>Дата: {{date('Y-m-d')}}</p>

                </div>


                <form class='form'  action="{{ localeRoute('billing.orders.contract_cancellation') }}" method="post" enctype="multipart/form-data">
                    <button class="print-btn">Распечатать</button>

                    <h3>Загрузите заполненный акт</h3>

                    @csrf
                    <div class="row">
                        <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                        {{--                    <span class="feedback__text">Загрузить акт отмены</span>--}}
                        <input class='form-input' type="file" name="image" id="file">

                        <button class="btn-orange text-orange" type="submit">{{__('billing/order.send')}}</button>
                    </div>
                </form>

            </div>

        </section>

        <script>
            window.addEventListener('DOMContentLoaded', (e) => {
                const btn = document.querySelector(".print-btn");
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.print()
                })
            })

        </script>
</body>
</html>
