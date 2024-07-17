<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>&nbsp;</title>
</head>
<body>

<div class="contract-block">

    {{-- Латинский --}}
    <div class="first-page" style="height: 1000px;">
        <div class="offer order-block">
            <div style="text-align: right">
                {{ $order->created_at }} dagi № {{ $order->contract->id }} – sonli Oferta hamda Ommaviy Ofertaning
                1-Ilovasi
            </div>

            <div class="title">
                <p style="margin: 0; line-height: 2px;">Maxsulotlarni topshirish va qabul qilish</p>
                <span>DALOLATNOMASI</span>
            </div>

            <header>
                <div class="region">
                    Toshkent sh
                </div>
                <div class="date">{{ $order->created_at }} y.</div>
            </header>

            <div class="offer-text">

                {{ $order->company->generalCompany->name_uzlat ?? '' }} nomidan {{ $order->created_at }} dagi № {{ $order->contract->id }} – sonli sonli
                ishonchnomaga asosan ish yurituvchi {{ $order->company->name ?? '_______' }}, bundan so’ng matnda “Sotuvchi” deb ataladigan bir tomondan va
                {{ $order->buyer->addressRegistration->string??'---' }} da doimiy yashovchi {{ $order->buyer->fio }}
                ({{ \App\Helpers\EncryptHelper::decryptData($order->buyer->personals->passport_number)?:'_______' }}
                {{ \App\Helpers\EncryptHelper::decryptData($order->buyer->personals->passport_date_issue)?:'_______' }}
                yilda berilgan)
                bundan so’ng «Xaridor», birgalikda “Tomonlar”, alohida esa “Tomon” deb ataluvchi tuzdik ushbu
                dalolatnomani shu haqdakim, Sotuvchi quyida keltirilgan maxsulotni yetkazib berganligi hamda xaridor
                quyida keltirilgan jadval asosida maxsulotni qabul qilganligi to’g’risida:
            </div>

            <div class="products">
                <table cellpadding="0" cellspacing="0">
                    <thead>
                    <tr>
                        <th>№</th>
                        <th>Maxsulot nomi va tavsifi</th>
                        <th><span class="d-none d-md-inline">Soni (dona)</span></th>
                        <th>
                            <span class="d-none d-md-inline">
                                Umumiy narxi (bo’lib bo’lib to’lash inobatga olingan holda,
                                shartnomaning umumiy shartlariga muvofiq 1 dona maxsulot QQS bilan)
                            </span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    @for($i = 0; $i < count($order->products); $i++)
                        <tr>
                            <td>{{$i+1}}</td>
                            <td>{{ $order->products[$i]->original_name != null || $order->products[$i]->original_name != '' ? $order->products[$i]->original_name : $order->products[$i]->name }}</td>
                            <td class="amount">x {{$order->products[$i]->amount }}</td>
                            <td>
                                <div class="total">
                                    {{$order->products[$i]->price * $order->products[$i]->amount}}
                                </div>
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td colspan="4" style="font-weight: bold;text-align: right">
                            Umumiy summa miqdori: {{ $order->contract->total + $order->contract->deposit }} QQS bilan .
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            {{--    {!! __('account.text_first') !!}--}}
            <div>
                <ol>
                    <li>Shartnoma asosida Sotuvchi tomonidan Xaridorga maxsulot to’liq va sifatli yetkazib berildi.</li>
                    <li>
                        Xaridor bu bilan quyidagi to’lov jadvaliga asosan to’lov qilishni tasdiqlaydi va o’z zimmasiga
                        oladi.
                    </li>
                </ol>
            </div>

            @php
                $deposit = $order->contract->deposit;
                $schedule = $order->contract->schedule;
                $count = count($schedule);
                if ($count === 9) {
                    $firstHalf = collect($schedule->chunk(6)->toArray()[0]);
                    $secondHalf = collect($schedule->chunk(6)->toArray()[1]);
                } elseif ($count === 3) {
                    $firstHalf = collect($schedule->chunk(3)->toArray()[0]);
                    $secondHalf = null;
                }elseif ($count === 1) {
                    $firstHalf = collect($schedule->toArray());
                    $secondHalf = null;
                } else {
                    $half = $count / 2;
                    $firstHalf = collect($schedule->chunk($half)->toArray()[0]);
                    $secondHalf = collect($schedule->chunk($half)->toArray()[1]);
                }
                $total = $order->contract->total;
            @endphp

            <div class="payments {{ $count === 6 ? 'h-60px' : 'h-80px' }}">
                @if($firstHalf)
                    <table class="first-half" width="49%" cellpadding="0" cellspacing="0" style="margin-right: 10px">
                        <thead>
                        <tr>
                            <th>№</th>
                            <th>To’lov sanasi</th>
                            <th>To’lov summasi (so’mda, QQS bilan)</th>
                            <th>To’lov qoldig’i</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($firstHalf && $deposit > 0)
                            <tr>
                                <td>{{0}}</td>
                                <td>{{date( 'Y-m-d', time())}}</td>
                                <td>{{$deposit}}</td>
                                <td>{{(int)$total}}</td>
                            </tr>
                        @endif
                        @foreach($firstHalf as $index => $payment)
                            @php
                                $total -= $payment['total'];
                                if($total < 0) $total = 0;
                            @endphp
                            <tr>
                                <td>{{$index + 1}}</td>
                                <td>{{date( 'Y-m-d', strtotime( $payment['payment_date'] ) )}}</td>
                                <td>{{$payment['total']}}</td>
                                <td>{{(int)$total}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif

                @if($secondHalf)
                    <table class="second-half" width="49%" cellpadding="0" cellspacing="0">
                        <thead>
                        <tr>
                            <th>№</th>
                            <th>To’lov sanasi</th>
                            <th>To’lov summasi (so’mda, QQS bilan)</th>
                            <th>To’lov qoldig’i</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($secondHalf as $index => $payment)
                            @php
                                $total -= $payment['total'];
                                if($total < 0) $total = 0;
                            @endphp
                            <tr>
                                <td>{{$index + 1}}</td>
                                <td>{{ date('Y-m-d', strtotime($payment['payment_date'])) }}</td>
                                <td>{{$payment['total']}}</td>
                                <td>{{(int)$total}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div>
                {{--        {!! __('account.text_second') !!}--}}
                <ol start="3">
                    <li>
                        Xaridor bu bilan shartnoma shartlari bilan tanishganligi (Xaridorning telefon raqamiga oferta
                        bilan tanishganligi to’g’risida yuborilgan SMS xabardagi havola orqali) va uning shartlarini
                        so’zsiz qabul qiladi. Xaridor shuningdek ushbu, dalolatnoma shartnomaning bir qismi ekanligi,
                        hamda u bilan maxsulotni qismlarga bo’lib bo’lib to’lash asosisda shartnoma tuzilganligini
                        anglaydi va tasdiqlaydi.
                    </li>
                    <li>
                        Xaridor o’zining shaxsiy ma’lumotlarini va ushbu dalolatnoma tafsilotlarini uchinchi shaxslarga
                        o’tkazilish va almashishiga to’liq roziligini bildiradi.
                    </li>
                    <li>
                        Ushbu dalolatnomada keltirilgan jadval asosida ko’rsatilgan to’lovlarni to’lamagan yoki <strong>60 kundan ko’p muddatga</strong> kechiktirgan taqdirda, Xaridor jadvalda ko’rsatilgan to’lovlarni barchasini ushbu muddatga ko’chirgan holda (shartnoma bo’yicha barcha qarzdorlikni to’lash majburiyati) o’zini qora ro’yxatga yoki to’lamaydiganlar ro’yxatiga qo’shishga rozilik beradi va bu ro’yxat keng ommaga oshkor etiladi.
                    </li>
                    <li>
                        Agar Xaridor to’lovlarni o’z vaqtida to’lamasdan u tomonidan tegishli tartibda qarzni to’lash
                        majburiyati bajarilmasa, Sotuvchi kechiktirilgan to’lovlar uchun qarzlar hamda muddatidan oldin
                        bo’lib bo’lib to’lash orqali olingan maxsulotning umumiy qarzdorligini undirish notariuslar
                        tomonidan tuzilgan ijro varaqasini olish orqali amalga oshiradi.
                    </li>
                    <li>
                        Muddatidan oldin bo’lib bo’lib to’lash orqali olingan maxsulotning umumiy qarzdorligini undirish
                        bilan bog’liq barcha xarajatlar Xaridor tomonidan qoplanadi.
                    </li>
                    <li>
                        Tomonlar Tovarning sifati, qadoqlanishi, yaxlitligi bilan bog’liq savollar va e’tirozlar
                        ushbu dalolatnoma imzo qo’yilishidan oldin hal qilinganligini va Sotuvchi tomonidan olingan
                        Tovarga nisbatan boshqa e’tirozlar qabul qilinmasligini tasdiqlaydi.
                    </li>
                    <li>
                        Mazkur Dalolatnoma Xaridorga tushunarli tilda 2 (ikki) nusxada tuzilgan bo’lib, ulardan biri
                        sotuvchida, ikkinchisi esa Xaridorda bo’ladi.
                    </li>
                </ol>
            </div>

            <div class="participants">

                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="part">
                            <div style="font-weight: bold;">
                                Topshirdi <br>
                                Sotuvchi tomonidan
                            </div>
                            <table class="sign__field">
                                <tr>
                                    <td class="checkmark" style="vertical-align: middle">V</td>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td style="vertical-align: middle">
                                            <img src="{{ $order->company->generalCompany->sign }}">
                                        </td>
                                        @else
                                        <td class="bordered">
                                            <img src="">
                                        </td>
                                    @endif
                                    <td style="vertical-align: middle">(Imzo)</td>
                                </tr>
                            </table>
                        </td>

                        <td class="part">
                            <div style="font-weight: bold;">
                                @if(isset($act_type) and $act_type == 'act_with_qr')
                                    Qabul qildi <br>
                                    Xaridor tomonidan
                                @else
                                    Qabul qildi <br>
                                    Xaridor tomonidan tanishib chiqildi
                                @endif
                            </div>
                            <table class="sign__field">
                                <tr>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td class="checkmark">&nbsp;</td>
                                        <td>
                                            <img src="{{ asset('images/public_oferta_qr.jpg')}}" alt="qr-sign" style="width: 50px; height: 50px; margin-top: 10px">
                                        </td>
                                        <td style="vertical-align: bottom">&nbsp;</td>
                                    @else
                                        <td class="checkmark">V</td>
                                        <td class="bordered">
                                            <img alt="image">
                                        </td>
                                        <td style="vertical-align: bottom">(Имзо)</td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </div><!-- /.participants -->
        </div>

        <div class="reconciliation-block">
            <div class="header-bottom">
                <p style="font-weight: bold;">TO’LOVLARNI SOLISHTIRISH DALOLATNOMASI</p>
            </div>

            <div>
                {{ date('Y') }} yil holatiga {{ $order->company->generalCompany->name_uzlat ?? '' }} va {{ $order->buyer->fio }}
                {{ $order->created_at }} № {{ $order->contract->id }} Oferta asosida
            </div>

            <div class="act">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr>
                        <td rowspan="2">Sana</td>
                        <td rowspan="2">Operatsiyalar</td>
                        <td colspan="2">{{ $order->company->generalCompany->name_uzlat ?? '' }}</td>
                        <td colspan="2">{{ $order->buyer->fio }}</td>
                    </tr>
                    <tr>
                        <td>Debet</td>
                        <td>Kredit</td>
                        <td>Debet</td>
                        <td>Kredit</td>
                    </tr>
                    <tr bgcolor="#e6e6fa">
                        <td colspan="2" style="font-weight: bold;">Сальдо
                            на {{ date('d.m.Y',strtotime(date('Y-01-01'))) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @if($deposit > 0)
                        <tr>
                            <td>{{ $order->created_at }}</td>
                            <td>Boshlang`ich to`lov</td>
                            <td></td>
                            <td>{{ $order->contract->deposit }}</td>
                            <td>{{ $order->contract->deposit }}</td>
                            <td></td>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ $order->created_at }}</td>
                        <td>Maxsulotlar realizatsiyasi <br> (narxlar QQS bilan birga); Tushum qayd etildi.</td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                        <td></td>
                        <td></td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                    </tr>
                    <tr>
                        <td colspan="2">Обороты за период</td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                        <td>{{ $order->contract->deposit > 0 ? $order->contract->deposit : "" }}</td>
                        <td>{{ $order->contract->deposit > 0 ? $order->contract->deposit : "" }}</td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                    </tr>
                    <tr bgcolor="#e6e6fa">
                        <td colspan="2" style="font-weight: bold;">Сальдо на {{ $order->created_at }}.</td>
                        <td style="font-weight: bold;">{{ $order->contract->total }}</td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold;">{{ $order->contract->total }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div>
                {{ $order->company->generalCompany->name_uzlat ?? '' }}
                foydasiga {{ $order->contract->total + $order->contract->deposit }} so’m
                {{--                ({{ Str::ucfirst(num2str($order->contract->total + $order->contract->deposit)) }})--}}
                {{--        (Два миллиона восемьсот тысяч сум 00 тийин) <--todo: дороботать надо --}}
            </div>
            <br>

            <div class="participants">

                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="part">
                            <div
                                style="font-weight: bold;">{{ $order->company->generalCompany->name_uzlat ?? '' }}</div>

                            <table class="sign__field">
                                <tr>
                                    <td class="checkmark" style="vertical-align: middle">V</td>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td style="vertical-align: middle">
                                            <img src="{{ $order->company->generalCompany->sign }}">
                                        </td>
                                    @else
                                        <td class="bordered">
                                            <img src="">
                                        </td>
                                    @endif
                                    <td style="vertical-align: middle">(Imzo)</td>
                                </tr>
                            </table>
                        </td>

                        <td class="part">
                            <div style="font-weight: bold;">{{ $order->buyer->fio }}</div>

                            <table class="sign__field">
                                <tr>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td class="checkmark">&nbsp;</td>
                                        <td>
                                            <img src="{{ asset('images/public_oferta_qr.jpg')}}" alt="qr-sign" style="width: 50px; height: 50px; margin-top: 10px;">
                                        </td>
                                        <td style="vertical-align: bottom">&nbsp;</td>
                                    @else
                                        <td class="checkmark">V</td>
                                        <td class="bordered"> <img alt="image"></td>
                                        <td style="vertical-align: bottom">(Имзо)</td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </div><!-- /.participants -->

        </div>
    </div>

    {{-- Кирилица --}}
    <div class="second-page" style="height: 1000px;">
        <div class="offer order-block">
            <div style="text-align: right">
                {{ $order->created_at }} даги № {{ $order->contract->id }} – сонли Оферта ҳамда Оммавий офертанинг
                1-Иловаси
            </div>

            <div class="title">
                <p style="margin: 0; line-height: 2px;">Махсулотларни топшириш ва қабул қилиш</p>
                <span>ДАЛОЛАТНОМАСИ </span>
            </div>

            <header>
                <div class="region">
                    Тошкент ш
                </div>
                <div class="date">{{ $order->created_at }} й.</div>
            </header>

            <div class="offer-text">
                {{ $order->company->generalCompany->name_uz ?? '' }} номидан {{ $order->created_at }} даги № {{ $order->contract->id }} – сонли
                ишончномага асосан иш юритувчи {{ $order->company->name ?? '_______' }}, бундан сўнг матнда “Сотувчи” деб аталадиган бир томондан ва
                {{ $order->buyer->addressRegistration->string??'---' }} да доимий яшовчи {{ $order->buyer->fio }}
                ({{ \App\Helpers\EncryptHelper::decryptData($order->buyer->personals->passport_number)?:'_______' }}
                {{ \App\Helpers\EncryptHelper::decryptData($order->buyer->personals->passport_date_issue)?:'_______' }}
                йилда берилган)
                бундан сўнг «Харидор», биргаликда “Томонлар”, алохида эса “Томон” деб аталувчи туздик ушбу далолатномани
                шу ҳақдаким, Сотувчи қуйида келтирилган махсулотни етказиб берганлиги ҳамда харидор қуйида келтирилган
                жадвал асосида махсулотни қабул қилганлиги тўғрисида:
            </div>

            <div class="products">
                <table cellpadding="0" cellspacing="0">
                    <thead>
                    <tr>
                        <th>№</th>
                        <th>Махсулот номи ва тавсифи</th>
                        <th><span class="d-none d-md-inline">Сони (дона)</span></th>
                        <th>
                            <span class="d-none d-md-inline">
                                Умумий нархи (бўлиб бўлиб тўлаш инобатга олинган ҳолда, шартноманинг умумий шартларига
                                мувофиқ 1 дона махсулот ҚҚС билан)
                            </span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>

                    @for($i = 0; $i < count($order->products); $i++)
                        <tr>
                            <td>{{$i+1}}</td>
                            <td>{{ $order->products[$i]->original_name != null || $order->products[$i]->original_name != '' ? $order->products[$i]->original_name : $order->products[$i]->name }}</td>
                            <td class="amount">x {{$order->products[$i]->amount }}</td>
                            <td>
                                <div class="total">
                                    {{$order->products[$i]->price * $order->products[$i]->amount}}
                                </div>
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td colspan="4" style="font-weight: bold;text-align: right">
                            Умумий сумма миқдори: {{ $order->contract->total + $order->contract->deposit }} ҚҚС билан.
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            {{--    {!! __('account.text_first') !!}--}}
            <div>
                <ol>
                    <li>Шартнома асосида Сотувчи томонидан Харидорга махсулот тўлиқ ва сифатли етказиб берилди.</li>
                    <li>
                        Харидор бу билан қуйидаги тўлов жадвалига асосан тўлов қилишни тасдиқлайди ва ўз зиммасига
                        олади.
                    </li>
                </ol>
            </div>

            @php
                $deposit = $order->contract->deposit;
                $schedule = $order->contract->schedule;
                $count = count($schedule);
                if ($count === 9) {
                    $firstHalf = collect($schedule->chunk(6)->toArray()[0]);
                    $secondHalf = collect($schedule->chunk(6)->toArray()[1]);
                } elseif ($count === 3) {
                    $firstHalf = collect($schedule->chunk(3)->toArray()[0]);
                    $secondHalf = null;
                }elseif ($count === 1) {
                    $firstHalf = collect($schedule->toArray());
                    $secondHalf = null;
                } else {
                    $half = $count / 2;
                    $firstHalf = collect($schedule->chunk($half)->toArray()[0]);
                    $secondHalf = collect($schedule->chunk($half)->toArray()[1]);
                }
                $total = $order->contract->total;
            @endphp

            <div class="payments {{ $count === 6 ? 'h-60px' : 'h-80px' }}">
                @if($firstHalf)
                    <table class="first-half" width="49%" cellpadding="0" cellspacing="0" style="margin-right: 10px">
                        <thead>
                        <tr>
                            <th>№</th>
                            <th>Тўлов санаси</th>
                            <th>Тўлов суммаси (сўмда, ҚҚС билан)</th>
                            <th>Тўлов қолдиғи</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($firstHalf && $deposit > 0)
                            <tr>
                                <td>{{0}}</td>
                                <td>{{date( 'Y-m-d', time())}}</td>
                                <td>{{$deposit}}</td>
                                <td>{{(int)$total}}</td>
                            </tr>
                        @endif
                        @foreach($firstHalf as $index => $payment)
                            @php
                                $total -= $payment['total'];
                                if($total < 0) $total = 0;
                            @endphp
                            <tr>
                                <td>{{$index + 1}}</td>
                                <td>{{date( 'Y-m-d', strtotime( $payment['payment_date'] ) )}}</td>
                                <td>{{$payment['total']}}</td>
                                <td>{{(int)$total}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif

                @if($secondHalf)
                    <table class="second-half" width="49%" cellpadding="0" cellspacing="0">
                        <thead>
                        <tr>
                            <th>№</th>
                            <th>Тўлов санаси</th>
                            <th>Тўлов суммаси (сўмда, ҚҚС билан)</th>
                            <th>Тўлов қолдиғи</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($secondHalf as $index => $payment)
                            @php
                                $total -= $payment['total'];
                                if($total < 0) $total = 0;
                            @endphp
                            <tr>
                                <td>{{$index + 1}}</td>
                                <td>{{ date('Y-m-d', strtotime($payment['payment_date'])) }}</td>
                                <td>{{$payment['total']}}</td>
                                <td>{{(int)$total}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div>
                {{--        {!! __('account.text_second') !!}--}}
                <ol start="3">
                    <li>
                        Харидор бу билан шартнома шартлари билан танишганлиги (Харидорнинг телефон рақамига оферта
                        билан танишганлиги тўғрисида юборилган СМС хабардаги ҳавола орқали) ва унинг шартларини сўзсиз
                        қабул қилади. Харидор шунингдек, ушбу далолатнома шартноманинг бир қисми эканлигини, ҳамда у
                        билан махсулотни қисмларга бўлиб бўлиб тўлаш асосида шартнома тузилганлигини англайди ва
                        тасдиқлайди.
                    </li>
                    <li>
                        Харидор ўзининг шахсий маълумотларини ва ушбу далолатнома тафсилотларини учинчи шахсларга
                        ўтказилиши ва алмашишига тўлиқ розилигини билдиради.
                    </li>
                    <li>
                        Ушбу далолатномада келтирилган жадвал асосида кўрсатилган тўловларни тўламаган ёки <strong>60 кундан кўп муддатга</strong> кечиктирган тақдирда, Харидор жадвалда кўрсатилган тўловларни барчасини ушбу муддатга кўчирган ҳолда (шартнома бўйича барча қарздорликни тўлаш мажбурияти) ўзини қора рўйхатга ёки тўламайдиганлар рўйхатига қўшишга розилик беради ва бу рўйхат кенг оммага ошкор этилади.
                    </li>
                    <li>
                        Агар Харидор тўловларни ўз вақтида тўламасдан у томонидан тегишли тартибда қарзни тўлаш
                        мажбурияти бажарилмаса, Сотувчи кечиктирилган тўловлар учун қарзлар ҳамда муддатидан олдин бўлиб
                        бўлиб тўлаш орқали олинган махсулотнинг умумий қарздорлигини ундириш нотариуслар томонидан
                        тузилган ижро варақасини олиш орқали амалга оширади.
                    </li>
                    <li>
                        Муддатидан олдин бўлиб бўлиб тўлаш орқали олинган махсулотнинг умумий қарздорлигини ундириш
                        билан боғлиқ барча харажатлар Харидор томонидан қопланади.
                    </li>
                    <li>
                        Томонлар Товарнинг сифати, қадоқланиши, яхлитлиги билан боғлиқ саволлар ва эътирозлар ушбу
                        далолатнома имзо қўйилишидан олдин ҳал қилинганлигини ва Сотувчи томонидан олинган Товарга
                        нисбатан бошқа эътирозлар қабул қилинмаслигини тасдиқлайди.
                    </li>
                    <li>
                        Мазкур далолатнома Харидорга тушунарли тилда 2 (икки) нусхада тузилган бўлиб, улардан бири
                        сотувчида, иккинчиси эса Харидорда бўлади.
                    </li>
                </ol>
            </div>

            <div class="participants">

                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="part">
                            <div style="font-weight: bold;">
                                Топширди <br>
                                СОТУВЧИ томонидан
                            </div>
                            <table class="sign__field">
                                <tr>
                                    <td class="checkmark" style="vertical-align: middle">V</td>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td style="vertical-align: middle">
                                            <img src="{{ $order->company->generalCompany->sign }}">
                                        </td>
                                    @else
                                        <td class="bordered">
                                            <img src="">
                                        </td>
                                    @endif
                                    <td style="vertical-align: middle">(Имзо)</td>
                                </tr>
                            </table>
                        </td>

                        <td class="part">
                            <div style="font-weight: bold;">
                                @if(isset($act_type) and $act_type == 'act_with_qr')
                                    Қабул қилди <br>
                                    Харидор тамонидан танишиб чикилди
                                @else
                                    Қабул қилди <br>
                                    Харидор томонидан
                                @endif
                            </div>
                            <table class="sign__field">
                                <tr>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td class="checkmark">&nbsp;</td>
                                        <td>
                                            <img src="{{ asset('images/public_oferta_qr.jpg')}}" alt="qr-sign" style="width: 50px; height: 50px; margin-top: 10px;">
                                        </td>
                                        <td style="vertical-align: bottom">&nbsp;</td>
                                    @else
                                        <td class="checkmark">V</td>
                                        <td class="bordered"> <img alt="image"></td>
                                        <td style="vertical-align: bottom">(Имзо)</td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </div><!-- /.participants -->
        </div>

        <div class="reconciliation-block">
            <div class="header-bottom">
                <p style="font-weight: bold;">ТУЛОВЛАРНИ СОЛИШТИРИШ ДАЛОЛАТНОМАСИ</p>
            </div>

            <div>
                {{ date('Y') }} йил холатига {{ $order->company->generalCompany->name_uz ?? '' }} ва {{ $order->buyer->fio }}
                {{ $order->created_at }} № {{ $order->contract->id }} Оферта асосисда
            </div>

            <div class="act">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr>
                        <td rowspan="2">Сана</td>
                        <td rowspan="2">Операциялар</td>
                        <td colspan="2">{{ $order->company->generalCompany->name_uz ?? '' }}</td>
                        <td colspan="2">{{ $order->buyer->fio }}</td>
                    </tr>
                    <tr>
                        <td>Дебет</td>
                        <td>Кредит</td>
                        <td>Дебет</td>
                        <td>Кредит</td>
                    </tr>
                    <tr bgcolor="#e6e6fa">
                        <td colspan="2" style="font-weight: bold;">Сальдо
                            на {{ date('d.m.Y',strtotime(date('Y-01-01'))) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @if($deposit > 0)
                        <tr>
                            <td>{{ $order->created_at }}</td>
                            <td>Бошланғич тўлов</td>
                            <td></td>
                            <td>{{ $order->contract->deposit }}</td>
                            <td>{{ $order->contract->deposit }}</td>
                            <td></td>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ $order->created_at }}</td>
                        <td>Махсулотлар реализацияси <br> (нархлар ҚҚС билан бирга); Тушум қайд этилди.</td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                        <td></td>
                        <td></td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                    </tr>
                    <tr>
                        <td colspan="2">Обороты за период</td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                        <td>{{ $order->contract->deposit > 0 ? $order->contract->deposit : "" }}</td>
                        <td>{{ $order->contract->deposit > 0 ? $order->contract->deposit : "" }}</td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                    </tr>
                    <tr bgcolor="#e6e6fa">
                        <td colspan="2" style="font-weight: bold;">Сальдо на {{ $order->created_at }}.</td>
                        <td style="font-weight: bold;">{{ $order->contract->total }}</td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold;">{{ $order->contract->total }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div>
                {{ $order->company->generalCompany->name_uz ?? '' }} фойдасига {{ $order->contract->total + $order->contract->deposit }} сум
                {{--                ({{ Str::ucfirst(num2str($order->contract->total + $order->contract->deposit)) }})--}}
                {{--        (Два миллиона восемьсот тысяч сум 00 тийин) <--todo: дороботать надо --}}
            </div>
            <br>

            <div class="participants">

                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="part">
                            <div style="font-weight: bold;">{{ $order->company->generalCompany->name_uz ?? '' }}</div>
                            <table class="sign__field">
                                <tr>
                                    <td class="checkmark" style="vertical-align: middle">V</td>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td style="vertical-align: middle">
                                            <img src="{{ $order->company->generalCompany->sign }}">
                                        </td>
                                    @else
                                        <td class="bordered">
                                            <img src="">
                                        </td>
                                    @endif
                                    <td style="vertical-align: middle">(Имзо)</td>
                                </tr>
                            </table>
                        </td>

                        <td class="part">
                            <div style="font-weight: bold;">{{ $order->buyer->fio }}</div>

                            <table class="sign__field">
                                <tr>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td class="checkmark">&nbsp;</td>
                                        <td>
                                            <img src="{{ asset('images/public_oferta_qr.jpg')}}" alt="qr-sign" style="width: 50px; height: 50px; margin-top: 10px;">
                                        </td>
                                        <td style="vertical-align: bottom">&nbsp;</td>
                                    @else
                                        <td class="checkmark">V</td>
                                        <td class="bordered"> <img alt="image"></td>
                                        <td style="vertical-align: bottom">(Имзо)</td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </div><!-- /.participants -->

        </div>
    </div>

    {{-- Русский --}}
    <div class="third-page" style="height: 980px">
        <div class="offer order-block">
            <div style="text-align: right; @if(count($order->products) === 1 ) margin-top: 10px; @endif">
                Приложение №1 к Оферте № {{ $order->contract->id }} от {{ $order->created_at }} года и Публичной оферте.
                {{--        {{ __('account.header_title', [--}}
                {{--            'number' => 1,--}}
                {{--            'offer_id' => ,--}}
                {{--            'company_name' => $order->company->name,--}}
                {{--            'date' => --}}
                {{--        ]) }}--}}
            </div>

            <div class="title">
                А К Т приема-передачи Товара
                {{--        {!! __('account.header') !!}--}}
            </div>

            <header>
                <div class="region">
                    г.Ташкент
                    {{--            {{ __('account.region') }}--}}
                </div>
                <div class="date">{{ $order->created_at }} г.</div>
            </header>

            <div class="offer-text">
                {{ $order->company->generalCompany->name_ru ?? '' }}, именуемое в дальнейшем «Продавец»,
                в лице директора {{ $order->company->name ?? '' }}, действующего на основании доверенности
                №{{ $order->company->uniq_num ?? '__' }}
                от {{ date('d.m.Y', strtotime($order->company->date_pact ?? '')) }} года,
                с одной Стороны, и гражданин(ка) {{ $order->buyer->fio }}
                ({{ \App\Helpers\EncryptHelper::decryptData($order->buyer->personals->passport_number)?:'_______' }}
                от {{ \App\Helpers\EncryptHelper::decryptData($order->buyer->personals->passport_date_issue)?:'_______' }}
                г.),
                проживающий по адресу: {{ $order->buyer->addressRegistration->string??'---' }},
                именуемый в дальнейшем «Покупатель»,
                с другой Стороны, вместе именуемые как Стороны, а по отдельности - Сторона,
                составили настоящий акт о том, что Продавец поставил,
                а Покупатель получил перечень товаров (далее – Товар) согласно нижеследующей таблице:
                {{--        {{__('account.txt_1', [--}}
                {{--            'vendor' => $order->company->name,--}}
                {{--            'buyer' => $order->buyer->fio,--}}
                {{--            'offer' => $order->id,--}}
                {{--            'date' => $order->created_at,--}}
                {{--            'address' => $order->buyer->addressRegistration->string??'---',--}}
                {{--            'passport_date_issue' => \App\Helpers\EncryptHelper::decryptData($order->buyer->personals->passport_date_issue)?:'---',--}}
                {{--            'passport_number' => \App\Helpers\EncryptHelper::decryptData($order->buyer->personals->passport_number??'')--}}
                {{--        ])}}--}}
            </div>

            <div class="products">
                <table cellpadding="0" cellspacing="0">
                    <thead>
                    <tr>
                        <th>№</th>
                        <th>Наименование и описание товара</th>
                        <th><span class="d-none d-md-inline">Количество в ед.изм.Шт.</span></th>
                        {{--                <th class="d-none d-md-table-cell">{{__('account.docs')}}</th>--}}
                        {{--                <th class="d-none d-md-table-cell">--}}
                        {{--                    {{ __('account.product_price') }}--}}
                        {{--                </th>--}}
                        {{--                <th class="d-none d-md-table-cell">{{__('offer.nds')}}</th>--}}
                        <th><span class="d-none d-md-inline">Общая стоимость с учетом рассрочки (в соответствии с условиями Договора за единицу Товара, сум с НДС)</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>

                    @for($i = 0; $i < count($order->products); $i++)
                        <tr>
                            <td>{{$i+1}}</td>
                            <td>{{ $order->products[$i]->original_name != null || $order->products[$i]->original_name != '' ? $order->products[$i]->original_name : $order->products[$i]->name }}</td>
                            {{--                    <td class="d-none d-md-table-cell">{{__('offer.piece')}}</td>--}}
                            <td class="amount">x {{$order->products[$i]->amount }}</td>
                            {{--                    <td>---------</td>--}}
                            {{--                    <td class="d-none d-md-table-cell">{{round($order->products[$i]->price/1.15, 2) }}</td>--}}
                            {{--                    <td class="d-none d-md-table-cell">{{$nds*100}} %</td>--}}
                            <td>
                                <div class="total">
                                    {{$order->products[$i]->price * $order->products[$i]->amount}}
                                </div>
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td colspan="4" style="font-weight: bold;text-align: right">
                            Итого на общую сумму: {{ $order->contract->total + $order->contract->deposit }} сум с НДС.
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            {{--    {!! __('account.text_first') !!}--}}
            <div>
                <ol>
                    <li>Товар Продавцом по договору поставлен Покупателю в полном объёме и надлежащего качества.</li>
                    <li>Покупатель настоящим подтверждает и обязуется оплачивать сумму приобретенного Товара, согласно
                        нижеприведенному графику платежей:
                    </li>
                </ol>
            </div>

            @php
                $deposit = $order->contract->deposit;
                $schedule = $order->contract->schedule;
                $count = count($schedule);
                if ($count === 9) {
                    $firstHalf = collect($schedule->chunk(6)->toArray()[0]);
                    $secondHalf = collect($schedule->chunk(6)->toArray()[1]);
                } elseif ($count === 3) {
                    $firstHalf = collect($schedule->chunk(3)->toArray()[0]);
                    $secondHalf = null;
                }elseif ($count === 1) {
                    $firstHalf = collect($schedule->toArray());
                    $secondHalf = null;
                } else {
                    $half = $count / 2;
                    $firstHalf = collect($schedule->chunk($half)->toArray()[0]);
                    $secondHalf = collect($schedule->chunk($half)->toArray()[1]);
                }
                $total = $order->contract->total;
            @endphp

            <div class="payments {{ $count === 6 ? 'h-60px' : 'h-80px' }}">
                @if($firstHalf)
                    <table class="first-half" width="48%" cellpadding="0" cellspacing="0" style="margin-right: 10px">
                        <thead>
                        <tr>
                            <th>№</th>
                            <th>Дата платежа</th>
                            <th>Сумма Платежа (сум, с НДС)</th>
                            <th>Остаток платежа</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($firstHalf && $deposit > 0)
                            <tr>
                                <td>{{0}}</td>
                                <td>{{date( 'Y-m-d', time())}}</td>
                                <td>{{$deposit}}</td>
                                <td>{{(int)$total}}</td>
                            </tr>
                        @endif
                        @foreach($firstHalf as $index => $payment)
                            @php
                                $total -= $payment['total'];
                                if($total < 0) $total = 0;
                            @endphp
                            <tr>
                                <td>{{$index + 1}}</td>
                                <td>{{date( 'Y-m-d', strtotime( $payment['payment_date'] ) )}}</td>
                                <td>{{$payment['total']}}</td>
                                <td>{{(int)$total}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif

                @if($secondHalf)
                    <table class="second-half" width="50%" cellpadding="0" cellspacing="0">
                        <thead>
                        <tr>
                            <th>№</th>
                            <th>Дата платежа</th>
                            <th>Сумма Платежа (сум, с НДС)</th>
                            <th>Остаток платежа</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($secondHalf as $index => $payment)
                            @php
                                $total -= $payment['total'];
                                if($total < 0) $total = 0;
                            @endphp
                            <tr>
                                <td>{{$index + 1}}</td>
                                <td>{{ date('Y-m-d', strtotime($payment['payment_date'])) }}</td>
                                <td>{{$payment['total']}}</td>
                                <td>{{(int)$total}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div>
                {{--        {!! __('account.text_second') !!}--}}
                <ol start="3">
                    <li>Покупатель настоящим подтверждает, что ознакомлен с условиями договора (оферты полученной путем
                        направления СМС со ссылкой, на номер телефона Покупателя) и принимает его условия безоговорочно.
                        Покупатель также настоящим осознает и подтверждает, что им заключен договор купли-продажи в
                        рассрочку
                        товаров, и настоящий акт является частью этого договора.
                    </li>
                    <li>Покупатель дает полное согласие передавать или делиться своими личными данными и деталями
                        данного
                        Акта с
                        третьими лицами.
                    </li>
                    <li>В случае неуплаты или просрочки платежей <strong>свыше 60 дней</strong>, указанных в таблице данного Акта, Покупатель дает свое согласие на добавление себя в черный список или список неплательщиков с переносом всех предстоящих платежей по графику на данную дату (обязательство по выплате всей задолженности по договору). Доступ к данному списку будет публичным.
                    </li>
                    <li>Если со стороны Покупателя не будет своевременно и надлежащим образом исполнено обязательство по
                        уплате
                        долга, Продавец имеет право взыскать имеющуюся сумму задолженности и досрочно всю оставшуюся по
                        рассрочке сумму за предоставленный Товар путём обращения к нотариусу за получением
                        исполнительного
                        листа.
                    </li>
                    <li>Все расходы, связанные с взысканием задолженности и с взысканием в досрочном порядке всей
                        оставшейся
                        суммы по рассрочке за предоставленный Товар несёт Покупатель.
                    </li>
                    <li>Стороны подтверждают, что все вопросы и претензии касательно качества, комплектации, целостности
                        Товара
                        урегулированы до момента подписания настоящего Акта и никакие последующие претензии касательно
                        полученного Товара Продавцом не принимаются.
                    </li>
                    <li>Настоящий Акт написан на понятном Покупателю языке составлен в 2 (двух) экземплярах, один из
                        которых
                        находится у Продавца, второй - у Покупателя.
                    </li>
                </ol>
            </div>

            <div class="participants">

                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="part">
                            <div style="font-weight: bold;">
                                Сдал <br>
                                От ПРОДАВЦА
                            </div>
                            <table class="sign__field">
                                <tr>
                                    <td class="checkmark" style="vertical-align: middle">V</td>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td style="vertical-align: middle">
                                            <img src="{{ $order->company->generalCompany->sign }}">
                                        </td>
                                    @else
                                        <td class="bordered">
                                            <img src="">
                                        </td>
                                    @endif
                                    <td style="vertical-align: middle">(Imzo)</td>
                                </tr>
                            </table>
                        </td>

                        <td class="part">
                            <div style="font-weight: bold;">
                                @if(isset($act_type) and $act_type == 'act_with_qr')
                                    Принял <br>
                                    Покупатель ознакомлен.
                                @else
                                    Принял <br>
                                    От ПОКУПАТЕЛЯ
                                @endif
                            </div>
                            <table class="sign__field">
                                <tr>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td class="checkmark">&nbsp;</td>
                                        <td>
                                            <img src="{{ asset('images/public_oferta_qr.jpg')}}" alt="qr-sign" style="width: 50px; height: 50px; margin-top: 10px;">
                                        </td>
                                        <td style="vertical-align: bottom">&nbsp;</td>
                                    @else
                                        <td class="checkmark">V</td>
                                        <td class="bordered"> <img alt="image"></td>
                                        <td style="vertical-align: bottom">(Имзо)</td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </div><!-- /.participants -->
        </div>

        <div class="reconciliation-block">
            {{--    <div style="text-align: right">--}}
            {{--        Приложение №2 к Оферте № {{ $order->contract->id }} от {{ $order->created_at }} года и Публичной оферте.--}}
            {{--        --}}{{--        {{ __('account.header_title', [--}}
            {{--        --}}{{--            'number' => 2,--}}
            {{--        --}}{{--            'offer_id' => $order->id,--}}
            {{--        --}}{{--            'company_name' => $order->company->name,--}}
            {{--        --}}{{--            'date' => $order->created_at--}}
            {{--        --}}{{--        ]) }}--}}
            {{--    </div>--}}

            <div class="header-bottom">
                <p style="font-weight: bold;">АКТ СВЕРКИ ВЗАИМОРАСЧЁТОВ</p>
            </div>

            <div>
                На период {{ date('Y') }} г. Между {{ $order->company->generalCompany->name_ru ?? '' }}
                и {{ $order->buyer->fio }} по Оферте
                № {{ $order->contract->id }}
                от {{ $order->created_at }} года
            </div>

            <div class="act">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr>
                        <td rowspan="2">Дата</td>
                        <td rowspan="2">Операции</td>
                        <td colspan="2">{{ $order->company->generalCompany->name_ru ?? '' }}</td>
                        <td colspan="2">{{ $order->buyer->fio }}</td>
                    </tr>
                    <tr>
                        <td>Дебет</td>
                        <td>Кредит</td>
                        <td>Дебет</td>
                        <td>Кредит</td>
                    </tr>
                    <tr bgcolor="#e6e6fa">
                        <td colspan="2" style="font-weight: bold;">Сальдо
                            на {{ date('d.m.Y',strtotime(date('Y-01-01'))) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @if($deposit > 0)
                        <tr>
                            <td>{{ $order->created_at }}</td>
                            <td>Первоначальный взнос</td>
                            <td></td>
                            <td>{{ $order->contract->deposit }}</td>
                            <td>{{ $order->contract->deposit }}</td>
                            <td></td>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ $order->created_at }}</td>
                        <td>Реализация товаров <br> (цены с НДС); Учтена выручка</td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                        <td></td>
                        <td></td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                    </tr>
                    <tr>
                        <td colspan="2">Обороты за период</td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                        <td>{{ $order->contract->deposit > 0 ? $order->contract->deposit : "" }}</td>
                        <td>{{ $order->contract->deposit > 0 ? $order->contract->deposit : "" }}</td>
                        <td>{{ $order->contract->total + $order->contract->deposit }}</td>
                    </tr>
                    <tr bgcolor="#e6e6fa">
                        <td colspan="2" style="font-weight: bold;">Сальдо на {{ $order->created_at }}.</td>
                        <td style="font-weight: bold;">{{ $order->contract->total }}</td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold;">{{ $order->contract->total }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div>
                В пользу {{ $order->company->generalCompany->name_ru ?? '' }} {{ $order->contract->total + $order->contract->deposit }} сум
                ({{ Str::ucfirst(num2str($order->contract->total + $order->contract->deposit)) }})
                {{--        (Два миллиона восемьсот тысяч сум 00 тийин) <--todo: дороботать надо --}}
            </div>
            <br>

            <div class="participants">

                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="part">
                            <div style="font-weight: bold;">{{ $order->company->generalCompany->name_ru ?? '' }}</div>
                            <table class="sign__field">
                                <tr>
                                    <td class="checkmark" style="vertical-align: middle">V</td>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td style="vertical-align: middle">
                                            <img src="{{ $order->company->generalCompany->sign }}">
                                        </td>
                                    @else
                                        <td class="bordered">
                                            <img src="">
                                        </td>
                                    @endif
                                    <td style="vertical-align: middle">(Подпись)</td>
                                </tr>
                            </table>
                        </td>

                        <td class="part">
                            <div style="font-weight: bold;">{{ $order->buyer->fio }}</div>

                            <table class="sign__field">
                                <tr>
                                    @if(isset($act_type) and $act_type == 'contract_pdf_qr')
                                        <td class="checkmark">&nbsp;</td>
                                        <td>
                                            <img src="{{ asset('images/public_oferta_qr.jpg')}}" alt="qr-sign" style="width: 50px; height: 50px; margin-top: 10px;">
                                        </td>
                                        <td style="vertical-align: bottom">&nbsp;</td>
                                    @else
                                        <td class="checkmark">V</td>
                                        <td class="bordered"> <img alt="image"></td>
                                        <td style="vertical-align: bottom">(Имзо)</td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </div><!-- /.participants -->

        </div>
    </div>
</div>

<div class="pay-example">
    <section class="head">
        <div class="logo">
            <img src="{{ asset('images/resus-logo.png') }}" alt="" width="156">
        </div>

        <div class="text">
            <p class="title">To'lovlarni amalga oshirish bo'yicha ko'rsatma</p>
            <p>Инструкция для пополнения лицевого счета</p>
        </div>

        <div class="buyer">
            <div class="text">
                <p>To'lov uchun sizning shaxsiy raqamingiz</p>
                <p>Ваш номер лицевого счета для пополнения</p>
            </div>
            <div class="phone">{{ $order->buyer->phone }}</div>
        </div>
    </section>

    @php
        $paymentTypes = [
            asset('images/resus-logo.png') => asset('images/resus-pay-example.png'),
            asset('images/payme.jpg') => asset('images/payme-pay-example.png'),
            asset('images/apelsin.jpg') => asset('images/apelsin-pay-example.png'),
            asset('images/click.jpg') => asset('images/click-pay-example.png'),
        ]
    @endphp

    <section class="body">
        @foreach($paymentTypes as $logo => $example)
            <div class="payment-type">
                <div class="title">
                    <img src="{{ $logo }}" alt="payment-logo" width="64">
                    @if($logo == asset('images/click.jpg'))
                        <p>Оплата за test через Click USSD *880*017958*Номер телефона клиента*сумма#</p>
                    @endif
                </div>
                <div class="photo">
                    <img src="{{ $example }}" width="284" alt="payment-logo">
                </div>
            </div>
        @endforeach
    </section>

    <section class="foot">
        <div class="other-payment-types">
            <p class="title">Boshqa to’lov turlari: <br>
                <span style="font-weight: normal; font-size: 10px;">Другие способы пополнения:</span>
            </p>

            <div class="text">
                <p style="margin-bottom: 5px;">Bank rekvizitlari <br>
                    Банковские реквизиты
                </p>
                {{ $order->company->generalCompany->name_uzlat ?? '' }} <br>
                Р/с: {{ $order->company->generalCompany->settlement_account ?? '' }} <br>
                МФО {{ $order->company->generalCompany->mfo ?? '' }} <br>
                ИНН: {{ $order->company->generalCompany->inn ?? '' }} <br>
                ОКЭД: {{ $order->company->generalCompany->oked ?? '' }} <br>
                Bank rekvizitlari bo'yicha to'lov (telefon raqamingiz yoki ID ni ko'rsatishni unutmang)
            </div>
            <div class="logo">
                <p style="margin: 0;">Boshqalar: <br>
                    Другие:
                </p>
                <img src="{{ asset('images/paynet.png') }}" alt="paynet" width="72" style="margin-top: 4px">
                <img src="{{ asset('images/my-uzcard.jpg') }}" alt="my-uzcard" width="72">
                <img src="{{ asset('images/upay.jpg') }}" alt="upay" width="60">
            </div>
        </div>
    </section>
</div>

<style>
    /*region ACT */
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 8px
    }

    table {
        font-family: DejaVu Sans, sans-serif;
        font-size: 8px;
    }

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
        /*background: #00A193;*/
        font-size: 8px;
        font-weight: bold;
        line-height: 20px;
        text-align: center;
        margin-top: 10px;
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

    .header-bottom {
        text-align: center;
        margin-bottom: 5px;
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
        /*padding: .25rem;*/
    }

    .participants {
        display: flex;
    }

    .participants .part {
        text-align: center;
    }

    .participants .part table {
        margin: 0 auto;
    }

    .participants .part table tr td.checkmark {
        font-weight: bold;
        font-size: 22px;
    }

    .lead {
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .participants .lead:after {
        display: none;
    }

    .participants table {
        margin-bottom: 1rem;
    }

    .participants table td.bordered {
        position: relative;
        width: 150px;
        border-bottom: 1px solid #000;
    }

    .participants table table tr td {
        /*padding-top: 12px;*/
    }

    .participants table table tr td:first-child {
        /*width: 130px;*/
        color: rgba(0, 0, 0, 0.6);
    }

    @media (max-width: 575px) {
        .offer .participants table td {
            /*font-weight: bold;*/
            /*font-size: 0.875rem;*/
        }
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

    @media (max-width: 575px) {
        .offer .products table th, .offer .payments table th {
            /*padding: .5rem .25rem;*/
        }
    }

    .offer .products table td {
        /*padding: 1rem;*/
        border: 1px solid rgba(0, 0, 0, 0.15);
    }

    .offer .payments table td {
        border: 1px solid rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 575px) {
        .offer .products table td, .offer .payments table td {
            /*padding: .5rem .25rem;*/
            /*font-size: 0.875rem;*/
        }
    }

    .offer .products table tr:last-child td, .offer .payments table tr:last-child td {
        /*border-bottom: none;*/
    }

    .offer .products table .total, .offer .payments table .total {
        font-weight: bold;
    }

    .offer .payments table td {
        /*padding: .5rem;*/
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

    .act {
        margin-bottom: 10px;
    }

    .act table {
        text-align: center;
    }

    .act table td {
        border: 1px solid rgba(0, 0, 0, 0.15);
        box-sizing: border-box;
    }

    /*endregion */

    /*region Pay-example */

    /*.pay-example {*/
    /*    margin-top: 25rem;*/
    /*}*/

    .pay-example .text {
        padding: 12px 0;
    }

    .pay-example .text .title {
        margin: 0;
        font-weight: bold;
        font-size: 20px;
    }

    .pay-example .text p:last-child {
        margin: 0;
        font-weight: 400;
        font-size: 14px;
    }

    .pay-example section.head .buyer {
        background-color: #F6F6F6;
        /*display: flex;*/
        /*justify-content: space-between;*/
        /*align-items: center;*/
        height: 72px;
        margin: 0 -1rem;
        padding: 0 1rem;
    }

    .pay-example section.head .buyer .text {
        float: left;
        padding: 20px 0 0;
    }

    .pay-example section.head .buyer .text p:first-child {
        margin: 0;
        font-weight: bold;
        font-size: 12px;
    }

    .pay-example section.head .buyer .text p:last-child {
        margin: 0;
        font-weight: 400;
        font-size: 10px;
    }

    .pay-example section.head .buyer .phone {
        padding-top: 20px;
        color: #6610F5;
        font-weight: bold;
        font-size: 20px;
        float: right;
    }

    .pay-example section.body {
        margin-top: 8px;
    }

    .pay-example section.foot {
        margin-top: 16px;
    }

    .pay-example section.body .payment-type {
        height: 120px;
        border-bottom: 1px solid #F6F6F6;
        margin-bottom: 8px;
    }

    .pay-example section.body .payment-type .title {
        float: left;
        padding-top: 50px;
    }

    .pay-example section.body .payment-type .photo {
        float: right;
    }

    .pay-example section.foot .other-payment-types {
        height: 60px;
        /*display: flex;*/
        /*justify-content: space-between;*/
        /*align-items: center;*/
        margin-top: 8px;
    }

    .pay-example section.foot .other-payment-types p.title {
        font-weight: bold;
        font-size: 14px;
    }

    .pay-example section.foot .other-payment-types .text {
        float: left;
        padding: 0;
    }

    .pay-example section.foot .other-payment-types .logo {
        float: right;
        /*display: flex;*/
        /*align-items: center;*/
    }

    .pay-example section.foot .other-payment-types .logo p {
        font-weight: bold;
    }

    .pay-example section.foot .other-payment-types .logo img:nth-child(3) {
        margin-right: 8px;
        padding-bottom: 7px;
    }

    .pay-example section.foot .other-payment-types .logo img:nth-child(2) {
        margin-right: 8px;
        padding-bottom: 5px;
    }

    .pay-example section.foot .other-payment-types .text p:first-child {
        font-weight: bold;
        /*font-size: 12px;*/
        margin: 0;
    }

    .pay-example section.foot .other-payment-types .text p:last-child {
        font-weight: 400;
        font-size: 10px;
        margin: 0;
    }

    .sign__field img {
        width: 170px;
        height: 100px;
    }

    .bordered {
        position: relative;
    }

    .bordered img {
        width: 170px;
        height: 120px;
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    .sign__field {
        margin-top: 10px !important;
    }
    /*endregion*/

    @media print{
        .first-page {
            height: 1050px !important;
        }

        .second-page {
            height: 1050px !important;
        }

        .third-page {
            height: 1050px !important;
        }
    }

</style>

</body>

</html>


