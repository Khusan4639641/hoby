@extends('templates.panel.app')

@section('content')

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
            height: 120px;
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
        .btn-word {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-word.processing {
            filter: grayscale(1);
            opacity: .7;
        }
        .btn-word .spinner-border{
            display: none;
            width: 1rem;
            height: 1rem;
            border: 2px solid currentColor;
            border-right-color: transparent;
        }
        .btn-word.processing .spinner-border {
            display: inline-block;
        }
    </style>

    <div id="court-letter" class="court-letter">
        <div class="row" v-if="letter">
            <div id="page" :class="{'page col-6': !this.isColumnHide, 'pr-5 pl-2': this.isColumnHide}">
                <div class="court-letter__top">
                    <div class="court-letter__top-block">
                        <div class="court-letter__top--left">
                            <img class="logo" src="/images/logo-court-letter.png"/>
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
                                ФИБ @{{ currentSelectedCourtRegionName || '________________ ' }} туманлараро судига<br/>
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
                                    Ҳ/р: @{{letter.buyer?.contract?.general_company?.settlement_account}}, МФО: @{{letter.buyer?.contract?.general_company?.mfo}} , <br/>СТИР: @{{letter.buyer?.contract?.general_company?.inn}} <br/>
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
                            &nbsp;&nbsp; @{{letter.buyer?.contract?.general_company?.name_uz}} томонидан товарларни муддатли тўлов асосида тақдим этишнинг
                            оммавий
                            офертаси ва умумий шартлари (кейинги ўринларда -Оферта) ўзининг веб-сайтига жойлаштирилган.
                            Ушбу оммавий шартнома бўйича аризачининг оммавий офертасини акцептлаш мақсадида, аризачи
                            ҳамда @{{letter.buyer?.name}} @{{letter.buyer?.surname}} @{{letter.buyer?.patronymic}} (кейинги ўринларда – Қарздор) ўртасида @{{  moment(letter.buyer?.contract?.confirmed_at, 'DD.MM.YYYY').format('DD.MM.YYYY') }}-йилда
                            @{{letter.buyer?.contract?.id}}-сонли оммавий оферта шартларини қабул қилиш тўғрисидаги акцепт (кейинги ўринларда -
                            Шартнома) имзоланган. @{{letter.buyer?.contract.confirmed_at}} йил кунги @{{letter.buyer?.contract?.id}}-сонли шартноманинг тўлов графигига асосан
                            қарздор шартнома бўйича @{{letter.buyer?.contract.first_payment_date}} йил кунидан @{{letter.buyer?.contract.last_payment_date}} йил кунига қадар ҳар ой
                            @{{parseInt((letter.buyer?.contract?.total / letter.buyer?.contract?.period)).toLocaleString('RU')}}
                            сўмдан, жами @{{(parseInt(letter.buyer?.contract?.total).toLocaleString('RU'))}} сўм миқдоридаги тўлов суммасини тўлаш мажбуриятини олган бўлиб,
                            бугунги кунда ушбу мажбурият лозим даражада бажарилмай келинмоқда.<br/>
                            &nbsp;&nbsp; Жамият томонидан мазкур шартнома асосида ўз мажбурияти бажарилган. Бироқ Қарздор
                            шартнома
                            асосида сотиб олинган маҳсулот учун тўловларни белгиланган муддатларда тўламаслиги оқибатида
                            @{{ letter.buyer?.contract?.payments_sum_balance || 0 }} сўм қарздорлик ва @{{  letter.buyer?.contract?.autopay || 0 }} сўм ундирув харажатлари буйича қарздорлик юзага келган.
                            Шу муносабат билан ҳозирги кунда қарздорнинг шартнома бўйича жами қарздорлиги
                            @{{ letter.buyer?.contract?.payments_sum_autopay || 0 }}
                            сўмни ташкил қилади.<br/>
                            &nbsp;&nbsp;Оммавий офертанинг низоларни ҳал қилиш тартиби бўлимига асосан ушбу шартнома
                            юзасидан келиб
                            чиққан низо ҳал қилиш учун <strong>фуқаролик ишлари бўйича Тошкент шаҳар @{{ currentSelectedCourtRegionName || '________________ ' }} туманлараро судига</strong> топширилиши белгилаб қўйилган.<br/>
                            &nbsp;&nbsp;Ўзбекистон Республикаси Савдо-саноат палатаси тўғрисида”ги Қонуннинг 21-моддаси
                            ҳамда
                            Ўзбекистон Республикаси “Давлат божи тўғрисида”ги Қонунининг 9-моддасининг 2-бандини қўллаб,
                            Суддан:
                        </p>
                        <h5 class="font-weight-bolder text-center">С Ў Р А Й Д И:</h5>
                        <p>
                            - Аризани давлат божисиз иш юритишга қабул қилишни;<br/>
                            - @{{letter.buyer?.contract?.general_company?.name_uz}} фойдасига жавобгар @{{letter.buyer?.name}} @{{letter.buyer?.surname}} @{{letter.buyer?.patronymic}} дан @{{ letter.buyer?.contract?.payments_sum_autopay || 0 }}
                            сўм асосий қарз ва  @{{ letter.buyer?.contract?.post_cost || 0 }} сўм почта харажатини, жами @{{ letter.buyer?.contract?.total_max_autopay_post_cost || 0 }} сўм ундиришни;<br/>
                            - Давлат божини Қарздор зиммасига юклашни.<br/>
                        </p>
                        <p class="px-5 pt-3 pb-2">
                            1. Ундирувчининг ЎзР ССПга аъзолик шартномаси ва гувоҳномаси нусхаси;<br/>
                            2. Шартнома нусхаси;<br/>
                            3. Паспорт нусхаси;<br/>
                            4. Ишончнома нусхаси;<br/>
                            5. Почта харажатлари тўлови амалга оширилганлигини тасдиқловчи хужжат.<br/>
                            <br/>
                        </p>

                        <div class="d-flex justify-content-between">
                            <strong class="font-size-18" style="max-width: 300px">
                                @{{position}}
                            </strong>
                            <strong class="font-size-18" style="max-width: 300px">
                                @{{fio}}
                            </strong>
                        </div>
                        <p style="margin: 10px 0 0 0; font-size: 10pt;"><span>Ижрочи:</span> @{{executorName}}</p>
                        <p style="margin: 0; font-size: 10pt;"><span>Тел:</span> @{{executorPhone}}</p>
                    </div>
                </div>
            </div>

            <div :class="{'col-6 pt-4': !this.isColumnHide, 'd-none': this.isColumnHide}">
                <form method="POST" target="_blank" action="{{localeRoute('panel.letterGenerateWordDocument', ["contract" => $contract_id, "notary" => $notary_id])}}">
                    @csrf
                    <div class="form-group">
                        <label >Туман суди</label>
                        <select :class="{'is-invalid': !selectedCourtRegion}" class="form-control modified " required name="selectedCourtRegionId" v-model="selectedCourtRegion">
                            <option :value="null" disabled selected>Выбрать</option>
                            <option :value="region.id" v-for="(region, index) in courtRegions" :key="index">@{{ region.name }}</option>
                        </select>
                        <div class="invalid-feedback">
                            Tuman sudini tanlang
                        </div>
                    </div>

                    <div class="form-group">
                        <label>ФИО</label>
                        <input class="form-control modified" required name="fio" type="text" v-model="fio">
                    </div>
                    <div class="form-group">
                        <label>Лавозим</label>
                        <input class="form-control modified" required name="position" type="text" v-model="position">
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input class="form-control modified" required name="phoneNumber" type="text" v-model="phoneNumber">
                    </div>

                    <div class="d-flex justify-content-end">
                        <button :disabled="!selectedCourtRegion" @click="print" type="button" class="btn btn-primary mr-2"> &check; {{__('app.btn_print')}}</button>
                        <button :disabled="!selectedCourtRegion" type="submit" class="btn btn-word btn-secondary ml-2" >
                            &check; Export to Word
                            <div class="spinner-border "></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script>
        const app = new Vue({
            el: '#court-letter',
            data: {
                // lang,
                letter: null,
                isColumnHide: false,
                fio: 'Б.Алматов',
                // position: 'Бошқарма бошлиғининг ҳуқуқий масалалар бўйича ўринбосари',
                position: 'Бошқарма бошлиғининг биринчи ўринбосари в.в.б.',
                phoneNumber: '+998 (90) 061-57-31',
                selectedCourtRegion: null,
                courtRegions: null,
                executorName: 'Ш. Юлдошов',
                executorPhone: '+998 (95) 202-16-16',
            },
            computed: {
                currentSelectedCourtRegionName: function () {
                    let currentCR = this.courtRegions.find(cr => cr.id === this.selectedCourtRegion);
                    if (currentCR) return currentCR.name
                    return null
                }
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
                        this.courtRegions = response.data?.data.court_regions
                    } catch (err) {
                        console.error(err)
                    }
                },
                print() {
                    if (!this.selectedCourtRegion) return;
                    this.isColumnHide = true;
                    setTimeout(() => {
                        window.print()
                    },0)

                    window.onafterprint = () => {
                        this.isColumnHide = false;
                    }
                },

            },
            created() {
                this.getData();
            },
        })

    </script>

@endsection
