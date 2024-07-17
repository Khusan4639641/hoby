@extends('templates.panel.app')

@section('content')

  <div id="letter" v-if="letter">
    <div class="row">
      <div class="col-md-6">

        <div class="page-a4 second">
          <div class="second-letter-top mb-3 mt-5">
            <h5 class="second-letter-top__title mb-2 text-center font-weight-900 font-size-22" v-html="letter.title"></h5>
            <br>
            <p v-html="letter.subtitle"></p>
          </div>

          <div class="second-letter-body" v-html="letter.body"></div>

          <div class="second-letter-footer" v-model="letter.body">

            <p class="second-letter-footer__mark">
              <strong>Зарегистрировано в реестре №: @{{ letter.uniqueNum }}</strong>
            </p>

            <p class="second-letter-footer__debt mt-3">
              Взыскано государственной пошлины – @{{ letter.expense }}
            </p>
            <p class="second-letter-footer__confirm">
              Взыскана установленная плата за совершение дополнительных действий технического характера – @{{ letter.fee }}
            </p>


            <div class="second-letter-footer__fio d-flex justify-content-between" style="margin-top: 100px">
              <h4 class="font-weight-900">Нотариус</h4>
              <h4 class="font-weight-900">@{{ letter.shortFio.toUpperCase() }}</h4>
            </div>

          </div>

        </div>

      </div>

      <div class="col-md-6 info">

        <div class="info">
          <div class="form-group">
            <label for="">Название</label>
            <input class="form-control mb-2 " type="text" v-model="letter.title" >
          </div>

          <div class="mb-2">
            <label for="">Информация</label>
            <vue-editor id="subtitle" v-model="letter.subtitle"></vue-editor>
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
            <input class="form-control mb-2 " type="text" v-model="letter.expense">
          </div>

          <div class="form-group">
            <label for="">Взыскана установленная плата</label>
            <input class="form-control mb-2 " type="text" v-model="letter.fee">
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
                  'Content-Language': 'ru' // temporary solution: coz static link with UZ
                },
              },
            )
            const resp = data.data
            this.letterData = resp
            this.buyer = resp.buyer
            this.generalCompany = resp.buyer.contract.general_company
            this.letters = resp.buyer.contract.letters
            this.notary = resp.buyer.contract.notary_setting
            this.letters = resp.buyer.contract.letters
          } catch (err) {
            console.error(err)
          }
        },
      },
      async created() {
        this.apiToken = Cookies.get('api_token')
        await this.getData()
        this.letter = {
          title: 'ИСПОЛНИТЕЛЬНАЯ НАДПИСЬ',

          subtitle: `
            Республика Узбекистан, г.Ташкент.
            <strong>${this.letterData?.current_date_string.charAt(0).toUpperCase() + this.letterData?.current_date_string.slice(1)}.</strong>`,

          body: `
            <p style="text-indent: 100px">
                Я, ${this.notary?.surname} ${this.notary?.name} ${this.notary?.patronymic}, нотариус ${this.notary?.region ?? '<strong>адрес натариуса не найден</strong>'}, занимающийся частной практикой, нотариальная контора расположенной по адресу: ${this.notary?.address ?? '<strong>адрес натариуса не найден</strong>'},  на основании статей 76-80 Закона Республики Узбекистан «О нотариате», а также глава III Перечня документов, по которым взыскание задолженности производится в бесспорном порядке на основании исполнительных надписей совершаемых нотариусами, утвержденного Постановлением Кабинета Министров Республики Узбекистан от 18.01.2002 года № 26.
           </p>
            <p class="text-center mt-3 mb-3">
                <strong>Предлагаю по настоящему документу взыскать</strong></p>
            <p>
            <strong>с должника</strong> – ${this.buyer?.surname?.toUpperCase()} ${this.buyer?.name?.toUpperCase()} ${this.buyer?.patronymic?.toUpperCase()}, ${this.buyer?.birth_date} г.р. ${this.buyer?.personals.passport_number}, ПИНФЛ: ${this.buyer?.personals.pinfl ?? '<strong>не найден</strong>'}, ${this.buyer?.personals.passport_date_issue ?? '<strong>срок паспорта не найден,</strong>'} зарегистрированный по адресу: ${this.buyer.addresses.registration_address.address?.toUpperCase()}.
            </p>
            <p class="mt-2">
              <strong>в пользу взыскателя </strong> – ${this.generalCompany.name_uzlat}, местонахождение: ${this.generalCompany.address}, ИНН-  ${this.generalCompany.inn}. Расчетный счет для взыскания:  ${this.generalCompany.settlement_account}; МФО: ${this.generalCompany.mfo}.
            </p>
            <p class="mt-2">
              <strong>не уплаченную в срок задолженность c </strong>
              ${moment(this.buyer?.contract.created_at, 'DD.MM.YYYY').format('DD.MM.YYYY')} до ${this.buyer.contract.expiration_date} по договору купли-продажи товара в рассрочку № ${this.buyer.contract.id}  от ${moment(this.buyer?.contract.created_at, 'DD.MM.YYYY').format('DD.MM.YYYY')} общей суммы задолженности в размере –  <strong>${this.buyer?.contract.balance} (${this.buyer?.contract.balance_string})</strong>, а также расходы, связанные с совершением исполнительной надписи нотариуса в размере – ${this.buyer.contract?.expense_and_notary_fee} (${this.buyer.contract?.expense_and_notary_fee_string}). Итого, сумма подлежащая взысканию составляет- ${this.buyer?.contract.total_expense} (${this.buyer?.contract.total_expense_string}).
            </p>
            <p style="text-indent: 45px;" class="mt-2">
              Настоящая исполнительная надпись совершена ${moment(Date.now()).format('DD.MM.YYYY')} года и вступает в силу со дня её совершения.
            </p>
            <p style="text-indent: 45px;" class="mt-2">
              Исполнительная надпись может быть предъявлена к принудительному исполнению в течение трёх лет со дня ее совершения.
            </p>
            <div class="mt-5">
              <p> Республика Узбекистан, г.Ташкент.</p>
              <p>${this.letterData?.current_date_string}.</p>
            </div>
        `,
          uniqueNum: `${this.notary?.letter_base_unique_number}_________`,
          expense: `${this.buyer?.contract.expense} сум (${this.buyer?.contract.expense_string}).`,
          fee: `${ this.notary?.fee } (${ this.notary?.fee_string }).`,
          shortFio: `${this.notary?.surname} ${this.notary?.name?.slice(0, 1)}. ${this.notary?.patronymic?.slice(0, 1)}`,
        }
      }
    })


  </script>


@endsection
