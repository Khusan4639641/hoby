@php
    $rootPath = Config::get('test.sftp_file_server_domain');
    $companyNameUz = 'АJ "SOLUTIONS LAB"';
    $companyNameRu = 'АО "SOLUTIONS LAB"';
    $account = '20208000905369234001';
    $mfo = '00974';
    $inn = '308349548';
    $oked = '62010';
    $userImage = $buyerAvatar ? $rootPath . 'storage/' .  $buyerAvatar : null;
    $companySignature = $isSigned ? $generalCompany->sign : null;
    $userSignature = $isSigned && isset($buyerSign) ? $rootPath . 'storage/' .  $buyerSign : null;
    $total = $contract->total;

    $userData = [
        'ФИО' => $buyer->name. ' ' . $buyer->surname,
        'Серия паспорта' => $passport,
        'Дата, время' => $contract->created_at
    ];

    $paymentTypes = [
        asset('images/resus-logo.png') => asset('images/resus-pay-example.png'),
        asset('images/resus-bank.svg') => asset('images/resus-bank-pay-example.png'),
        asset('images/payme.jpg') => asset('images/payme-pay-example.png'),
        asset('images/click.jpg') => asset('images/click-pay-example.png'),
    ];


@endphp
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MFO Act</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            box-sizing: border-box;
        }

        .act-body table {
            min-height: 500px;
            width: 100%;
            font-size: 14px;
            table-layout: fixed;
        }

        .act-body table tr td {
        }

        .act-body table thead tr th {
            text-align: center;
        }

        .payments-list{
            height: 1000px;
        }

        .payments-list h1 {
            font-size: 16px;
        }

        .payments-list table {
            width: 100%;
            font-size: 12px;
        }

        .gray {
            font-size: 10px;
            font-weight: 400;
            margin-bottom: 2px !important;
            color: #555555;
        }

        .page-1 {
            height: 1000px;
        }

        .page-2 {
            height: 1000px;
        }

        .page-3 {
            height: 1000px;
        }

        .page-4 {
            height: 1000px;
        }

        .page-5 {
            /*height: 100px;*/
        }

        .payments-list h1 {
            font-size: 16px;
        }

        .payments-list table {
            width: 100%;
            font-size: 12px;
        }

        .payments-list {

        }

        .payments-list p {
            font-weight: 300;
            margin: 0;
        }

        .payments-list h1, h2, h3, h4, h5 {
            margin: 0;
        }

        .payments-list header {
            height: 60px;
        }

        .payments-list__client-phone {
            height: 50px;
            border: 5px solid #7433FF;
            border-radius: 20px;
            padding: 10px;
        }

        .client-phone__text h3{
            font-weight: 700;
            font-size: 12px;
            margin-top: 15px;
            margin-bottom: 0;
        }

        .client-phone__text p {
            margin: 0;
        }

        .client-phone__number {
            font-weight: 700;
            font-size: 20px;
            color: #7433FF;
            margin-top: 8px;
        }

        .payments-list__table {
            min-height: 500px;
        }

        .payments-list__footer {
        }

        .pay-example{
            padding-top: 20px;
        }

        .pay-example .payment-type {
            height: 220px;
            width: 100%;
            border-bottom: 1px solid #F6F6F6;
            padding-bottom: 24px;
        }

        .pay-example .payment-type .payment-type__payment {
            float: left;
        }

        .payment__info {
            width: 250px;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .pay-example .payment-type .payment-type__payment p {
            margin: 0;
        }

        .pay-example .payment-type .payment-type__payment img {
            display: block;
            width: 200px;
            margin-bottom: 10px;
        }

        .pay-example .payment-type .payment-type__photo {
            width: 450px;
            height: 200px;
        }
        .pay-example .payment-type .payment-type__photo img {
            object-fit: contain;
            width: 100%;
            height: 100%;
        }

        .float-right {
            float: right;
        }

        .float-left {
            float: left;
        }

        .sign__confirm strong{
            font-size: 10px;
        }

        .sign__confirm span {
            font-size: 10px;
        }
    </style>
</head>
<body>

<div class="page-1">
    <div style="height: 150px">
        <div class="float-left">
            <img src="{{ asset('images/logos/resus-nasiya-brand.png') }}" alt="" width="156">

            @foreach($userData as $label => $value)
                <p><strong>{{ $label }}:</strong> <span>{{ $value }}</span></p>
            @endforeach
        </div>
        <div class="float-right">
            <img width="150" height="150"  src="{{ $userImage }}" alt="user image">
        </div>
    </div>

    <table border="1" cellspacing="0" cellpadding="5" width="100%">
        <tbody>
        <tr>
            <th>ONLINE-MIKROQARZ shartnomasi <br> ELEKTRON TAKLIF</th>
            <th>Договор ОНЛАЙН-ЗАЙМА <br> ЭЛЕКТРОННАЯ ОФЕРТА</th>
        </tr>
        <tr>
            <td valign="baseline">
                <strong>1-§. Elektron taklif mavzusi</strong> <br> <br>
                <strong>1. Ushbu elektron taklifda quyidagi atamalar qo'llaniladi:</strong> <br>
                Mas'uliyati cheklangan jamiyat shaklidagi mikromoliya tashkiloti <strong>"SHAFFOF-MOLIYA"</strong>
                (keyingi
                o‘rinlarda mikromoliya tashkiloti deb yuritiladi) – <strong>{{ $companyNameUz }}</strong> (keyingi
                o‘rinlarda Jamiyat
                deb yuritiladi) bilan hamkorlik qiluvchi yuridik shaxs bo‘lib,Hamkorlarning tovarlarini sotib
                olish,ish va xizmatlar uchun haq to‘lash uchun jismoniy shaxslarga qisqa muddatli onlayn
                mikrokreditlar taqdim etadi.resus Nasiya tizimi; <br>

                <strong>Platforma</strong> – “resus Nasiya” tizimi, Platforma, Kompaniya, Mikromoliya tashkiloti yoki
                elektron pul
                tizimining agenti, Mijoz va Mijoz oʻrtasida oʻzaro hamkorlik va axborot almashinuvini taʼminlaydigan
                tashkiliy, axborot va texnik yechimlarning dasturiy-texnik kompleksi.Hamkor, Hamkorlar tarmog'i
                doirasida tovarlar, ishlar va xizmatlar uchun keyinchalik to'lash bilan mikrokredit olish uchun.
                <br>
                <strong>Mikroqarz shartnomasi</strong> - to'lov uchun taqdim etilgan Hamkorlardan sotib olingan
                tovarlarni, ishlarni
                va xizmatlarni qaytarish muddatini, imkoniyatini hisobga olgan holda, mijozning to'lov
                qobiliyatining Jamiyat tomonidan baholanishi asosida mikromoliya tashkiloti tomonidan
                qarzdor/mijozga ajratiladigan mablag'lar;
                <br>
                <strong>Qarzdor – Mijoz</strong> – Hamkorlardan onlayn kredit evaziga tovarlar sotib oluvchi,
                O‘zbekiston
                Respublikasi qonunchiligida belgilangan tartibda raqamli identifikatsiyadan (raqamli
                autentifikatsiya) o‘tgan, tijorat banklaridan biri tomonidan chiqarilgan bank kartasiga ega bo‘lgan
                jismoniy shaxs. O‘zbekiston Respublikasi banklari;
                <br>
                <strong>Vositachilik komissiyasi</strong> – Kompaniyaning Qarzdor-mijozdan elektron hamyonlardan
                foydalanish, “resus
                Nasiya” tizimi doirasida to‘lovlarni amalga oshirish, mikrokredit shartnomalarini rasmiylashtirish
                va “resus Nasiya” tizimining boshqa funktsiyalari uchun olinadigan daromadi. Vositachilik komissiyasi
                har bir onlayn-kreditning elektron taklifida aks ettirilmasdan (chegirilib) hisobdan olinadi;
                <br>
                <strong>Qarzdor/mijoz</strong> - ushbu ommaviy ofertada nazarda tutilgan shartlarda resus Nasiya tizimi
                Hamkorlaridan
                tovarlarni sotib olish, ish va xizmatlar uchun haq to‘lash uchun “resus Nasiya” tizimi orqali onlayn
                mikrokredit olgan jismoniy shaxslar;
                <br>
                <strong>Hamkor</strong> – “resus Nasiya” tizimi orqali sotilgan tovarlar, ishlar va ko‘rsatilgan
                xizmatlar uchun
                to‘lovlarni o‘zining o‘rtacha oylik daromadi miqdorida majburiyatlarini qabul qilish maqsadida “resus
                Nasiya” tizimida ro‘yxatdan o‘tgan yuridik shaxs (yoki yuridik shaxs tashkil etmagan yakka
                tartibdagi tadbirkor).
                <br>
                <strong>O'rtacha oylik to'lov summasi</strong> - barcha qarz oluvchilar bilan tuzilgan va tuzilishi
                mo'ljallangan
                shartnomalarga muvofiq qarz oluvchi/mijoz tomonidan amalga oshirilgan oylik to'lovlarning o'rtacha
                arifmetik ko'rsatkichi, shuningdek, qarz oluvchi/mijoz kafil sifatida ishtirok etadigan bitimlar.
                <br>
                <strong>O'rtacha oylik daromad</strong> - qarz oluvchi/mijoz tomonidan so'nggi 12 oy davomida ish haqi
                loyihasi
                asosida chiqarilgan bank kartasi orqali olingan daromadning elektron taklif aks ettirilgan sanaga
                nisbatan o'rtacha arifmetik ko'rsatkichi (agar 12 oydan kam ishlagan bo'lsa). oylar, amalda ishlab
                chiqilgan muddat kamida 6 oy );
                <br>
                <strong>Shaxsni tasdiqlovchi hujjat</strong> - Oʻzbekiston Respublikasi fuqarosining pasporti yoki
                shaxsiy
                guvohnomasi, Oʻzbekiston Respublikasida doimiy yashovchi chet el fuqarosi va fuqaroligi boʻlmagan
                shaxsning yashash uchun ruxsatnomasi yoki shaxsini tasdiqlovchi hujjat.
                <br><br>
                2. Qarzdor/mijoz elektron taklif shartnomasini imzolagandan so‘ng mikromoliya tashkilotida
                qarzdor/mijoz nomiga onlayn qarz hisobvarag‘i ochiladi va qarzdor/mijozga ajratilgan onlayn
                mikrokredit summasi kreditga o‘tkaziladi. ushbu hisobdan elektron hamyon.
                <br><br>
                3. Elektron taklif asosida mikromoliya tashkiloti tomonidan o‘tkazilgan mablag‘lar qarz
                oluvchi/mijoz uchun onlayn kredit sifatida qayta ishlanadi.
            </td>
            <td valign="baseline">
                <strong>1-§. Предмет электронной оферты</strong> <br>
                <strong>1. В настоящей электронной оферте используются следующие понятия:</strong> <br><br>
                Микрофинансовая организация в форме общества с ограниченной ответственностью
                <strong>"SHAFFOF-MOLIYA"</strong> (далее – микрофинансовая организация) является юридическим лицом,
                которое сотрудничает с <strong>{{ $companyNameRu }}</strong>
                (далее Общество) , по предоставлению краткосрочных онлайн микрозаймов физическим лицам для
                приобретения товаров, оплаты работ и услуг Партнеров системы resus Nasiya;
                <br>
                <strong>Платформа</strong> – система resus Nasiya, программно-аппаратный комплекс организационных,
                информационных и
                технических решений, обеспечивающие взаимодействие и обмен информацией между Платформой, Обществом,
                Микрофинансовой организации или агентом системы электронных денег, Клиента и Партнером, в целях
                получения микрозайма с последующей оплатой за товары, работы и услуги в рамках Партнерской сети.
                <br>
                <strong>Договор микрозайма</strong> - денежные средства, выделенные микрофинансовой организацией
                должнику/клиенту на
                основании проведенного скоринга платежеспособности клиента Обществом с учетом срока, возможности
                возврата предусмотренных для оплаты товаров, работ и услуг приобретенных у Партнеров;
                <br>
                <strong>Должник – клиент</strong> - физическое лицо, покупающее товары у Партнеров за онлайнзайм,
                прошедшее цифровую
                идентификацию (цифровую аутентификацию) в соответствии с процедурой, установленной законодательством
                Республики Узбекистан, владеющее банковской картой, выпущенной одним из коммерческих банков
                Республики Узбекистан;
                <br>
                <strong>Посредническая комиссия</strong> - это доход Общества, взимаемый с Должника-клиента за
                использование
                электронных кошельков, проведение платежей в рамках resus Nasiya, обработку договоров микрозаймов и
                прочие функции системы resus Nasiya . Посредническая комиссия взимается со счета, без отражения в
                электронном предложении каждого онлайн-займа (вычитается);
                <br>
                <strong>Должник/клиент</strong> - физические лица получившие через систему resus Nasiya онлайн микрозайм
                для
                приобретения товаров, оплаты работ и услуг у Парнеров системы resus Nasiya на условиях
                предусмотренных настоящей публичной офертой..;
                <br>
                <strong>Партнер</strong> - юридическое лицо (или индивидуальный предприниматель без образования
                юридического лица),
                зарегистрированное в системе resus Nasiya с целью приемки платежей за проданный товар, оказанные
                работы и услуги через систему resus Nasiya. показатель займовой нагрузки – отношение среднемесячной
                суммы платежа заемщика/клиента по займным и другим обязательствам к сумме его среднемесячного
                дохода;
                <br>
                <strong>Среднемесячная сумма платежа</strong> - представляет собой среднее арифметическое значение
                ежемесячных
                платежей, произведенных заемщиком/клиентом в соответствии с соглашениями, заключенными и
                предназначенными для заключения со всеми заемщиками, а также сделками, в которых заемщик/клиент
                участвует в качестве гаранта;
                <br>
                <strong>Среднемесячный доход</strong> - — это среднее арифметическое значение дохода, полученного
                заемщиком/клиентом
                через банковскую карту, выпущенную на основе зарплатного проекта за последние 12 месяцев по
                сравнению с датой отражения Электронного предложения (если проработал менее 12 месяцев, период,
                отработанный в действительности, не менее 6 месяцев);
                <br>
                <strong>Документ, удостоверяющий личность</strong> - паспорт или удостоверение личности гражданина
                Республики
                Узбекистан, вид на жительство или удостоверение личности иностранного гражданина и лица без
                гражданства, постоянно проживающего в Республике Узбекистан.
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="page-2">
    <table border="1" cellspacing="0" cellpadding="5" width="100%">
        <tbody>
        <tr>
            <th>ONLINE-MIKROQARZ shartnomasi <br> ELEKTRON TAKLIF</th>
            <th>Договор ОНЛАЙН-ЗАЙМА <br> ЭЛЕКТРОННАЯ ОФЕРТА</th>
        </tr>
        <tr>
            <td valign="baseline">
                <br><br>
                <strong>2-§. Onlayn kredit shartlari</strong> <br>
                4. Onlayn kredit 12 (o'n ikki) oygacha bo'lgan muddatga taqsimlanadi. <br>
                5. Onlayn mikrokreditning maksimal miqdori 15 000 000,00 (o‘n besh million) so‘mdan oshmaydi.
                ________________ <br>
                6. Mikromoliya tashkiloti onlayn-kreditni taqsimlashda hududiy cheklov qo‘yishi mumkin. <br>
                7. Onlayn mikroqarz qarz oluvchi/mijoz o'rtasida Kompaniyaning skoring asosida taqsimlanadi. <br>
                8.Skorlash jarayonida Mikromoliya tashkiloti quyidagi holatlardan biri mavjud bo‘lganda qarz
                oluvchi/mijozga onlayn
                mikroqarz bermaslik huquqiga ega:
                a) ilgari olingan kreditlar va boshqa majburiyatlar bo'yicha
                muddati o'tgan qarz mavjud bo'lsa; <br>
                b) "Soliq xizmati" DUK negizida qarzdor/mijozning daromadi 6
                oydan kam bo'lsa; <br>
                v) “Soliq xizmati” DUK negizida onlayn-kredit olish uchun ariza topshirish oyiga 2
                oy qolganda qarzdor/mijozning daromadlari to‘g‘risida ma’lumot bo‘lmaganda; <br>
                g) agar qarz oluvchi/mijozning so'rovi bo'yicha chiqarilgan bank kartasi oxirgi 2 oy ichida kreditga
                o'tkazilmagan bo'lsa; <br>
                d) qarzdor/mijoz 22 yoshdan 65 yoshgacha bo'lgan yosh chegarasiga kirmasa; <br>
                e) kredit yuki 50 foizdan oshgan taqdirda, onlaynkreditning qiymati ham hisob-kitobga qo'shiladi.
                <br><br>

                <strong>3-§. Tomonlarning huquq va majburiyatlar</strong><br>
                <strong>13. Mikromoliya tashkilotining huquqlari:</strong> <br>
                a) mikrokreditni onlayn to'lash muddatiga kelganda, qarz oluvchi/mijozning bank kartasi
                hisobvarag'idan kreditni hisobdan chiqarish; <br>
                b) agar onlayn mikroqarz belgilangan muddatda to'lanmagan bo'lsa (qarz summasini to'lamagan yoki
                belgilangan muddatda qarzni to'lagan bo'lsa), uni
                qaytarish uchun hisobvaraqlar va bank kartalaridagi mablag'lar bo'yicha undirib olinadi. shartnomani
                imzolash vaqtida mavjud bo‘lgan, shuningdek, qarzdor/mijoz nomiga ochilgan O‘zbekiston
                Respublikasining boshqa banklarida keyinroq ochilgan, tegishli ma’lumotlarni kiritib, ushbu elektron
                ofertada ko‘rsatilgan choralarni ko‘radi; <br>
                v) qarz oluvchini/mijozni kreditni onlayn to'lash muddati
                buzilgan taqdirda nizolarni hal qilish uchun ariza to'g'risida xabardor qilish; <br>
                e) kreditning belgilangan muddatda qaytarilmasligi natijasida onlayn mikroqarz sug‘urta kompaniyasi
                tomonidan
                qoplangan taqdirda, mikrokredit bo‘yicha qarzni onlayn undirish huquqini sug‘urta kompaniyasiga
                o‘tkazish.
                <br><br>

                <strong>14. Mikromoliya tashkilotining majburiyatlari:</strong><br>
                a) ushbu elektron taklifda ko'rsatilgan shartlar asosida onlayn mikroqarz olish uchun ariza berish; <br>
                b) qarz oluvchini/mijozni onlayn mikroqarzni muddatidan oldin to'lash sabablari to'g'risida xabardor
                qilish; <br>
                v) qarz oluvchidan/mijozdan onlayn - joriy kredit to'lovi uchun onlayn kredit to'lash
                jadvalida aniqlanadi. Agar nisbatan katta miqdordagi mablag 'qo'yilgan bo'lsa, unda olingan
                summaning ortig'i keyingi oyga qadar kreditni to'lash uchun ishlatiladi; <br>
            </td>
            <td valign="baseline">
                2. После того, как должник/клиент
                подпишет соглашение об электронной оферте, в микрофинансовой организации будет открыт счет
                онлайн-задолженности на имя должника/клиента, и с этого счета на электронный кошелек будет зачислена
                сумма онлайн-микрозайма, выделенная должнику/клиенту.
                <br><br>
                3. На основании электронного предложения
                денежные средства, переведенные микрофинансовой организацией, оформляются как онлайн-займ
                заемщику/клиенту.
                <br><br>
                <strong>2-§. Условия Онлайн-займа</strong> <br><br>
                4. Онлайн-займ распределяется на срок до 12 (двенадцать) месяцев. <br>
                5. Максимальная сумма онлайн микрозайма не превышает 15 000 000,00 (пятнадцать миллионов) сум. <br>
                6. Микрофинансовая организация может наложить территориальное ограничение на распределение онлайн-займа.
                <br>
                7. Онлайн микрозайзайм
                распределяется между заемщиком/клиентом на основе проведенного скоринга Обществом. <br>
                8. В процессе скоринга Микрофинансовая организация имеет право не выделяется заемщику/клиенту онлайн
                микрозайм при
                наличии одной из следующих ситуаций: а) при наличии просроченной задолженности по ранее полученным
                займам и другим обязательствам; Г) когда доход должника/клиента на базе ГУП "Налоговая служба"
                составляет менее 6 месяцев; д) при отсутствии информации о доходах должника/клиента за 2 месяца до
                месяца подачи заявки на онлайн-займа на базе ГУП "Налоговая служба"; е) если банковская карта,
                выпущенная для обращения заемщика/клиента, не была зачислена за последние 2 месяца; ж) когда
                должник/клиент не попадает в возрастной диапазон от 22 до 65 лет; з) в том случае, если показатель
                займовой нагрузки превышает 50 процентов, при расчете также добавляется стоимость онлайнзайма. <br>
                9. Заемщик/клиент может использовать утвержденный лимит онлайн-займа в течение 12 месяцев. Срок
                действия утвержденного лимита рассчитывается с момента отправки SMS-уведомления об утверждении
                лимита. Утвержденный лимит, независимо от того, сколько его частей они используют, считается
                утратившим свою силу после одного использования. <br>
                10. За использованный онлайн микрозайма оплата в виде процентов с заемщика/клиента не взимается. <br>
                11. Онлайн микрозайм возвращается заемщиком/клиентом в соответствии с графиком погашения онлайн-займа.
                <br>
                12. Онлайн микрозайм выделяется в виде микро
                займовой линии на 12 месяцев с возобновляемым остатком в момент погашения части микрозайма.
                <br><br>
                <strong>3-§. Права и обязанности сторон</strong> <br>
                <strong>13. Права микрофинансовой организации:</strong><br>
                а) когда дело доходит до срока онлайн-оплаты микрозайма, списать займ со счета банковской карты
                заемщика / клиента; <br>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="page-3">
    <br><br><br>
    <table border="1" cellspacing="0" cellpadding="5" width="100%">
        <tbody>
        <tr>
            <th>ONLINE-MIKROQARZ shartnomasi <br> ELEKTRON TAKLIF</th>
            <th>Договор ОНЛАЙН-ЗАЙМА <br> ЭЛЕКТРОННАЯ ОФЕРТА</th>
        </tr>
        <tr>
            <td valign="baseline">
                e) qarzdor/mijozning kredit
                yukining yanada oshishiga yo'l qo'ymaslik maqsadida, onlaynkredit bo'yicha muddati o'tgan qarzdorlik
                yuzaga kelgan taqdirda, har qanday aloqa vositalaridan, shu jumladan elektron aloqa vositalaridan
                foydalangan holda qarzdor/mijozni muddati o'tgan qarzdorlik yuzaga kelganligi to'g'risida xabardor
                qilish. , ko'rsatgan holda, ushbu qarz olish sodir bo'lgan kundan boshlab 7 kalendar kun ichida; <br>
                f) qarzdorni/mijozni holati, amal qilish muddati, qiymati, tarkibi va ushbu elektron oferta bo'yicha
                muddati o'tgan qarzni to'lash majburiyatini bajarmaslik oqibatlari to'g'risida xabardor qilish; <br>
                i) qarzdor/mijoz onlayn-kredit bo‘yicha kredit to‘liq to‘langanidan so‘ng qarzdor/mijozning shaxsiy
                kabinetidagi onlayn-kredit bo‘yicha qarzni to‘liq to‘laganligi to‘g‘risidagi ma’lumotlarni avtomatik
                tarzda shakllantirish.
                <br><br>
                <strong>15. Qarzdor/mijozning huquqlari:</strong> <br>
                a) ushbu elektron taklifni aks ettirish bo'yicha mustaqil qaror qabul qilish; <br>
                b) onlayn kreditni o'z vaqtida taqsimlashni talab qilish; <br>
                c) istalgan vaqtda onlayn kredit to'liq to'langanidan keyin ushbu
                elektron taklifni muddatidan oldin bekor qilish.
                <br><br>
                <strong>16. Qarzdor/mijozning majburiyatlari:</strong> <br>
                a) onlayn - kreditni o'z vaqtida to'lash; b) taklif bilan tanishish; v) shaxsiy ma'lumotlar,
                ro'yxatdan o'tish manzili, telefon raqami va pul mablag'larini yig'ish tartibini amalga oshirish
                uchun zarur bo'lgan boshqa ma'lumotlar o'zgarganligi to'g'risida to'lov jadvali yuzaga kelganda
                darhol Kompaniyani va mikromoliya tashkilotini xabardor qilish;
                <br><br>
                <strong>4-§. Tomonlarning javobgarligi.</strong> <br>
                17. Tomonlar ushbu Elektron oferta bo‘yicha o‘z majburiyatlarini bajarmagan yoki zarur darajada
                bajarmagan taqdirda, ularga O‘zbekiston Respublikasi qonun hujjatlarida nazarda tutilgan javobgarlik
                choralari qo‘llaniladi.
                <br>
                <strong>5-§. Favqulodda vaziyat.</strong> <br>
                18. Tomonlar fors-major holatlari amal qilgan davrda Elektron oferta bo‘yicha o‘z majburiyatlarini
                qisman yoki to‘liq bajarmaganliklari uchun javobgar emaslar. Eng muhim holatlarga quyidagilar
                kiradi: tabiiy hodisalar (zilzilalar, ko'chkilar, qurg'oqchilik va boshqa tabiat hodisalari) yoki
                ijtimoiy-iqtisodiy vaziyatlar (iqtisodiy sanksiyalar, harbiy harakatlar, ish tashlashlar, qamallar,
                davlat tashkilotlari va davlat tashkilotlari o'rtasidagi cheklovchi va taqiqlovchi choralar)
                natijasida yuzaga kelgan favqulodda vaziyatlar; hukumat qarorlari va boshqalar), agar bu holatlar
                elektron taklif bo'yicha shartlarning bajarilishiga bevosita ta'sir etsa, joriy sharoitda bartaraf
                etilishi mumkin bo'lmagan kutilmagan holatlar va kutilmagan holatlar. Fors-major holatlari yuzaga
                kelganda va bu to'xtatilganda, tomonlar darhol bir-birlarini xabardor qilishlari kerak. Xabarnoma
                tomonlar uchun mavjud bo'lgan barcha aloqa vositalari orqali yuboriladi. Fors-major holatlari bilan
                bog'liq holatlar yuzaga kelgan taqdirda, tomonlarning majburiyatlarini bajarish muddati fors-major
                holatlari bilan bog'liq holatlar amal qilgan muddatga mutanosib ravishda kechiktiriladi.
                <br><br>
                <strong>6-§. Nizolarni hal qilish.</strong> <br>
                19. Mikromoliya tashkiloti va qarzdor/mijoz taraflarning o‘zaro kelishuviga ko‘ra nizolarni qonun
                hujjatlarida belgilangan, shu jumladan muzokaralar yo‘li bilan hal qilish usullarini qo‘llashga
                haqli. <br>
                20. Majburiyatlarni bajarmaslik yoki lozim darajada bajarmaslik bilan bog'liq barcha
                nizolarni Tomonlar muzokaralar va da'volarni taqdim etish jarayonida hal qilishga harakat qiladilar.
                Da'voni ko'rib chiqish muddati da'voni oluvchiga da'vo yuborilgan kundan boshlab 3 kalendar kun. <br>
                21. Muzokaralar davomida kelishuvga erishilmaganda va da’vo tartibiga rioya qilgan holda, nizolar
                O‘zbekiston Respublikasining amaldagi qonunchiligiga muvofiq Mikromoliya tashkiloti joylashgan
                (shartnoma yurisdiktsiyasi) bo‘yicha sud tartibida hal etiladi. O‘zbekiston Respublikasi davlat
                sudi.
            </td>
            <td valign="baseline">
                б) при невозврате онлайн-микрозайма в установленный срок (невозврат суммы займа
                или погашение задолженности в установленный срок), для его возврата направить взыскание на средства,
                находящиеся на счетах и банковских картах имевшихся на момент подписания договора, а так же открытых
                позже в других банках Республики Узбекистан, открытых на имя должника/клиента, внести
                соответствующие сведения и принять меры, указанные в этой электронной оферте; <br>
                в) информировать заемщика/клиента о заявке на урегулирование спора в случае нарушения срока погашения
                онлайн-займа; <br>
                д) передача права онлайн-взыскания задолженности по микрозайму страховой организации, когда онлайн
                микрозайм покрывается страховой организацией в результате невозврата займа в течение указанного
                периода. <br><br>
                <strong>14. Обязательства микрофинансовой организации:</strong> <br>
                а) оформить онлайн микрозайм на условиях, изложенных в настоящем электронном предложении; <br>
                б) информировать заемщика/клиента о причинах досрочного погашения онлайнмикрозайма; <br>
                в) от заемщика/клиента онлайн - определяется в онлайн-графике погашения займа для текущего платежа по
                займу. Если вносится относительно большая сумма средств, то превышение полученной суммы направляется
                на погашение займа к следующему месяцу; <br>
                д) в целях предотвращения дальнейшего увеличения займовой
                нагрузки на должника/клиента в случае возникновения просроченной задолженности по онлайнзайму
                проинформировать должника/клиента о возникновении просроченной задолженности с использованием любых
                средств связи, включая электронную связь, в течение 7 календарных дней с момента дата возникновения
                этого заимствования, с указанием; <br>
                е) информировать должника/клиента о статусе, сроке действия,
                стоимости, составе и последствиях неисполнения обязательства по погашению непогашенной
                задолженности, которая была просрочена по данному электронному предложению; <br>
                и) автоматическое формирование информации о том, что должник/клиент полностью погасил задолженность по
                онлайнзайму в
                личном кабинете должника/клиента после полной оплаты займа по онлайн-займу
                <br><br>
                <strong>15. Права должника/клиента:</strong><br>
                а) принять независимое решение об отражении данного электронного предложения; б) требовать
                своевременного распределения онлайн-займа; в) досрочно отменить данное электронное предложение после
                полной оплаты онлайнзайма в любое время <br>

                <strong>16. Обязательства должника/клиента:</strong> <br>
                а) онлайн - своевременное погашение займа; <br>
                б) ознакомится с офертой; <br>
                в) незамедлительно информировать Общество и микрофинансовую организацию о смене персональных данных,
                адреса прописки,
                номере телефона и иных данных необходимых для исполнения процедуры взыскания средств при наступлении
                графика платежей;<br>
                <strong>4-§. Ответственность сторон</strong> <br>
                17. В случае, если стороны невыполняют обязательства по настоящей Электронной Оферте или не
                выполняют их на требуемом уровне, к ним применяются меры ответственности, предусмотренные
                законодательством Республики Узбекистан.
                <br><br>
                <strong>5-§. Форс-мажор</strong> <br>
                18. Стороны не несут ответственность за частичное или полное неисполнение своих обязательств по
                Электронной Оферте в период, в течение которого действует форсмажор. К наиболее важным
                обстоятельствам относятся: чрезвычайные ситуации, вызванные природными явлениями (землетрясения,
                оползни, засухи и другие природные явления) или социальноэкономические ситуации (экономические
                санкции, военные действия, забастовки, осады, ограничительные и запретительные меры между
                государственными организациями и государственными организациями, решения правительства и т.д.),
                непредвиденные случаи и непредвиденные ситуации которые не могут быть устранены в текущих условиях,
                если эти ситуации оказывают непосредственное влияние на выполнении условий по электронной оферте.
                Когда возникают ситуации форс-мажорные ситуации, и когда это прекращается, стороны немедленно
                информируют друг друга. Уведомление направляется всеми доступными для сторон средствами связи. В
                случае наступления обстоятельств, связанных с форс-мажором, у сторон срок исполнения обязательств
                откладывается пропорционально периоду, на который действительны случаи, связанные с форсмажорами.

            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="page-4">
    <table border="1" cellspacing="0" cellpadding="5" width="100%">
        <tbody>
        <tr>
            <th>ONLINE-MIKROQARZ shartnomasi <br> ELEKTRON TAKLIF</th>
            <th>Договор ОНЛАЙН-ЗАЙМА <br> ЭЛЕКТРОННАЯ ОФЕРТА</th>
        </tr>
        <tr>
            <td valign="baseline">
                <br><br>
                <strong>7-§. Elektron taklifning boshqa shartlari</strong> <br>
                22. Qarz oluvchi/mijoz Elektron taklifni ko‘rsatish orqali uning shaxsiga oid ma’lumotlarni qayta
                ishlashga rozi bo‘lgan deb hisoblanadi. <br>
                23. Mikromoliya tashkiloti ushbu elektron taklifdan
                foydalangan holda qarz oluvchi/mijoz uchun onlayn-kredit uchun onlayn tarzda to‘lash imkoniyatini
                yaratadi. <br>
                24. Mikromoliya tashkiloti onlayn-kredit shartlarini, shu jumladan ushbu elektron
                taklifning amal qilish muddatini bir tomonlama tartibda o‘zgartirishga haqli emas. <br>
                25. Qarzdor/mijoz
                vafot etgan taqdirda uning huquq va majburiyatlari O‘zbekiston Respublikasi qonun hujjatlariga
                muvofiq hal etiladi. <br>
                26. Mazkur elektron oferta qarzdor/mijoz tomonidan imzolangan kundan boshlab
                ushbu elektron oferta bo‘yicha majburiyatlar to‘liq bajarilgunga qadar amal qiladi. <br>
                27. Ushbu Elektron ofertaning bekor qilinishi tomonlarni u to‘xtatilgunga qadar bildirilgan o‘zaro
                talabni
                (talabni) qondirish majburiyatidan ozod etmaydi. <br>
                28. Mazkur elektron ofertada nazarda tutilmagan hollarda O‘zbekiston Respublikasi qonun hujjatlari
                qo‘llaniladi. Vositachilik komissiyasining
                to‘lovi oshkor etilmagan. Erta yig'ish tartibi (61 kun davomida shartnoma bo'yicha kechikish bo'lsa,
                biz butun shartnomani muddatidan oldin undirish huquqiga egamiz).
            </td>
            <td valign="baseline">
                <br><br>
                <strong>6-§. Разрешение споров</strong> <br>
                19. Микрофинансовая организация и должник/клиент, по взаимному согласию сторон, вправе применять
                установленные законом методы разрешения спора, в том числе путем переговоров. <br>
                20. Все споры, связанные с неисполнением, или ненадлежащим исполнением обязательств, Стороны будут
                стараться решить
                в ходе переговоров и направления претензий. Срок рассмотрения претензии составляет 3 календарных дня
                с даты направления претензии в адрес получателя претензии. <br>
                21. В случае не достижения согласия в ходе переговоров и при соблюдении претензионного порядка, споры
                будут разрешаться в судебном порядке
                в соответствии с действующим законодательством Республики Узбекистан по месту нахождения
                Микрофинансовой организации (договорная подсудность), уполномоченным государственным судом РУз.
                <br><br>
                <strong>7-§. Другие условия электронной оферты</strong> <br>
                22. Считается, что заемщик/клиент согласился обработать информацию, касающуюся его/ее личности,
                путем отображения Электронного Предложения. <br>
                23. Микрофинансовая организация создает возможность для
                заемщика/клиента удаленно осуществлять оплату онлайн-займа по данному электронному предложению. <br>
                24. Микрофинансовая организация не имеет права в одностороннем порядке изменять условия онлайн- займа,
                включая срок действия настоящего электронного предложения. <br>
                25. В случае смерти должника/клиента его
                права и обязанности разрешаются в соответствии с законодательством Республики Узбекистан. <br>
                26. Настоящая электронная оферта действует с даты ее подписания должником/клиентом до полного выполнения
                обязательств по настоящей электронной оферте. <br>
                27. Прекращение действия настоящей Электронной Оферты
                не освобождает стороны от обязанности удовлетворить взаимную претензию (требование), высказанную до
                тех пор, пока она не будет прекращена. <br>
                28. Законодательство Республики Узбекистан применяется в
                случаях, не предусмотренных настоящей электронной офертой. Не раскрыто оплата посреднической
                комиссии. Процедура досрочного взыскания (на 61 день имеем право на досрочное взыскание всего
                договора при наличии просрочки по договору).

            </td>
        </tr>
        </tbody>
    </table>
</div>

    <section class="page-5 payments-list">
        <header class="payment-list__header">
            <div class="header__logo float-left" >
                <img src="{{ asset('images/logos/resus-nasiya-brand.png') }}" alt="" width="156">
            </div>
            <div class="header__info float-right">
                <h3 class="gray" style="color: #000">Qo'llab-quvvatlash xizmati</h3>
                <h3 class="gray" style="margin:0 0 5px 0 !important;">Служба поддержки</h3>
                <h3>+998 78 777 1515</h3>
            </div>
        </header>

        <h1 class="payments-list__title" style="font-size: 25px; margin: 20px 0 5px 0">To'lash jadvali buyurtma №2133413</h1>
        <h3 style="margin:0 0 10px 3px;" class="gray">График оплаты заказа</h3>

        <div class="payments-list__table" style="height: 420px">
            <table border="1" cellspacing="0" cellpadding="5" style="margin-bottom: 16px">
                <thead>
                <tr>
                    <th style="width: 40px">№</th>
                    <th>
                        <h3>To'lov sanasi</h3>
                        <h3 class="gray" style="font-size: 12px">Дата платежа</h3>
                    </th>
                    <th>
                        <h3>To'lov summasi, so'm</h3>
                        <h3 class="gray" style="font-size: 12px">Сумма платежа, сум</h3>
                    </th>
                    <th>
                        <h3>To'lov qoldig'i, so'm</h3>
                        <h3 class="gray" style="font-size: 12px">Остаток сум</h3>
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($contract->schedule as $index => $payment)
                    @php
                        $total -= $payment->total;
                        if($total < 0) $total = 0;
                    @endphp
                    <tr>
                        <td align="center">{{ $index + 1 }}</td>
                        <td align="center">{{ $payment->date }}</td>
                        <td align="center">{{ $payment->total }}</td>
                        <td align="center">{{ (int)$total }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <h3 class="gray">O'z vaqtida bo'lib — bo'lib to'lovlarni amalga oshiring-jadvalda ko'rsatilgan sanaga qadar.</h3>
        <h3 class="gray">Вносите платежи по рассрочке в срок — до указанной в графике даты включительно.</h3>

        <div class="payments-list__sign" style="padding-top: 60px; height: 60px">
            <div class="sign__company float-left">
                <p style="margin-bottom: 10px">_______________________________________________________________________</p>
                <div class="sign__confirm" style="height: 40px">
                    <strong class="float-left">МФО “SHAFFOF–MOLIYA” MChJ</strong>
                    <span class="float-right">Imzo</span>
                </div>
            </div>

            <div class="sign__client float-right">
                <p style="margin-bottom: 10px">_______________________________________________________________________</p>
                <div class="sign__confirm" style="height: 40px">
                    <strong class="float-left">Mijoz</strong>
                    <span class="float-right">Imzo</span>
                </div>
            </div>
        </div>

        <div class="payments-list__client-phone">
            <div class="client-phone__text float-left">
                <h3 style="margin:8px 0 0 0">To'lov uchun sizning shaxsiy raqamingiz</h3>
                <h4 class="gray" style="margin:0">Номер вашего счета для пополнения</h4>
            </div>

            <div class="client-phone__number float-right">
                {{ $buyer->phone }}
            </div>
        </div>

        <div class="payments-list__footer">
            <div class="footer__info" style="height: 40px">
                <h2 class="footer__info-title float-left" style="margin-top: 30px">
                    Bank rekvizitlari bo'yicha to'lov
                </h2>

                <div class="footer__info-logos float-right">
                    <img src="{{ asset('images/logos/paynet.png') }}" width="100px" alt="paynet">
                    <img src="{{ asset('images/logos/uzcard.png') }}" width="100px" alt="uzcard" >
                    <img src="{{ asset('images/logos/upay.png') }}" width="100px" alt="upay" >
                </div>
            </div>

            <h3 class="gray" style="margin-bottom: 20px">Оплата по банковским реквизитам</h3>

            <div class="footer__info-footer" style="height: 60px">
                <div class="float-left" style="font-size: 10px">
                    <h4 style="font-weight: 400"><strong>{{ $companyNameRu }}</strong></h4>
                    <h4 style="font-weight: 400">Р/с: <strong>{{ $account }}</strong></h4>
                    <h4 style="font-weight: 400">МФО <strong>{{ $companyNameRu }}</strong></h4>
                    <h4 style="font-weight: 400">ИНН <strong>{{ $inn }}</strong></h4>
                    <h4 style="font-weight: 400">ОКЭД <strong>{{ $oked }}</strong></h4>
                </div>
                <div class="float-right" style="padding-top: 50px">
                    <strong>Telefon raqamingiz yoki ID ni ko'rsatishni unutmang</strong>
                    <h4 style="font-weight: 400">Не забудьте указать свой номер телефона или ID</h4>
                </div>
            </div>

        </div>
    </section>

    <section class="pay-example">
        <div class="payment-type">

            <div class="payment-type__payment float-left">
                <img src="{{ asset('images/logos/resus-nasiya.png') }}" alt="payment-logo" width="64">

                <div class="payment__info">
                    <strong>Asosiy — Shaxsiy hisobingizni to'ldiring</strong>
                    <p>Главная — Пополнить лицевой счёт</p>
                </div>
                <div class="payment__info">
                    <strong>To'ldirish orqali — Xaritalar</strong>
                    <p>Пополнить через — Карты</p>
                </div>

                <div class="payment__info">
                    <strong>To'lov summasi</strong>
                    <p>Сумма оплаты</p>
                </div>

            </div>

            <div class="payment-type__photo float-right">
                <img src="{{ asset('images/payment-examples/resus-nasiya.png') }}" alt="payment-logo">
            </div>

        </div>

        <div class="payment-type">
            <div class="payment-type__payment float-left">
                <img src="{{ asset('images/logos/resus-bank.png') }}" alt="payment-logo" width="64">
                <div class="payment__info">
                    <strong>To'lov — Kategoriya bo'yicha to'lov</strong>
                    <p>Оплата — Оплата по категориям</p>
                </div>
                <div class="payment__info">
                    <strong>Kreditlar — resus Nasiya</strong>
                    <p>Kreditlar — resus Nasiya</p>
                </div>

                <div class="payment__info">
                    <strong>Telefon raqami</strong>
                    <p>Номер телефона</p>
                </div>

            </div>
            <div class="payment-type__photo float-right">
                <img src="{{ asset('images/payment-examples/resus-bank.png') }}" alt="payment-logo">
            </div>
        </div>

        <div class="payment-type">
            <div class="payment-type__payment float-left">
                <img src="{{ asset('images/logos/payme.png') }}" alt="payment-logo" width="64">
                <div class="payment__info">
                    <strong>To'lov — xizmatiga to'lov</strong>
                    <p>Оплата — Оплата услуг</p>
                </div>
                <div class="payment__info">
                    <strong>Kredit va bo'lakli to'lovlarni so'ndirish — resus Nasiya</strong>
                    <p>Погашение кредитов и рассрочек — resus Nasiya</p>
                </div>

                <div class="payment__info">
                    <strong>Telefon raqami, to'lov summasi</strong>
                    <p>Ваш номер, сумма платежа</p>
                </div>

            </div>
            <div class="payment-type__photo float-right">
                <img src="{{ asset('images/payment-examples/payme.png') }}" alt="payment-logo">
            </div>
        </div>

        <div class="payment-type">
            <div class="payment-type__payment float-left">
                <img src="{{ asset('images/logos/click.png') }}" alt="payment-logo" width="64">
                <div class="payment__info">
                    <strong>To'lov — Kredit so'ndirish</strong>
                    <p>Оплата — Погашение кредита</p>
                </div>
                <div class="payment__info">
                    <strong>resus Nasiya</strong>
                    <p>Kreditlar — resus Nasiya</p>
                </div>

                <div class="payment__info">
                    <strong>Telefon raqami, to'lov summasi</strong>
                    <p>Номер телефона, сумма оплаты</p>
                </div>

            </div>
            <div class="payment-type__photo float-right">
                <img src="{{ asset('images/payment-examples/click.png') }}" alt="payment-logo">
            </div>
        </div>
    </section>
</body>
</html>
