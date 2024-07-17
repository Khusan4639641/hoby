@extends('templates.panel.app')

@section('content')

    <div id="court-letter" class="court-letter">
        <div class="row" v-if="letter">
            <div :class="{'col-6': !this.isColumnHide, 'pr-5 pl-2': this.isColumnHide}">
                <div class="court-letter__top">
                    <div class="court-letter__top-block">
                        <div class="court-letter__top--left">
                            <img src="/images/logo-court-letter.png"/>
                            <h6>TOSHKENT VILOYATI HUDUDIY BOSHQARMASI</h6>
                        </div>

                        <div class="court-letter__top--right">
                        <span>
                            O'zbekiston Respublikasi, Toshkent shahri, 100070
                        </span>
                            <span>
                            Yakkasaroy tumani, Shota Rustaveli ko'chasi 22-uy
                        </span>
                            <span>
                            Tel: (998) 71-202-21-21, (998) 71-202-23-45
                        </span>
                            <span>
                            Email: tv@chamber.uz, web-site: www.chamber.uz
                        </span>
                        </div>
                    </div>

                    <span class="text-center d-block font-size-14 mt-2">
                    CHAMBER OF COMMERCE AND INDUSTRY OF UZBEKISTAN - ТОРГОВО-ПРОМЫШЛЕННАЯ ПАЛАТА УЗБЕКИСТАНА <br/>
                    ATIB "IPOTEKA BANK" MEHNAT FILIALI, H/R: 202120001005374491001, ИНН 201806983, МФО 00423
                    </span>
                    <hr class="m-0"/>
                    <hr class="m-0" style="border-top: 2px solid"/>
                    <hr class="m-0"/>

                    <strong class="d-block my-2">№__________"___"____ _______y.</strong>
                </div>

                <div class="court-letter__body py-3">
                    <div class="row mb-2">
                        <div class="col-6 d-flex justify-content-end">
                            <strong>
                                Ундирувчи:
                            </strong>
                        </div>
                        <div class="col-6">
                            <strong>
                                ФИБ Шайхонтоҳур туманлараро судига<br/>
                                Ўзбекистон Савдо-саноат палатаси Тошкент вилоят худудий бошқармаси <br/>
                                <i class="font-weight-normal">Инд: 100070, Тошкент шаҳар, Ш.Руставелли 22-уй.</i>
                            </strong>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 d-flex justify-content-end">
                            <strong>
                                Палата аъзоси манфаатида:
                            </strong>
                        </div>
                        <div class="col-6">
                            <strong>
                                @{{letter.buyer?.contract?.general_company?.name_uz}} <br/>
                                <i class="font-weight-normal">
                                    @{{letter.buyer?.contract?.general_company?.address}}
                                    Банк: ОПЕРУ АКБ “Капиталбанк”<br/>
                                    Ҳ/р: @{{letter.buyer?.contract?.general_company?.inn}}, МФО: @{{letter.buyer?.contract?.general_company?.mfo}} , <br/>СТИР: 307 769 761 <br/>
                                    тел: @{{phoneNumber}}
                                </i>
                            </strong>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 d-flex justify-content-end">
                            <strong>
                                Қарздор:
                            </strong>
                        </div>
                        <div class="col-6">
                            <strong>
                                @{{letter.buyer?.name}} @{{letter.buyer?.surname}} @{{letter.buyer?.patronymic}}<br/>
                                <i class="font-weight-normal">
                                    @{{letter.buyer?.addresses?.registration_address?.address}}
                                </i><br/>
                                Паспорт маълумоти: <i class="font-weight-normal">@{{letter.buyer?.personals?.birthday}} й.т. @{{letter.buyer?.personals?.passport_number}}</i><br/>
                                ЖШШИР: <i class="font-weight-normal">@{{letter.buyer?.personals?.pinfl}}</i><br/>
                                Тел: @{{letter.buyer?.phone}}
                            </strong>
                        </div>
                    </div>

                    <div class="court-letter__body-text">
                        <h5 class="font-weight-bolder text-center">А Р И З А
                            <span class="d-block font-weight-normal font-size-16">(қарз ундириш тўғрисида)</span>
                        </h5>
                        <p>
                            &nbsp;&nbsp; @{{letter.buyer?.contract?.general_company?.name_uz}} МЧЖ томонидан товарларни муддатли тўлов асосида тақдим этишнинг
                            оммавий
                            офертаси ва умумий шартлари (кейинги ўринларда -Оферта) ўзининг веб-сайтига жойлаштирилган.
                            Ушбу оммавий шартнома бўйича аризачининг оммавий офертасини акцептлаш мақсадида, аризачи
                            ҳамда @{{letter.buyer?.name}} @{{letter.buyer?.surname}} @{{letter.buyer?.patronymic}} (кейинги ўринларда – Қарздор) ўртасида @{{letter.buyer?.contract?.confirmed_at}} -йилда
                            @{{letter.buyer?.contract?.id}} -сонли оммавий оферта шартларини қабул қилиш тўғрисидаги акцепт (кейинги ўринларда -
                            Шартнома) имзоланган. 22.08.2021 йил кунги @{{letter.buyer?.contract?.id}}- сонли шартноманинг тўлов графигига асосан
                            қарздор шартнома бўйича 15.09.2021 йил кунидан 13.02.2022 йил кунига қадар ҳар ой
                            @{{parseInt((letter.buyer?.contract?.total / letter.buyer?.contract?.period)).toLocaleString('RU')}}
                            сўмдан, жами @{{(parseInt(letter.buyer?.contract?.total).toLocaleString('RU'))}} сўм миқдоридаги тўлов суммасини тўлаш мажбуриятини олган бўлиб,
                            бугунги кунда ушбу мажбурият лозим даражада бажарилмай келинмоқда.<br/>
                            &nbsp;&nbsp; Жамият томонидан мазкур шартнома асосида ўз мажбурияти бажарилган.Бироқ Қарздор
                            шартнома
                            асосида сотиб олинган маҳсулот учун тўловларни белгиланган муддатларда тўламаслиги оқибатида
                            @{{letter.buyer?.contract?.balance_int.toLocaleString('RU')}} сўм қарздорлик ва @{{letter.buyer?.contract?.autopay_debit_history_balance}} сўм ундиру харажатлари буйича қарздорлик юзага келган.
                            Шу муносабат билан ҳозирги кунда қарздорнинг шартнома бўйича жами қарздорлиги
                            @{{(letter.buyer?.contract?.balance_int + letter.buyer?.contract?.autopay_debit_history_balance).toLocaleString('RU')}}
                            сўмни ташкил қилади.<br/>
                            &nbsp;&nbsp;Оммавий офертанинг низоларни ҳал қилиш тартиби бўлимига асосан ушбу шартнома
                            юзасидан келиб
                            чиққан низо ҳал қилиш учун <strong>фуқаролик ишлари бўйича Тошкент шаҳар Шайхонтоҳур
                                туманлараро
                                судига</strong> топширилиши белгилаб қўйилган.<br/>
                            &nbsp;&nbsp;Ўзбекистон Республикаси Савдо-саноат палатаси тўғрисида”ги Қонуннинг 21-моддаси
                            ҳамда
                            Ўзбекистон Республикаси “Давлат божи тўғрисида”ги Қонунининг 9-моддасининг 2-бандини қўллаб,
                            Суддан:
                        </p>
                        <h5 class="font-weight-bolder text-center">С Ў Р А Й Д И:</h5>
                        <p>
                            - Аризани давлат божисиз иш юритишга қабул қилишни;<br/>
                            - @{{letter.buyer?.contract?.general_company?.name_uz}} МЧЖ фойдасига жавобгар @{{letter.buyer?.name}} @{{letter.buyer?.surname}} @{{letter.buyer?.patronymic}} дан @{{(letter.buyer?.contract?.balance_int + letter.buyer?.contract?.autopay_debit_history_balance).toLocaleString('RU')}}
                            сўм асосий қарз ва  @{{parseInt(letter.buyer?.contract?.notary_setting?.fee).toLocaleString('RU')}} сўм почта харажатини, жами @{{(Number(letter.buyer?.contract?.notary_setting.fee) + letter.buyer?.contract?.balance_int + letter.buyer?.contract?.autopay_debit_history_balance).toLocaleString('RU')}} итог сўм ундиришни;<br/>
                            - Давлат божини Қарздор зиммасига юклашни.<br/>
                        </p>
                        <p class="px-5 pt-3 pb-2">
                            1. Ундирувчининг ЎзР ССПга аъзолик шартномаси ва гувоҳномаси нусхаси;<br/>
                            2. Шартнома нусхаси;<br/>
                            3. Паспорт нусхаси;<br/>
                            4. Ишончнома нусхаси;<br/>
                            5. Почта харажатлари тўлови амалга оширилганлигини тасдиқловчи хужжат.<br/>
                            6.Ушбу Аризани имзолаш хуқуқини берувчи буйруқ ва ишончнома нусхаси.<br/>
                        </p>

                        <div class="d-flex justify-content-between">
                            <strong class="font-size-18" style="max-width: 300px">
                                @{{fio}}
                            </strong>
                            <strong class="font-size-18">
                                @{{position}}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <div :class="{'col-6': !this.isColumnHide, 'd-none': this.isColumnHide}">
                <label for="">ФИО</label>
                <input class="form-control mb-2 w-100" type="text" v-model="position">

                <label for="">Лавозим</label>
                <input class="form-control mb-2 w-100" type="text" v-model="fio">

                <label for="">Телефон</label>
                <input class="form-control mb-2 w-100" type="text" v-model="phoneNumber">

                <button @click="print" class="btn btn-primary mt-2"> &check; {{__('app.btn_print')}}</button>
            </div>
        </div>
    </div>

{{--    <script src="https://cdn.jsdelivr.net/npm/vue2-editor@2.3.34/dist/vue2-editor.js"></script>--}}
    <script
        src="https://www.jqueryscript.net/demo/Export-Html-To-Word-Document-With-Images-Using-jQuery-Word-Export-Plugin/FileSaver.js"></script>
    <script
        src="https://www.jqueryscript.net/demo/Export-Html-To-Word-Document-With-Images-Using-jQuery-Word-Export-Plugin/jquery.wordexport.js"></script>

    <script>
        const app = new Vue({
            el: '#court-letter',
            created() {
                this.getData();
            },
            data: {
                // lang,
                letter: null,
                isColumnHide: false,
                position: 'K.Вафаев',
                fio: 'Бошқарма бошлиғининг ҳуқуқий масалалар бўйича ўринбосари',
                phoneNumber: '+998 (97) 743-02-82'
            },
            methods: {
                async getData() {
                    const contractId = window.location.href.split('/').at(-2)
                    const notaryId   = window.location.href.split('/').at(-1)

                    try {
                        const response = await axios.get(`/api/v1/letters/letter-filling-data?contract_id=${contractId}&notary_id=${notaryId}`,
                            {
                                headers: {
                                    Authorization: `Bearer ${Cookies.get('api_token')}`,
                                    'Content-Language': 'ru', // temporary  solution: coz on link static Uz
                                },
                            },
                        )
                        this.letter = response.data?.data
                    } catch (err) {
                        console.error(err)
                    }
                },
                print() {
                    this.isColumnHide = true;
                    setTimeout(() => {
                        window.print()
                    },0)

                    window.onafterprint = () => {
                        this.isColumnHide = false;
                    }
                },
            }
        })

    </script>


    <style>
        .court-letter {
            font-family: "Times New Roman";
            margin-top: -2rem;
        }

        .court-letter__page {
            background: white;
            padding: 30px;
            border-radius: 4px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .court-letter img {
            width: 250px;
            height: 100px;
        }

        .court-letter__top-block {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid;
        }

        .court-letter__top--right span {
            display: block;
            font-weight: 500;
            font-size: 14px;
        }

        .court-letter__top--left h6 {
            font-weight: 900;
        }

        p {
            font-weight: 500;
            font-size: 18px;
            text-align: justify;
        }

        label, input {
            font-family: 'Gilroy', sans-serif;
        }

        hr {
            border-top: 2px solid #878787;
            height: 2px;
        }
    </style>

@endsection
