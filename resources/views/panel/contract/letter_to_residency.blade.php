@extends('templates.panel.app')
@section('title', __('panel/contract.template_residency_letter'))

@section('content')
<style>

  @media print {
    body {
      max-height: 1132px !important;
      page-break-after: auto;
      top: 0;
      margin: -2cm 0 0 0 ;
      padding: 0;
    }
    * {
      margin: 0;
      padding: 0;
      border: none;
    }
    .letter {
      line-height: 16px !important;
    }
    @page {
      size: auto;
      max-height: 1132px !important;
      margin: 0;
      padding: 0;
    }
  }
  .btn:focus {
    border: 1px solid transparent ;
  }
  .content .center .center-body {
    padding: 0;
    background: transparent;
  }
  #letter_to_residency .info {
    background-color: #fff;
    padding: 2rem;
    border-radius: 16px;
    border: 1px #d3d3d345 solid;
    box-shadow: var(--card-shadow);
  }
  .page-a4 {
      font-size: 14.5pt;
      font-family: "Times New Roman", monospace;
      /* min-height: 29.7cm; */
      padding: 2cm;
      margin: 0 auto;
      border-radius: 16px;
      background: white;
      border: none;
      box-shadow: none;
  }
  .page-a4-container {
    /* max-height: 1132px !important; */
    border: 1px #d3d3d345 solid;
    border-radius: 16px;
    background: white;
    box-shadow: var(--card-shadow);
  }
  .info__images{
    margin-bottom: 36px;
    gap: 16px !important;
    padding: 0 !important;
    background: transparent !important;
  }
  .info__images img.no-image {
    background-color: #F6F6F6 !important;
  }
  .info__images img {
    cursor: pointer;
    width: calc(50% - 8px) !important;
    border: 1px #d3d3d31f solid;
    border-radius: 8px;
  }
  .my-id-form {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: space-between;
    border: 1px dashed var(--orange);
    padding: 1.5rem;
    margin-top: 1rem;
    border-radius: 8px;
    background-color: #6610f50f;
    margin-bottom: 36px;
  }
  .my-id-form .my-id-txt{
    color: var(--orange);
  }
  .my-id-form .my-id-txt h4{
    font-weight: 700;
    margin: 0;
  }
  .my-id-form .my-id-txt p{
    margin: 0
  }
  h3.section-title {
    font-style: normal;
    font-weight: 700;
    font-size: 24px;
    line-height: 28px;
    color: #1E1E1E;
    margin-bottom: 16px;
  }
  .form-group label {
    font-style: normal;
    font-weight: 400;
    font-size: 15px;
    line-height: 24px;
    letter-spacing: 0.01em;
    color: #2A2A2A;
  }
  .form-control.modified {
    padding: 12px 16px !important;
  }
  .spinner-border {
      width: 20px;
      height: 20px;
      border: 1px solid currentColor;
      border-right-color: transparent;
  }
  .v-enter-active {
    animation: fade-in .5s ease
  }
  .v-leave-active {
    animation: fade-out .5s ease
  }
  .modal-backdrop.show {
      opacity: .2;
  }
  .modal-content {
      border: none !important;
      border-radius: 16px;
      outline: 0;
      box-shadow: 0px 16px 20px rgb(0 0 0 / 20%);
  }
  .modal__title{
    font-style: normal;
    font-weight: 700;
    font-size: 18px;
    line-height: 19px;
    color: #1E1E1E;
  }
  .dot-line {
    display: flex;
  }
  .dot-line.before::before{
    content: '';
    flex: 1;
    border-bottom: 1px dashed #1e1e1e77
  }
  .dot-line.after::after{
    content: '';
    flex: 1;
    border-bottom: 1px dashed #1e1e1e77;
  }
  .btn.disabled{
    cursor: not-allowed;
    pointer-events: none;
    opacity: .65;
  }
  .form-control.modified.is-invalid {
    box-shadow: none !important;
    border: 1px solid #ff97a1 !important;
  }
  select.form-control.modified {
    cursor: pointer;
  }
  @keyframes fade-in {
    from {
      transform: scale(.8);
      opacity: 0;
    }
    to {
      transform: scale(1);
      opacity: 1;
    }
  }
  @keyframes fade-out {
    from {
      transform: scale(1);
      opacity: 1;
    }
    to {
      transform: scale(.8);
      opacity: 0;
    }
  }
</style>
  <div id="letter_to_residency"  v-if="generalCompany">
    <div class="row">
      <div class="col-6">
        <div class="page-a4-container " :class="{'border-0 shadow-none': printing}">
          <div class="page-a4 residency" id="a4-doc" :class="{'border-0 pb-0 shadow-none': printing}">

            <section class="topside">
              <div class="topside__top mb-3">
                <h2>@{{ generalCompanyName }}</h2>
                <div class="topside__top-style">
                  <hr>
                  <hr>
                  <hr>
                </div>
                <h5 class="topside__top-address">
                    {{__('panel/contract.address')}}: @{{generalCompany?.address }}, р.с:  @{{ generalCompany.settlement_account }}, в Оперу АКБ «Капиталбанк», МФО: @{{ generalCompany.mfo }}; STIR (ИНН): @{{ generalCompany.inn }}; ОКЭД: @{{ generalCompany.oked }};
                </h5>
              </div>

              <div class="topside__info d-flex align-items-center justify-content-between">
                <div class="topside__info-date">
                  <div>№ @{{ buyer.contract?.recovery?.id ?? "____"}}</div>
                  <div>{{ __('panel/contract.pretension_date') }} @{{ moment(Date.now()).format('DD.MM.YYYY') }}</div>
                </div>

                <div class="topside__info-buyer" style="width: 50%;">
                  <span>@{{ buyer.surname }} @{{ buyer.name }} @{{ buyer.patronymic }}</span>
                  <br>
                  <span>@{{ buyer.addresses.registration_address.address }}</span>
                </div>
              </div>

            </section>

            <section v-if="lang == 'ru'" class="letter">
              <h2 class="letter__title font-weight-600">{{ __('panel/contract.title') }}</h2>

              <p>
                Уважаемый(-ая) <span>@{{ buyer.surname }} @{{  buyer.name }} @{{ buyer.patronymic}}</span> сообщаем Вам о наличии за
                Вами
                задолженности,
                возникшей по Договору № <span>@{{ buyer.contract.id }}</span> от <span>@{{ moment(buyer.contract.created_at, 'DD.MM.YYYY').format('DD.MM.YYYY') }}</span>
                года, заключенного с
                компанией @{{ generalCompanyName }}.
              </p>
              <p>
                Несмотря на факт исполнения нашим предприятием (resus NASIYA/test) обязательств в сроки и в
                соответствии с требованиями, предусмотренными в Оферте и договоре № <span>@{{ buyer.contract.id }}</span> от <span>@{{ moment(buyer.contract.created_at, 'DD.MM.YYYY').format('DD.MM.YYYY') }}</span>
                года, до сегодняшнего дня с вашей стороны не была произведена оплата поставленного товара.
              </p>
              <p>
                В результате несвоевременного выполнения своих обязательств, с вашей
                стороны имеется задолженность сроком <span>@{{ getDaysText(buyer.contract.expired_days) }}</span>, которая на <span>@{{ moment(Date.now()).format('DD.MM.YYYY') }}</span> составляет <span>@{{ buyer.contract.debts_amount }}</span> сум,
                в связи с чем, согласно ст.242 Гражданского кодекса Республики Узбекистан, мы настоятельно просим произвести оплату в трёхдневный срок.
              </p>
              <p>
                В случае неисполнения Вами своих обязательств, мы будем вынуждены обратиться в  нотариальную контору
                или в гражданский суд для взыскания долга в размере до <span>@{{ buyer?.contract?.total_max_autopay_post_cost || 0 }}</span>
                сум, что в свою очередь будет включать в себя задолженность на сумму госпошлины, почтовых расходов,
                понесенных убытков (в том числе упущенной выгоды) с Вас либо за счёт Вашего имущества.
              </p>
              <p>
                Вы можете оплатить задолженность путем пополнения своего ID ( № <span>@{{buyer.phone}}</span> ) платежными системами
                CLICK, PAYME, APELSIN (resus BANK) или по следующим реквизитам в любом отделении Банка:
              </p>

            </section>

            <section v-else class="letter">
              <h2 class="letter__title font-weight-600">{{ __('panel/contract.title') }}</h2>
              <p>

                Hurmatli <span>@{{ buyer.surname }} @{{  buyer.name }} @{{ buyer.patronymic}}</span> , Siz tomondan <span>@{{ buyer.contract.id }}</span>-sonli
                shartnoma bo'yicha @{{ generalCompanyName }} oldida <span>@{{ buyer.contract.debts_amount }}</span> so’m qarzdorlik
                mavjudligi haqida ma’lum qilamiz.
              </p>

              <p>Kompaniyamiz (resus NASIYA/test)<span> @{{ moment(buyer.contract.created_at, 'DD.MM.YYYY').format('DD.MM.YYYY') }}</span> yil <span
                  class="font-weight-bold">@{{ buyer.contract.id }}</span>-sonli Shartnomada ko‘zda tutilgan o‘z
                majburiyatlarini vaqtida talablarga muvofiq bajargan bo‘lishiga qaramasdan,
                shu kungacha yetkazib berilgan tovar(lar) uchun Siz tomondan qarzdorlik yuzaga kelgan.</p>

              <p>Majburiyatlaringizni o'z vaqtida bajarmaganligingiz natijasida sizdan <span>@{{ moment(Date.now()).format('DD.MM.YYYY') }}</span>
                yil holatiga ko'ra <span>@{{ buyer.contract.expired_days }} kun</span> kechiktirganingiz tufayli hozirda @{{
                buyer.contract.debts_amount }} so'm qarzdorlik yuzaga kelganligi sababli
                Sizdan 3 (uch) kun ichida to'lovni amalga oshirishni so'raymiz.</p>

              <p>Agar Siz o'z majburiyatlaringizni bajarmasangiz yani, <span>@{{ buyer.contract.debts_amount }}</span>
                so'm qarzdorlikni to'lamasangiz, <span>@{{ buyer?.contract?.total_max_autopay_post_cost || 0 }}</span> so'm miqdoridagi butun asosiy
                qarzni notarial idoraga yoki fuqarolik sudiga murojaat qilib undirish ishlarini amalga oshirishga majbur bo’lamiz,
                bu esa o'z navbatida qarzni davlat boji va boshqa xarajatlarni hisobga olgan holda kattalashishiga olib
                keladi va sizdan yoki mol-mulkingiz hisobidan o'rnatilgan tartibda undiriladi.</p>


              <p>Qarzni ID ( № <span>@{{ buyer.phone }}</span> ) ni CLICK, PAYME, APELSIN (resus BANK) to'lov tizimlari bilan
                to'ldirib yoki Bankning istalgan filialida quyidagi ma'lumotlardan foydalangan holda to'lashingiz mumkin:
              </p>
            </section>

            <section class="letter">
                <p><span>{{ __('cabinet/profile.work_company') }}</span> : @{{ generalCompanyName }}</p>
                <p><span>AKB «Kapitalbank»</span></p>
                <p><span>{{__('panel/contract.payment_account')}}: </span> @{{ generalCompany.settlement_account }}</p>
                <p>в Центральном отделении АКБ «Kapitalbank» г.Ташкент</p>
                <div class="d-flex align-items-center">
                  <p><span>{{__('offer.seller_mfo')}}: </span>@{{ generalCompany.mfo }};</p>
                  <p style="text-indent: 0"><span>{{__('offer.seller_inn')}}: </span>@{{ generalCompany.inn }};</p>
                  <p style="text-indent: 0"><span>ОКЭД: </span> @{{ generalCompany.oked }};</p>
                </div>
              <p>{{ __('panel/contract.payment_note') }}</p>

              <div class="bottomside__register d-flex justify-content-between">
                <p><span>{{ __('panel/contract.director') }} @{{ generalCompanyName }}</span></p>
                <img
                  :src="generalCompany.stamp"
                  alt="stamp"
                  class="bottomside__register-stamp"
                >
                <p><span>@{{ generalCompany.director_uzlat }}</span></p>
              </div>
            </section>

            <p class="bottomside__number">Call Center: @{{ callCenter }}</p>

          </div>
        </div>
        <div v-if="letters.length"  v-for="(letter, index) in letters" class="page-a4 mt-2">
          <div class="contract-letters mt-2">
            <div class="ticket mt-5">
              <h4>Ягона миллий тизим орқали юборилган жўнатмалар учун квитанция</h4>
              <img :src="mailStamp" alt="mail-stamp">
              <p>Жўнатма рақами: @{{ letter.Id }}</p>
              <p>Жўнатилган вақт: @{{ moment(letter.CreatedOn).format('YYYY-MM-DD') }}</p>
              <p class="mb-2" style="line-height: 25px">Кимга: @{{ letter.Receiver }}</p>
              <p style="line-height: 25px">Қаерга: @{{ letter.Region.Name }} @{{ letter.Area.Name }} @{{ letter.Address }} </p>
            </div>
          </div>
        </div>

      </div>

      <div class="col-6" v-if="!printing">
        <div class="info">
          <div class="info__images d-flex">
            <img
              v-if="buyer.personals && buyer.personals.passport_first_page.path"
              id="passport_first_page"
              @click="showPhotoViewer"
              :src="buyer.personals.passport_first_page.path"
            >
            <img
              v-else
              src="{{ asset('/images/images/media/noimage.svg') }}"
              alt="no-image"
              class="no-image bg-light "
            >

            <img
              v-if="buyer.personals && buyer.personals.passport_with_address.path"
              id="passport_with_address"
              @click="showPhotoViewer"
              :src="buyer.personals.passport_with_address.path"
            >

            <img v-else
                 src="{{ asset('/images/images/media/noimage.svg') }}"
                 alt="no-image"
                 class="no-image bg-light "
            >
          </div>
            {{--     Myid form link       --}}
            <div v-if="myId?.my_id" class="my-id-form">
              <div class="my-id-txt">
                <h4>Анкета My ID</h4>
                <p>Форма №1</p>
              </div>
              <a  class="btn px-3 d-inline-flex align-items-center justify-content-between btn-orange" :href="`/uz/panel/contracts/myid/form-1/${myId?.my_id}/${contractId}`" target="_blank">
                Посмотреть
              </a>
            </div>

            <h3 class="section-title" >{{__('billing/profile.btn_edit_data')}}</h3>


          <div class="form-group mb-3">
            <label>{{ __('panel/letters.enter_address') }}</label>
            <textarea
            :disabled="saveAddressLoader"
              class="form-control modified"
              v-model="buyerAddress"
              placeholder="{{ __('panel/letters.enter_address_example') }}"
            ></textarea>
          </div>


          <div class="d-flex justify-content-between">
            <div class="left">
              <button class="btn px-3 d-inline-flex align-items-center justify-content-between btn-orange-light" @click="printDocument">
                <svg class="mr-2" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M7 17.4795H6C4.343 17.4795 3 16.2575 3 14.7495V10.7685C3 9.26147 4.343 8.03847 6 8.03847H18.667C20.324 8.03847 21.667 9.26047 21.667 10.7685V14.7495C21.667 16.2565 20.324 17.4795 18.667 17.4795H17.667M7 5.41847V3.60547H17.666V5.41847M5.94859 11.0151L5.9504 11.0122M7 14.5645H17.667V20.3935H7V14.5645Z" stroke="currentColor" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{__('app.btn_print')}}
              </button>
            </div>
            <div class="right">
              <button class="btn px-3 d-inline-flex align-items-center justify-content-between btn-orange-light" :class="{'disabled':saveAddressLoader}"  @click="saveAddress({ address: buyerAddress })">
                <div v-if="saveAddressLoader" class="spinner-border mr-2"></div>
                <svg v-else class="mr-2" xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 25 25" fill="none">
                  <path d="M12.6593 7.46428L15.1447 9.94963M4.18585 15.9369L15.6384 4.48438L20.2179 9.06393L8.76541 20.5165H4.18463L4.18585 15.9369Z" stroke="currentColor" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{__('panel/letters.change_address')}}
              </button>
              <button style="padding-left: calc(2rem + 16px)!important;" class="position-relative btn px-3 ml-2 d-inline-flex align-items-center justify-content-between btn-orange" :disabled="!buyer.addresses?.registration_address?.address?.length" data-toggle="modal" data-target="#sendLetterModal">
                <svg style="position: absolute;left:1rem;" class="mr-2" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M5.35303 15.9993V18.3803C5.35303 19.1233 6.13503 19.6073 6.80003 19.2743L21.353 11.9993L6.80003 4.72428C6.13503 4.39128 5.35303 4.87528 5.35303 5.61828V11.9993H12.02" stroke="currentColor" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{__('panel/letters.send_letter')}}
              </button>
            </div>
          </div>

        </div>
      </div>


      <div class="modal fade" id="sendLetterModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <form @submit.prevent="sendLetter('letter-to-residency')" class="modal-content">
            <div class="modal-header p-4">
              <h5 class="modal__title m-0">{{__('panel/letters.modal_create_letter')}}</h5>
            </div>

            <div class="modal-body p-4">
              <table class="w-100 table-borderless mb-3">
                <tr>
                  <th class="py-1"><div class="dot-line after">{{__('panel/letters.receiver')}}:</div></th>
                  <td class="py-1 text-right"><div class="dot-line before">@{{ buyer.surname }} @{{  buyer.name }} @{{ buyer.patronymic}}</div></td>
                </tr>
                <tr>
                  <th class="py-1"><div class="dot-line after">{{__('panel/letters.address')}}:</div></th>
                  <td v-if="buyer.addresses.registration_address.postal_region && buyer.addresses.registration_address.postal_area" class="py-1 text-right">
                    <div class="dot-line before">
                      @{{ buyer.addresses.registration_address.postal_region?.name }}  @{{ buyer.addresses.registration_address.postal_area?.name }}
                    </div>
                  </td>
                  <td v-else class="py-1 text-right"><div class="dot-line before"><span class="text-muted">Не указан</span></div></td>
                </tr>
              </table>
              <hr>
              <div class="form-group">
                <label>{{__('panel/letters.region')}}:</label>
                <select :disabled="sendLetterLoader" :class="{'is-invalid': !selectedRegion}" class="form-control regions modified" @change="onSelectChange($event)">
                  <option value="" disabled selected>{{__('panel/letters.choose_region')}}</option>
                  <option
                    v-for="(postalRegion, index) in postalRegions "
                    :selected="postalRegion.external_id === selectedRegion"
                    :key="index"
                    :value="postalRegion.external_id"
                  >
                    @{{postalRegion.name}}
                  </option>
                </select>
                <div v-if="!selectedRegion" class="invalid-feedback">
                  Выберите регион
                </div>
              </div>
              <div class="form-group">
                <label>{{__('panel/buyer.address_area')}}:</label>
                <select @change="areaSelected($event)" :disabled="sendLetterLoader || !selectedRegion" :class="{'is-invalid': !selectedArea && selectedRegion}" class="form-control areas modified" >
                  <option value="" disabled selected>{{__('panel/letters.choose_area')}}</option>
                  <option
                    v-for="(postalArea, index) in filteredAreas"
                    :selected="postalArea.external_id === selectedArea"
                    :key="index"
                    :value="postalArea.external_id"
                  >
                    @{{ postalArea.name }}
                  </option>
                </select>
                <div v-if="!selectedArea && selectedRegion" class="invalid-feedback">
                  Выберите район
                </div>
              </div>

            </div>

            <div class="modal-footer px-4 py-3">
              <button  class="m-0 btn px-3 d-inline-flex align-items-center justify-content-between btn-orange-light" :class="{'disabled': sendLetterLoader}" type="button" data-dismiss="modal">
                {{__('app.btn_cancel')}}
              </button>
              <button type="submit" style="padding-left: calc(2rem + 16px)!important;" class="m-0 position-relative btn px-3 ml-2 d-inline-flex align-items-center justify-content-between btn-orange" :class="{'disabled': (sendLetterLoader || !selectedRegion || !selectedArea)}">
                <div v-if="sendLetterLoader" style="position: absolute;left:1rem;" class="spinner-border mr-2"></div>
                <svg v-else style="position: absolute;left:1rem;" class="mr-2 " width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M5.35303 15.9993V18.3803C5.35303 19.1233 6.13503 19.6073 6.80003 19.2743L21.353 11.9993L6.80003 4.72428C6.13503 4.39128 5.35303 4.87528 5.35303 5.61828V11.9993H12.02" stroke="currentColor" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{__('panel/buyer.send')}}
              </button>
            </div>
          </form>
        </div>
      </div>


    </div>

  </div>
  <script>
    const lang = window.Laravel.locale
    const mailStamp = '{{ asset('images/logo_uzpost.png')}}';

    const letter_to_residency = new Vue({
        el: "#letter_to_residency",

        data: {
            printing:false,
            saveAddressLoader: false,
            sendLetterLoader: false,
            mailStamp,
            apiToken: '',
            selectedArea: '',
            selectedRegion: '',
            filteredAreas: '',
            postalAreas: '',
            postalRegions: '',
            letters: '',
            lang,
            client: '',
            company: {},
            isLoading: false,
            buyer: null,
            generalCompany: null,
            callCenter: null,
            buyerAddress: '',
            myId: null,
            contractId: null
        },
        computed: {
          generalCompanyName(){
            if (lang == 'uz') return this.generalCompany?.name_uzlat
            return this.generalCompany?.name_ru
          }
        },
        methods: {

            getDaysText(number) {
                if (!String(number).length) return ' дней'
                if (number === 0) {
                    return "0 дней";
                } else if (number % 10 === 1 && number % 100 !== 11) {
                    return number + " день";
                } else if ([2, 3, 4].includes(number % 10) && ![12, 13, 14].includes(number % 100)) {
                    return number + " дня";
                } else {
                    return number + " дней";
                }
            },
            onSelectChange(event) {
                this.selectedRegion = event.target.value
                this.filteredAreas = this.postalAreas.filter(area => area.postal_region_id == this.selectedRegion).sort((a, b) => a.name.localeCompare(b.name))
                this.selectedArea = ''
            },
            areaSelected(event) {
              this.selectedArea = event.target.value
            },
            showPhotoViewer(event) {
                new PhotoViewer([{src: event.target.src, title: event.target.id}]);
            },

            printDocument() {
                polipop.closeAll();
                // window.print();
                this.printing = true
                  setTimeout(() => {
                      window.print()
                  }, 0)
                  window.onafterprint = () => {
                        this.printing = false;
                }

            },

            async checkMyId(buyer) {
                let checkMyStatus = new FormData()
                checkMyStatus.append('api_token', this.apiToken);
                checkMyStatus.append('user_id', buyer.id);

                return axios.post('/api/v1/recovery/myid-status', checkMyStatus)
                    .then(response => response.data)
            },

            async getLetterData() {
                this.contractId = window.location.href.split('/').at(-1)
                try {
                    const {data} = await axios.get(`/api/v1/letters/letter-filling-data?contract_id=${this.contractId}`,
                        {
                            headers: {
                                Authorization: `Bearer ${this.apiToken}`,
                                'Content-Language': this.lang
                            },
                        },
                    )
                    const resp = await data.data
                    this.buyer = resp.buyer;
                    this.notary = resp.buyer.contract.notary_setting
                    this.generalCompany = resp.buyer.contract.general_company
                    this.letters = resp.buyer.contract.letters
                    this.callCenter = resp.callcenter_number
                    this.selectedRegion = this.buyer.addresses.registration_address.postal_region?.external_id
                    this.selectedArea = this.buyer.addresses.registration_address.postal_area?.external_id
                    this.buyerAddress = this.buyer.addresses.registration_address.address

                    // this.filteredAreas = this.postalAreas.filter(area => area.postal_region_id == this.selectedRegion).sort((a, b) => a.name.localeCompare(b.name))

                    // check my id form and show link in page
                    this.myId = await this.checkMyId(resp.buyer);
                } catch (err) {
                    console.error(err)
                }
            },

            async getAreas() {
                try {
                    const {data} = await axios.get(`/api/v1/letters/postal-regions-and-areas`,
                        {
                            headers: {
                                Authorization: `Bearer ${this.apiToken}`,
                                'Content-Language': this.lang
                            },
                        },
                    )
                    this.postalAreas = data.postal_areas
                    this.postalRegions = data.postal_regions.sort((a, b) => a.name.localeCompare(b.name))
                } catch (err) {
                    polipop.add({content: err, title: `Ошибка`, type: 'error'})
                    console.error(err)
                }
            },

            async saveAddress(address) {
                this.saveAddressLoader = true
                const data = {
                    api_token: this.apiToken,
                    buyer_id: this.buyer.id,
                    postal_region: address.postal_region ?? undefined,
                    postal_area: address.postal_area ?? undefined,
                    address: address.address ?? undefined
                }

                try {
                    const {data: response} = await axios.post('/api/v1/buyer/save-address', data, {
                            headers: {
                                Authorization: `Bearer ${this.apiToken}`,
                                'Content-Language': this.lang
                            },
                        }
                      );
                    if (response.status === 'success') {
                      await this.getLetterData()
                      polipop.add({content: response.data[0], title: `Успешно`, type: 'success'})

                    }

                } catch (err) {
                    err.response.data.error.forEach((error) => polipop.add({
                        content: error.text,
                        title: `Ошибка`,
                        type: 'error'
                    }))
                } finally {
                    this.saveAddressLoader = false
                }
            },

            async sendLetter(letter_type) {
                this.sendLetterLoader = true
                const formData = new FormData()

                if (letter_type) formData.append('letter_type', letter_type)

                formData.append('api_token', this.apiToken)
                formData.append('contract_id', this.buyer.contract.id)
                formData.append('postal_region', this.selectedRegion)
                formData.append('postal_area', this.selectedArea)

                try {
                    const {data: resp} = await axios.post('/api/v1/letters/send', formData)
                    if (resp.status === 'success') {
                        polipop.add({content: resp.response.message[0].text, title: `Успешно`, type: 'success'});
                    } else {
                        if (resp.response.code === 404) {
                            polipop.add({content: resp.response.message[0].text, title: `Ошибка`, type: 'error'});
                        }
                        Object.values(resp.response.message).forEach(val => {
                            polipop.add({content: val.text, title: `Ошибка`, type: 'error'});
                        })
                    }

                } catch (err) {
                    console.log(err)
                } finally {
                  this.sendLetterLoader = false
                }
            },
        },
        async created() {
            this.apiToken = Cookies.get('api_token')
            await this.getAreas()
            await this.getLetterData()
        }
    })
</script>


@endsection


