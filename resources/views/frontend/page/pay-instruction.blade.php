<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Pay Instruction</title>
    <link rel="stylesheet" href="{{ asset('assets/css/pay-instruction.css') }}">
    <script src="{{ asset('assets/js/vue.min.js') }}"></script>

    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
    (function (m, e, t, r, i, k, a) {
        m[i] = m[i] || function () {
            (m[i].a = m[i].a || []).push(arguments);
        };
        m[i].l = 1 * new Date();
        for (var j = 0; j < document.scripts.length; j++) {
            if (document.scripts[j].src === r) {
                return;
            }
        }
        k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a);
    })
    (window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');

    ym(92936187, 'init', {
        clickmap: true,
        trackLinks: true,
        accurateTrackBounce: true,
        webvisor: true,
    });
    </script>
    <noscript>
        <div><img src="https://mc.yandex.ru/watch/92936187" style="position:absolute; left:-9999px;" alt="" /></div>
    </noscript>
    <!-- /Yandex.Metrika counter -->
</head>
<body>

<main id="pay-instruction" class="container">
    <section class="instruction-header">
        <header>
            <h3>
                To'lovlarni amalga oshirish bo'yicha ko'rsatma
            </h3>
            <p>Инструкция по проведению платежа</p>
        </header>

        <div class="instruction-header__client-phone">
            <div class="client-phone__text">
                <h3>To'lov uchun hisob raqami - bu sizning telefon raqamingiz</h3>
                <p>Лицевой счет для оплаты - это номер вашего телефона</p>
            </div>

            <div class="client-phone__number">
                +998 ** *** ** **
            </div>
        </div>
    </section>

    <section class="pay-example">

        <div class="tabs">
            <div
                v-for="(tab, index) in tabs"
                :key="index"
                :class="{ tab: true, active: tab.status }"
                @click="showTab(index)"
            >
                @{{ tab.title }}
            </div>
        </div>

        <div :class="{ 'tab-content': true, active: activeTab === 0 }">
            <div class="payment-type">
                <div class="payment-type__payment">
                    <img src="{{ asset('images/logos/resus-nasiya.png') }}" alt="payment-logo" width="64">
                    <div class="payment__info">
                        <strong>Asosiy — Shaxsiy hisobni to'ldirish</strong>
                        <p>Главная — Пополнить лицевой счёт</p>
                    </div>
                    <div class="payment__info">
                        <strong>To'ldirish — Kartalar</strong>
                        <p>Пополнить через — Карты</p>
                    </div>

                    <div class="payment__info">
                        <strong>To'lov summasi</strong>
                        <p>Сумма оплаты</p>
                    </div>
                </div>

                <div class="payment-type__photo">
                    <img src="{{ asset('images/payment-examples/resus-nasiya.png') }}" alt="payment-logo" width="368">
                </div>
            </div>
            <div class="pay-example__app">
                <div class="pay-example__app__text">
                    <h3>resus Nasiya mobil ilovasini yuklab olish/to'lov qilish</h3>
                    <p>Скачать / Оплатить через приложение resus Nasiya</p>
                </div>

                <a href="{{ config('test.url_service_resusnasiya') }}" target="_blank"
                   class="pay-example__app__image"
                >
                    <div>
                        <img src="{{ asset('images/resusnasiya-icon.png') }}" alt="payment-logo" width="28" height="28">
                    </div>
                    <div>
                        resus Nasiyaga o'tish <br>
                        Перейти в resus Nasiya
                    </div>
                </a>
            </div>
        </div>

        <div :class="{ 'tab-content': true, active: activeTab === 1 }">
            <div class="payment-type">
                <div class="payment-type__payment float-right">
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
            <div class="pay-example__app">
                <div class="pay-example__app__text">
                    <h3>resus Bank mobil ilovasini yuklab olish/to'lov qilish</h3>
                    <p>Скачать / Оплатить через приложение resus Bank</p>
                </div>
                <a href="{{ config('test.url_service_resusbank') }}" target="_blank" class="pay-example__app__image resusbank">
                    <div>
                        <img src="{{ asset('images/resusbank-icon.png') }}" alt="payment-logo" width="28" height="28">
                    </div>
                    <div>
                        resus Bankka o'tish <br>
                        Перейти в resus Bank
                    </div>
                </a>
            </div>
        </div>

        <div :class="{ 'tab-content': true, active: activeTab === 2 }">
            <div class="payment-type">
                <div class="payment-type__payment float-left">
                    <img src="{{ asset('images/logos/payme.png') }}" alt="payment-logo" width="64">
                    <div class="payment__info">
                        <strong>To'lov — Xizmatlar uchun to'lov</strong>
                        <p>Оплата — Оплата услуг</p>
                    </div>
                    <div class="payment__info">
                        <strong>Kreditlar va muddatli to'lovlarni so'ndirish — resus Nasiya</strong>
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
            <div class="pay-example__app" style="background: rgba(0, 214, 255, 0.05)">
                <div class="pay-example__app__text">
                    <h3>Payme mobil ilovasini yuklab olish/to'lov qilish</h3>
                    <p>Скачать / Оплатить через приложение Payme</p>
                </div>
                <a href="{{ config('test.url_service_payme') }}" target="_blank" class="pay-example__app__image payme">
                    <div>
                        <img src="{{ asset('images/payme-icon.png') }}" alt="payment-logo" width="28" height="28">
                    </div>
                    <div>
                        Payme o'tish <br>
                        Перейти в Payme
                    </div>
                </a>
            </div>
        </div>

        <div :class="{ 'tab-content': true, active: activeTab === 3 }">
            <div class="payment-type">
                <div class="payment-type__payment float-right">
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
            <div class="pay-example__app" style="background: rgba(0, 214, 255, 0.05)">
                <div class="pay-example__app__text">
                    <h3>Click Up mobil ilovasini yuklab olish/to'lov qilish</h3>
                    <p>Click Up mobil ilovasini yuklab olish/to'lov qilish</p>
                </div>
                <a href="{{ config('test.url_service_click') }}" target="_blank" class="pay-example__app__image click">
                    <div>
                        <img src="{{ asset('images/click-icon.png') }}" alt="payment-logo" width="28" height="28">
                    </div>
                    <div>
                        Click Up o'tish <br>
                        Перейти в Click Up
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section class="payments-list__footer">
        <div class="footer__info">
            <h2 class="footer__info-title float-left" style="margin-top: 15px">
                Bank rekvizitlari bo'yicha to'lov
            </h2>

            <div class="footer__info-logos float-right">
                <img src="{{ asset('images/logos/paynet.png') }}" width="80px" alt="paynet">
                <img src="{{ asset('images/logos/uzcard.png') }}" width="80px" alt="uzcard">
                <img src="{{ asset('images/logos/upay.png') }}" width="80px" alt="upay">
            </div>
        </div>

        <p class="gray" style="margin-bottom: 30px">Оплата по банковским реквизитам</p>

        <div class="footer__info-footer">
            <div class="float-left" style="font-size: 10px">
                <p><strong>AO SOLUTIONS LAB</strong></p>
                <p>Р/с: <strong>20208000905369234001</strong></p>
                <p>МФО <strong>00974</strong></p>
                <p>ИНН <strong>308349548</strong></p>
                <p>ОКЭД <strong>62010</strong></p>
            </div>
            <div class="float-right" style="padding-top: 40px">
                <strong>Telefon raqamingizni yoki ID'ni ko'rsatishni unutmang</strong>
                <p>Не забудьте указать свой номер телефона или ID</p>
            </div>
        </div>
    </section>
</main>

<script>
new Vue({
    el: '#pay-instruction',
    data() {
        return {
            tabs: [
                {
                    title: 'resus Nasiya',
                    status: true,
                },
                {
                    title: 'resus Bank',
                    status: false,
                },
                {
                    title: 'Payme',
                    status: false,
                },
                {
                    title: 'Click Up',
                    status: false,
                },
            ],
            activeTab: 0,
        };
    },
    methods: {
        showTab(index) {
            this.activeTab = index;
            this.tabs.map(tab => tab.status = false)
            this.tabs[index].status = true
        },
    }
});
</script>
</body>
</html>
