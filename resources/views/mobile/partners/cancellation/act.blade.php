<!doctype html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
  <style>
    @import url("https://test.uz/assets/fonts/fonts.css");

    p, h1, h2, h3 h4, h5 {
      margin: 0;
      padding: 0;
    }

    html, body {
      font-family: 'Gilroy', sans-serif;
      box-sizing: border-box;
      padding: 0;
      margin: 0;
      font-size: 16px;
      text-align: justify;
      line-height: 40px;
      background-color: #F6F6F6;
    }

    .container-mobile {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .content-text {
      text-align: center;
      font-style: normal;
      font-weight: 700;
      font-size: 16px;
      line-height: 17px;
    }

    .content p {
      font-style: normal;
      font-weight: 500;
      font-size: 14px;
      line-height: 1.5;
    }

    .sign-area {
      display: flex;
      align-items: flex-end;
      width: 100px;
      height: 80px;
      position: relative;
    }

    .sign-area .signature {
      position: absolute;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 2;

    }

  </style>

</head>

<body>
<section class="container-mobile" id="page-content">
  <div class="content">
    <h3 class="content-text">
      Директору {{ $contract->generalCompany->name_ru ?? '' }}
      <br>
      {{ $contract->generalCompany->director_ru ?? '' }}
    </h3>
    <div>
      <p>Я {{ $user->name }}&nbsp{{ $user->surname }}&nbsp{{ $user->patronymic }}</p>
      <p>(Паспорт {{ App\Helpers\EncryptHelper::decryptData($contract->buyer->personals->passport_number) }})</p>
      <p>Номер телефона / ID @if(isset($user->phone))
          +{{ correct_phone($user->phone) }}
        @else
          {{ $user->id }}
        @endif</p>
      <p>прошу Вас отменить договор №{{ $contract->id}} от {{$contract->confirmed_at}}г по</p>
      <p>следующей причине: </p>
      <p style="word-break: break-word">{{ $contract->contract_cancellation_reason }}</p>
    </div>

    <div>
      <p>Покупатель: {{ $user->name }}&nbsp{{ $user->surname }}&nbsp{{ $user->patronymic }}
        @if(!empty($clientSignaturePath))

          <span class="sign-area">
                        _______________
                      <img class="signature"
                           src="{{$clientSignaturePath}}"
                           alt="client-sign">
                    </span>
        @endif
      </p>

      <p>Партнер: {{ $contract->company->name}}
        @if(!empty($generalSignaturePath))

          <span class="sign-area">
                        _______________
                      <img class="signature"
                           src="{{$generalSignaturePath}}"
                           alt="client-sign">
                    </span>
        @endif
      </p>

      <p>Дата: {{date('Y-m-d')}}</p>

    </div>
  </div>
</section>
</body>
</html>
