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

          <div class="executive-footer">
            <p class="executive-footer__mark">
              Уникальный номер нот.действия: @{{ letter.uniqueNum }}
            </p>

            <p class="executive-footer__debt">
              Взыскано государственной пошлины – @{{ letter.debt }}
            </p>

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
          <div class="form-group">
            <label for="">Уникальный номер нот.действия</label>
            <input class="form-control mb-2 " type="text" v-model="letter.uniqueNum">
          </div>
          <div class="form-group">
            <label for="">Взыскано государственной пошлины</label>
            <input class="form-control mb-2 " type="text" v-model="letter.debt">
          </div>
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
  <script
    src="https://www.jqueryscript.net/demo/Export-Html-To-Word-Document-With-Images-Using-jQuery-Word-Export-Plugin/FileSaver.js"></script>
  <script
    src="https://www.jqueryscript.net/demo/Export-Html-To-Word-Document-With-Images-Using-jQuery-Word-Export-Plugin/jquery.wordexport.js"></script>

  <script>
    {{--const lang = @json(config('app.locale'));--}}
    const app = new Vue({
      el: '#letter',
      data: {
        // lang,
        expense: null,
        letterData: null,
        notary: null,
        generalCompany: null,
        letters: null,
        buyer: null,
        letter: null,
      },
      computed: {
        suffedName() {
          const sufSurname = this.notary.surname.slice(0, 1)
          let surname = this.notary.surname
          if (sufSurname) {
            if (sufSurname == 'а') {
              surname = this.notary.surname.slice(0, this.notary.surname.length - 1)
              return `${surname.concat('ой')} ${this.notary.name} ${this.notary.patronymic.slice(0, this.notary.patronymic.length - 1).concat('ой')}`
            }
            return `${surname.concat('ым')} ${this.notary.name.concat('ом')} ${this.notary.patronymic}`
          }
        },
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
                  'Content-Language': 'ru', // temporary  solution: coz on link static Uz
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
        }
      },
      async created() {
        this.apiToken = Cookies.get('api_token')
        await this.getData()
        this.letter = {
          title: 'ИСПОЛНИТЕЛЬНАЯ НАДПИСЬ',
          subtitle: `Республика Узбекистан. Город Ташкент. ${this.letterData?.current_date_string}`,
          address: `Нотариус, занимающийся частной практикой в  ${this.notary?.region ?? '<strong>адрес не найден</strong>'}, ${this.notary?.surname} ${this.notary?.name} ${this.notary?.patronymic}, расположенный по адресу: ${this.notary?.address}`,
          body: `<p>
            Я, <strong>${this.notary?.surname} ${this.notary?.name} ${this.notary?.patronymic}</strong>, нотариус, занимающийся частной практикой в ${this.notary?.address ? this.notary?.address : '<strong>адрес натариуса не найден</strong>'}, на основании п. 19 ст. 23 Закона РУз «О нотариате» и п. 5 ПРИЛОЖЕНИЯ к постановлению Кабинета Министров РУз от 18 января 2002 года за № 26 «Об утверждении перечня документов, по которым взыскание задолженности производится в бесспорном порядке на основании исполнительных надписей, совершаемыхнотариусами» предлагаю по настоящему документу взыскать  с гражданина(ки) ${this.buyer?.surname} ${this.buyer?.name} ${this.buyer?.patronymic} ${this.buyer?.birth_date} года рождения (паспорт ${this.buyer?.personals.passport_number} выдан ${this.buyer?.personals.passport_date_issue ?? '<strong>срок паспорта не найден</strong>'} года ${this.buyer?.personals.passport_issued_by ?? '<strong>срок паспорта не найден</strong>'} ПИНФЛ ${this.buyer?.personals.pinfl ?? '<strong>не найден</strong>'}) постоянно прописанная по адресу: <span>${this.buyer?.addresses.registration_address.address ?? '<strong>не найден</strong>'} ${this.buyer?.addresses.postal_area}</span>.
          </p>

          <p>
            ${this.generalCompany?.name_uzlat} находящейся по адресу: ${this.generalCompany?.address}. ИНН — ${this.generalCompany?.inn}. Расчетный счет для взыскания: ${this.generalCompany?.settlement_account}, МФО: ${this.generalCompany?.mfo};
          </p>
          <p>не уплаченную в срок задолженность</p>
          <p>по кредитному договору № ${this.buyer?.id} от ${moment(this.buyer?.contract.created_at, 'DD.MM.YYYY').format('DD.MM.YYYY')}</p>
          <p>
            общей суммы задолженности в размере – ${this.buyer?.contract.balance} (${this.buyer?.contract.balance_string}), а также расходы, связанные с совершением исполнительной надписи нотариуса в размере –  ${this.buyer?.contract.expense} (${this.buyer?.contract.expense_string}). А также, расходы связанные с нотариуcом ${this.notary.fee} (${this.notary.fee_string}).
          </p>
          <p>Итого, сумма подлежащая взысканию составляет ${this.buyer?.contract.total_expense} (${this.buyer?.contract.total_expense_string})
</p>
          <p>Сумму расходов, связанных с совершением исполнительной надписи взыскать с должника.</p>
          <p style="text-indent: 0" > Республика Узбекистан, город Ташкент,</p>
          <p style="text-indent: 0">${this.letterData?.current_date_string}.</p>
          <p>Настоящая исполнительная надпись удостоверена мной, ${this.suffedName ? this.suffedName : '_________________________________________________________________________________'}, нотариусом, занимающимся частной практикой в ${this.notary?.region}, расположенной по адресу: ${this.notary?.address}.
        </p>
        `,
          uniqueNum: this.notary.letter_base_unique_number,
          debt: `${this.buyer?.contract.expense} сум (${this.buyer?.contract.expense_string}) + ${this.notary?.fee} (${this.notary?.fee_string}).`,
          shortFio: `${this.notary?.name} ${this.notary?.name?.slice(0, 1)}. ${this.notary?.patronymic?.slice(0, 1)}.`,
        }
      }
    })

  </script>


@endsection
