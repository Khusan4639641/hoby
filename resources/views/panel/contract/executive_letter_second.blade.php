@extends('templates.panel.app')

@section('content')

  <div id="letter" v-if="letter">
    <div class="row">
      <div class="col-md-6">

        <div class="page-a4">
          <div class="executive-top">
            <h2 class="executive-top__title" v-html="letter.title"></h2>
            <div class="executive-top__subtitle" v-html="letter.subtitle"></div>
            <div class="executive-top__address" v-html="letter.address"></div>
          </div>

          <div class="executive-body" v-html="letter.body"></div>

          <div class="executive-footer" v-model="letter.body">
            <div class="executive-footer__fio d-flex justify-content-around mt-4">
              <h3>Нотариус</h3>
              <h3>@{{ letter.shortFio }}</h3>
            </div>

          </div>

        </div>

      </div>

      <div class="col-md-6 info">
        <div class="info">
          <div class="form-group">
            <label for="">Название</label>
            <input class="form-control mb-2 " type="text" v-model="letter.title">
          </div>
          <div class="form-group">
            <label for="">Информация</label>
            <input class="form-control mb-2 " type="text" v-model="letter.subtitle">
          </div>
          <div class="form-group">
            <label for="">Информация о нотариусе</label>
            <textarea class="form-control mb-2 " type="text" v-model="letter.address"></textarea>
          </div>
          <div class="form-group">
            <label for="">ФИО нотариуса</label>
            <input class="form-control mb-2 " type="text" v-model="letter.shortFio">
          </div>
          <vue-editor class="form-group" v-model="letter.body"></vue-editor>
        </div>

        <hr>
        <div class="d-flex justify-content-end">
          <button @click="print" class="btn btn-primary mr-2"> &check; {{__('app.btn_print')}}</button>
          <button @click="exportToWord" class="btn btn-secondary"> &check; Export to word</button>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/vue2-editor@2.3.34/dist/vue2-editor.js"></script>
  <script src="https://www.jqueryscript.net/demo/Export-Html-To-Word-Document-With-Images-Using-jQuery-Word-Export-Plugin/FileSaver.js"></script>
  <script src="https://www.jqueryscript.net/demo/Export-Html-To-Word-Document-With-Images-Using-jQuery-Word-Export-Plugin/jquery.wordexport.js"></script>

  <script>
    const lang = @json(config('app.locale'));
    const app = new Vue({
      el: '#letter',
      data: {
        lang,
        expense: null,
        letterData: null,
        notary: null,
        generalCompany: null,
        letters: null,
        buyer: null,
        letter: null,
      },
      methods: {
        exportToWord() {
          $(".page-a4").wordExport();
        },
        print() {
          window.print()
        },
        async getData() {
          const contractId = window.location.href.split('/').at(-2)
          const notaryId   = window.location.href.split('/').at(-1)

          try {
            const {data} = await axios.get(`/api/v1/letters/letter-filling-data?contract_id=${contractId}&notary_id=${notaryId}`,
              {
                headers: {
                  Authorization: `Bearer ${this.apiToken}`,
                  'Content-Language': this.lang,
                },
              },
            )
            const resp = data.data
            this.letterData = resp
            this.buyer = resp.buyer
            this.generalCompany = resp.buyer.contract.general_company
            this.letters = resp.buyer.contract.letters
            this.notary = resp.buyer.contract.notary_setting
          } catch (err) {
            console.error(err)
          }
        },
      },
      async created() {
        this.apiToken = Cookies.get('api_token')
        const monthString = new Date().toLocaleString('ru-RU', {month: 'long'})

        await this.getData()

        this.letter = {
          title: 'ИЖРО ХАТИ',

          subtitle: ` Тошкент шаҳар ${this.notary?.region ?? '<strong>манзил топилмади</strong>'}да жойлашган ${this.notary?.address}даги хусусий амалиёт билан шуғулланувчи нотариус ${this.notary?.surname} ${this.notary?.name} ${this.notary?.patronymic}`,

          address: `Ўзбекистон Республикаси, ${this.notary?.region ?? '<strong>манзил топилмади</strong>'}.
             <br>
          ${this.letterData?.current_date_string}.`,

          body: `
            <p>
                Мен – ${this.notary?.region ?? '<strong>манзил топилмади</strong>'}даги хусусий амалиёт билан шуғулланувчи нотариус <strong>${this.notary?.surname} ${this.notary?.name} ${this.notary?.patronymic}</strong> Ўзбекистон Республикаси Фуқаролик кодексининг 422-моддаси, Ўзбекистон Республикаси “Нотариат тўғрисида”ги Қонунининг 76-80-моддалари ҳамда Ўзбекистон Республикаси Вазирлар Маҳкамасининг 2002 йил 18 январдаги 26-сонли қарори билан тасдиқланган “Нотариусларнинг ижро хатларига асосан қарзни ундириш сўзсиз амалга ошириладиган ҳужжатлар рўйхати”га асосан,
            </p>
            <p>
              Тошкент шахар Чилонзор тумани Давлат хизматлар маркази томонидан 29.09.2020 йилда реестр рақами 896956-сон билан рўйхатга олинган Тошкент шаҳар Яккасарой тумани, Ш. Руставели кўчаси, 12-уй манзилда жойлашган <strong>${this.generalCompany?.name_uzlat} (СТИР: ${this.generalCompany?.inn})</strong> томонидан ${this.buyer?.personals.pinfl} тақдим этилган ҳужжатларга кўра,
            </p>
            <p>
              ${moment(this.buyer?.contract.created_at, 'DD.MM.YYYY').format('DD.MM.YYYY')} йилдаги ${this.buyer?.contract.id}-сонли далолатнома бўйича фуқаро ${this.buyer?.addresses.registration_address.address ?? '<strong>манзил топилмади</strong>'} да доимий рўйхатда турувчи, Вактинчалик ишсиз, ${this.buyer?.birth_date} й.т. <strong>${this.buyer?.surname} ${this.buyer?.name} ${this.buyer?.patronymic} </strong> (паспорт ${this.buyer?.personals.passport_number} ${this.buyer?.personals.passport_date_issue ?? '<strong>паспорт муддати топилмади</strong>'} томонидан ${this.buyer?.personals.passport_issued_by ?? '<strong>паспорт тугаш муддати топилмади</strong>'} йилда берилган PINFL  ${this.buyer?.personals.pinfl}) ${moment(this.buyer.contract.confirmed_at, 'DD.MM.YYYY').format('DD.MM.YYYY')} йилдан ${moment(this.buyer.contract.expiration_date, 'DD.MM.YYYY').format('DD.MM.YYYY')} йилгача бўлган муддат ичида келиб чиқган ва ўз вақтида тўланмаган ${this.buyer?.contract.balance} (${this.buyer?.contract.balance_string}). миқдордаги асосий  қарз, ${this.buyer?.contract.expense} (${this.buyer?.contract.expense_string}) миқдордаги давлат божи, ҳуқуқий ва техник тусдаги қўшимча ҳаракатлар амалга оширганлиги учун ${this.notary?.fee} (${this.notary?.fee_string}) миқдордаги пул суммалари ҳисобга олиниб жами ${this.buyer?.contract.total_expense} (${this.buyer?.contract.total_expense_string}) миқдоридаги суммани ${this.generalCompany.name_uzlat}нинг ТОШКЕНТ Ш. АТ "Капиталбанк" ОФИСИДАГИ ҳисоб рақами ${this.generalCompany.settlement_account}, МФО ${this.generalCompany.mfo} ҳисобига қарздордан ундирилсин.
           </p>
            <br>
            <div class="body__info">
               <p>Ушбу ижро хати ${moment(Date.now()).format('YYYY')} йилнинг ${moment(Date.now()).format('DD')} ${monthString} куни ёзилди.
              </p>
              <p>Реестр ${this.notary.letter_base_unique_number}_________ рақами билан қайд қилинган.</p>
              <p class="main-text__item-mini main-text__item">
                Давлат божи: ${this.buyer?.contract.expense} (${this.buyer?.contract.expense_string}).
              </p>
              <p>
                Нотариуснинг пуллик хизматлари : ${this.notary?.fee} (${this.notary?.fee_string}).
              </p>
            </div>
           `,
          shortFio: `${this.notary?.surname} ${this.notary?.name?.slice(0, 1)}. ${this.notary?.patronymic?.slice(0, 1)}`,
        }
      }
    })


  </script>


@endsection




