<div id="form_0" style="display: none">
    <div class="letters-helper"></div>
    <div class="form-group">
        <label>Дней отсрочки</label>
        <select id="day" class="form-control" onchange="setValue(this)">
            <option value="0">Не задано</option>
            @for($i=1;$i<31;$i++)
                <option value="{{$i}}">{{$i}}</option>
            @endfor
        </select>
    </div>
    <div class="form-group">
        <label>Комментарии</label>
        <textarea class="form-control" id="comment_call" placeholder="Комментарии" onchange="setValue(this)"></textarea>
    </div>
</div>

<script>

    let contractsTable,
        status = 3,
        contract_id,
        tabActive = false,
        user_id,
        data = {},
        images = [],
        delaysList = [],
        action = {{$action}},
        recovery = {{$recovery}},
        api_token = '{{Auth::user()->api_token}}',
        server_name = '{{$_SERVER['SERVER_NAME']}}',
        documents = '',
        letter_send = false,         // письмо не сформировано
        can_inv_number = false,     // можно вводить номер инфойса,
        notaryUniqueNumber = '',        // номер испольнительный надписи
        registrationNumber = '';       // польный номер испольнительный надписи

function numberWithSpaces(x) {
    var parts = x.toString().split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    parts[1] = parts[1] > 0 ? parts[1].substr(0, 2) : 0;
    return parts.join('.');
}
function openExportModal(btn) {
    $('#modalExports').modal('show')
}

function setDateMax() {
    //setting max value for contract_date_from
    let dateToValue = $('#contract_date_to').val()
    $('#contract_date_from').attr('max', dateToValue)
}
function setDateMin() {
    //setting min value for contract_date_to
    let dateFromValue = $('#contract_date_from').val()
    $('#contract_date_to').attr('min', dateFromValue)
}
function checkDelayedDays(inp, type) {
    let val = $(inp).val()
    let valTo = $('#delayed_day_to').val()

    if (type == 'from') $('#delayed_day_to').attr('min', val)
    else $('#delayed_day_from').attr('max', val)
}
function downloadExcel(event) {
    event.preventDefault()
}

function fetchRecoveryContractRegions() {
    $('#recovery_regions').prop('disabled', true)
    $('#recovery_regions').html(`
        <option disabled selected>Выберите регион</option>
        <option value="">Все регионы</option>
    `)

    axios.get('/api/v1/letters/postal-regions-and-areas?api_token='+window.globalApiToken)
        .then(({data}) => {
            $('#recovery_regions').prop('disabled', false)
            if (data.postal_regions && data.postal_regions.length) {
                data.postal_regions.forEach(el => {
                    $('#recovery_regions').append(`<option value="${el.katm_region}">${el.name}</option>`)
                });
            } else {
                alert('Ошибка сервера при загрузке регионов');
            }
        })
        .catch(e => {
            $('#recovery_regions').prop('disabled', false)
            alert(e);
        });
}


    $.urlParameters = function (params) {
        var results = new RegExp('[?&]' + params + '=([^&#]*)').exec(
            window.location.href,
        );
        return results ? decodeURI(results[1]) : null;
    };

    $(document).ready(function () {

    // $('#excelForm').on('submit', function(e){
        // e.preventDefault()

        // $('#excelForm button[type="submit"]').prop('disabled', true)
        // let formData = new FormData($('#excelForm')[0])
        // formData.append('api_token', window.globalApiToke)

        // axios.get('/panel/reports/debt-collectors-filtered-export', formData)
        // .then(({data}) => {
        //     $('#excelForm button[type="submit"]').prop('disabled', false)
        // })
        // .catch(e => {
        //     $('#excelForm button[type="submit"]').prop('disabled', false)
        //     alert(e);
        // });

    // })

    fetchRecoveryContractRegions();

    $(document).on('click', '#letter-home', function () {
        letter_send = true;
    });


        recovery = {{$recovery}};

        //Data tables init
        if ($('.contracts .contract-list').length > 0) {

            // window.onhashchange = locationHashChanged;
            contractsTable = $('.contracts .contract-list').dataTable({
                serverSide: true,
                pagingType: "input",
                pageLength: 10,
                info: false,
                lengthChange: false,
                sDom: 'lrtip',
                buttons: [],
                'ajax': function (data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.contracts.list')}}', { // panel.contracts.list
                        api_token: Cookies.get('api_token'),
                        orderByDesc: 'contracts.created_at',
                        list_type: 'data_tables',
                        status: 3,
                        recovery: recovery,
                        action: action,
                        id: $('#contract_id').val(),
                        passport_number: $('#passport_number').val(),
                        fio: $('#buyer_fio').val(),
                        // name__like: $('#buyer_fio').val(),
                        //act_status: act_status,
                        //imei_status: imei_status,
                        //cancel_act_status: cancel_act_status,
                        offset: contractsTable.fnSettings()._iDisplayStart,
                        limit: contractsTable.fnSettings()._iDisplayLength,
                    }, function (res) {
                        //console.log(res);
                        //res = JSON.parse(res);
                        delaysList = res.data.data;
                        callback({
                            recordsTotal: res.data.recordsTotal,
                            recordsFiltered: res.data.recordsTotal,
                            data: res.data.data,
                        });
                        loading(false);
                    });
                },
                'language': {
                    'url': '/assets/json/ru.lang',
                },
                'ordering': false,
                initComplete: function (settings, json) {

                },
            });
            //locationHashChanged();
        }

        //Change status
        $('#contractRecovery a').click(function () {
            const recoveryStatus = $(this).data('status');
            //const user_id = $(this).data('user_id');
            recovery = recoveryStatus === 7 ? [1, 2, 3, 4, 5, 6, 7] : recoveryStatus; // != '' ? recoveryStatus : 0;
            let debtRecovery = recoveryStatus;
            action = $(this).data('action') ?? -1;

            if (!tabActive) contractsTable.DataTable().draw();

            $.ajax({
                headers: {
                    'Content-Language': '{{app()->getLocale()}}',
                    'Accept': 'application/json',
                },
                'url': '/api/v1/recovery/get-debts',
                'type': 'post',
                data: {
                    'recovery': debtRecovery,
                    'action': action,
                    'api_token': '{{Auth::user()->api_token}}',
                    '_token': '{{ csrf_token() }}',
                },
                success: function (result) {
                    if (result.status == 'success') {
                        $('#all_debts').text(' ' + numberWithSpaces(result.debts));
                    }
                },
                error: function (e) {
                    console.log('SERVER ERROR');
                },
            });


        });

        // Search By keyCode and without keyCode
        function searchByKeyup(event, inputId) {
            if (event.keyCode === 13) {
                contractsTable.DataTable().search($(`#dataTablesSearch #${inputId}`).val()).draw();
            } else if (event.keyCode === 27) {
                $(`#dataTablesSearch #${inputId}`).val('');
                contractsTable.DataTable().search('').draw();
            }

            if (event === '') {
                contractsTable.DataTable().search($(`#dataTablesSearch #${inputId}`).val()).draw();
            }
        }

        // search by contract_id
        $('#dataTablesSearch #contract_id')
            .keyup((event) => searchByKeyup(event, event.target.id))
            .parent()
            .find('button')
            .click(() => searchByKeyup('', 'contract_id'));

        // search by passport_number
        $('#dataTablesSearch #passport_number')
            .keyup((event) => searchByKeyup(event, event.target.id))
            .parent()
            .find('button')
            .click(() => searchByKeyup('', 'passport_number'));

        // search by buyer_fio
        $('#dataTablesSearch #buyer_fio')
            .keyup((event) => searchByKeyup(event, event.target.id))
            .parent()
            .find('button')
            .click(() => searchByKeyup('', 'buyer_fio'));

    });

    // recovery 0
    function all(recovery, appendedLinks) {

        if (appendedLinks) {

            $('#form_0').find('.letters-helper').html(appendedLinks);
        }

        return $('#form_0').html();
    }

    // recovery 2
    function lettersToSent(recovery) {
        return `
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" id="letter-home" target="_blank">Скачать письмо домой</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency-2/${contract_id}" target="_blank" id="letter-home-2">Скачать письмо домой 2 ( 61 + дней )</a><br>
        <br>
    <h4>Отсрочка</h4>
    <div class="form-group">
        <label>День</label>
        <select id="day" class="form-control" onchange="setValue(this)">
             <option value="0">Не задано</option>
@for($i=1;$i<31;$i++)
        <option value="{{$i}}">{{$i}}</option>
                  @endfor
        </select>
    </div>
    <div class="form-group">
        <label>Комментарии</label>
        <textarea id="comment_letter" class="form-control" placeholder="Комментарии" onchange="setValue(this)"></textarea>
    </div>
`;
    }

    function lettersToSentMyId(my_id) {
        return `
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" id="letter-home" target="_blank">Скачать письмо домой</a><br>
        <a href="//${server_name}/uz/panel/contracts/myid/form-1/` + my_id + `/${contract_id}" target="_blank">MyID Анкета</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency-2/${contract_id}" target="_blank" id="letter-home-2">Скачать письмо домой 2 ( 61 + дней )</a><br>
        <br>
    <h4>Отсрочка</h4>
    <div class="form-group">
        <label>День</label>
        <select id="day" class="form-control" onchange="setValue(this)">
             <option value="0">Не задано</option>
@for($i=1;$i<31;$i++)
        <option value="{{$i}}">{{$i}}</option>
                  @endfor
        </select>
    </div>
    <div class="form-group">
        <label>Комментарии</label>
        <textarea id="comment_letter" class="form-control" placeholder="Комментарии" onchange="setValue(this)"></textarea>
    </div>
`;
    }

    // recovery 4
    function transferToNotary({
                                  with_invoice_number_inputs = false,
                                  invoice_number_fix = 0,
                                  invoice_number_percent = 0,
                                  notaryId = null,
                                  executiveTemplateNumber
                              }) {
        // dev_nurlan 27.04.2022
        let modal_body = `
      <a href="//${server_name}/${window.Laravel.locale}/panel/contracts/court-contract-print-form/${contract_id}" id="courtContractAnchor" target="_blank">Скачать договор</a><br>
      <a href="//${server_name}/ru/panel/recovery/get-document/${user_id}/passport_first_page" target="_blank">Скачать Паспорт</a><br>
      <a href="//${server_name}/ru/panel/recovery/get-document/${user_id}/passport_with_address" target="_blank">Скачать Прописку</a><br>
      <a href="//${server_name}/ru/panel/recovery/get-document/${contract_id}/act" target="_blank" download="">АКТ</a><br>
      <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" target="_blank">Скачать письмо домой</a><br>
      <a href="//${server_name}/uz/panel/contracts/letter-to-residency-2/${contract_id}" target="_blank" id="letter-home-2">Скачать письмо домой 2 ( 61 + дней )</a><br>
      <a href="//${server_name}/uz/panel/contracts/executive-letter-${executiveTemplateNumber}/${contract_id}/${notaryId}" id="anchor1" data-anchor="1" target="_blank">Исполнительная надпись (ИЖРО ХАТИ)</a><br>
      <a href="//${server_name}/uz/panel/contracts/letter-to-enforcement-agency/${contract_id}" target="_blank">Скачать Сопроводительное письмо в МИБ</a><br>
      <br>
      <select style="display: none;" id="select" name="select" onchange="setNewValue()" class="form-control" required>
         <option disabled selected value>{{__("panel/lawsuit.select")}}</option>
      </select>
      <br>
      <div class="form-group mb-2">
        <label  for="invoice">Загрузить Инвойс</label>
        <input class="border rounded p-2 w-100" id="invoice" type="file" name="image[invoice]" onchange="setImage(this)" required>
      </div>
    `;

        if (with_invoice_number_inputs) {
            modal_body += `
        <div class="form-group">
          <label id="fix_type_label" for="invoice_number_fix">{{__("panel/contract_invoice.enter_invoice_number_fix")}}</label>
          <input id="invoice_number_fix" name="invoice_number_fix" data-user_id="${user_id}" data-contract_id="${contract_id}"
                 type="text" required="true" maxlength="20" class="form-control"
                 pattern="[0-9]{14,20}" title="{{__("panel/contract_invoice.invoice_number_must_be_digits_between")}}">
              <div id="inv_error_msg_1"></div>
        </div>
        `;
        } else {
            modal_body += `
        <div class="form-group">
          <label id="fix_type_label" for="invoice_number_fix">{{__("panel/contract_invoice.invoice_number_fix")}}</label>
          <input id="invoice_number_fix" name="invoice_number_fix" type="text" required="true" class="form-control"
                 disabled value="${invoice_number_fix}">
        </div>
      `;
        }

        modal_body += `
      <div class="form-group mb-2">
        <label for="execute">Загрузить Исполнительная надпись</label>
        <input class="border rounded p-2 w-100" id="execute" type="file" name="image[execute]" onchange="setImage(this)" required>
      </div>
    `;

        if (with_invoice_number_inputs) {
            modal_body += `
        <div class="form-group">
          <label id="percent_type_label" for="invoice_number_percent">{{__("panel/contract_invoice.enter_invoice_number_percent")}}</label>
          <input id="invoice_number_percent" name="invoice_number_percent" data-user_id="${user_id}" data-contract_id="${contract_id}"
                 type="text" required="true" maxlength="20" class="form-control"
                 pattern="[0-9]{14,20}" title="{{__("panel/contract_invoice.invoice_number_must_be_digits_between")}}">
              <div id="inv_error_msg_2"></div>
        </div>
        `;
        } else {
            modal_body += `
        <div class="form-group">
          <label id="percent_type_label" for="invoice_number_percent">{{__("panel/contract_invoice.invoice_number_percent")}}</label>
          <input id="invoice_number_percent" name="invoice_number_percent" type="text" required="true" class="form-control"
                 disabled value="${invoice_number_percent}">
        </div>
        `;
        }

        modal_body += `
      <div class="form-group">
        <label for="registration_number">Номер испольнителный надписи</label>
        <input id="registration_number" name="registration_number" type="number" required="true" class="form-control" value="${notaryUniqueNumber}">
      </div>
    `

        return modal_body;

    }

    function transferToNotaryMyId({
                                      with_invoice_number_inputs = false,
                                      invoice_number_fix = 0,
                                      invoice_number_percent = 0,
                                      notaryId = null,
                                      executiveTemplateNumber,
                                      myId = null
                                  }) {
        // dev_nurlan 27.04.2022
        console.log(myId)
        let modal_body = `
      <a href="//${server_name}/ru/panel/recovery/get-document/${user_id}/passport_first_page" target="_blank">Скачать Паспорт</a><br>
      <a href="//${server_name}/ru/panel/recovery/get-document/${user_id}/passport_with_address" target="_blank">Скачать Прописку</a><br>
      <a href="//${server_name}/ru/panel/recovery/get-document/${contract_id}/act" target="_blank" download="">АКТ</a><br>
      <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" target="_blank">Скачать письмо домой</a><br>
      <a href="//${server_name}/uz/panel/contracts/letter-to-residency-2/${contract_id}" target="_blank" id="letter-home-2">Скачать письмо домой 2 ( 61 + дней )</a><br>
      <a href="//${server_name}/uz/panel/contracts/executive-letter-${executiveTemplateNumber}/${contract_id}/${notaryId}" id="anchor1" data-anchor="1" target="_blank">Исполнительная надпись (ИЖРО ХАТИ)</a><br>
      <a href="//${server_name}/uz/panel/contracts/letter-to-enforcement-agency/${contract_id}" target="_blank">Скачать Сопроводительное письмо в МИБ</a><br>
      <a href="//${server_name}/uz/panel/contracts/myid/form-1/${myId}/${contract_id}" target="_blank">Анкета MyId(Форма №1)</a>
      <br>
      <select style="display: none;" id="select" name="select" onchange="setNewValue()" class="form-control" required>
         <option disabled selected value>{{__("panel/lawsuit.select")}}</option>
      </select>
      <br>
      <div class="form-group mb-2">
        <label  for="invoice">Загрузить Инвойс</label>
        <input class="border rounded p-2 w-100" id="invoice" type="file" name="image[invoice]" onchange="setImage(this)" required>
      </div>
    `;

        if (with_invoice_number_inputs) {
            modal_body += `
        <div class="form-group">
          <label id="fix_type_label" for="invoice_number_fix">{{__("panel/contract_invoice.enter_invoice_number_fix")}}</label>
          <input id="invoice_number_fix" name="invoice_number_fix" data-user_id="${user_id}" data-contract_id="${contract_id}"
                 type="text" required="true" maxlength="20" class="form-control"
                 pattern="[0-9]{14,20}" title="{{__("panel/contract_invoice.invoice_number_must_be_digits_between")}}">
              <div id="inv_error_msg_1"></div>
        </div>
        `;
        } else {
            modal_body += `
        <div class="form-group">
          <label id="fix_type_label" for="invoice_number_fix">{{__("panel/contract_invoice.invoice_number_fix")}}</label>
          <input id="invoice_number_fix" name="invoice_number_fix" type="text" required="true" class="form-control"
                 disabled value="${invoice_number_fix}">
        </div>
      `;
        }

        modal_body += `
      <div class="form-group mb-2">
        <label for="execute">Загрузить Исполнительная надпись</label>
        <input class="border rounded p-2 w-100" id="execute" type="file" name="image[execute]" onchange="setImage(this)" required>
      </div>
    `;

        if (with_invoice_number_inputs) {
            modal_body += `
        <div class="form-group">
          <label id="percent_type_label" for="invoice_number_percent">{{__("panel/contract_invoice.enter_invoice_number_percent")}}</label>
          <input id="invoice_number_percent" name="invoice_number_percent" data-user_id="${user_id}" data-contract_id="${contract_id}"
                 type="text" required="true" maxlength="20" class="form-control"
                 pattern="[0-9]{14,20}" title="{{__("panel/contract_invoice.invoice_number_must_be_digits_between")}}">
              <div id="inv_error_msg_2"></div>
        </div>
        `;
        } else {
            modal_body += `
        <div class="form-group">
          <label id="percent_type_label" for="invoice_number_percent">{{__("panel/contract_invoice.invoice_number_percent")}}</label>
          <input id="invoice_number_percent" name="invoice_number_percent" type="text" required="true" class="form-control"
                 disabled value="${invoice_number_percent}">
        </div>
        `;
        }

        modal_body += `
      <div class="form-group">
        <label for="registration_number">Номер испольнителный надписи</label>
        <input id="registration_number" name="registration_number" type="number" required="true" class="form-control" value="${notaryUniqueNumber}">
      </div>
    `

        return modal_body;

    }


    // recovery 5
    function transferToMIB(recovery) {
        return `
        <a href="//${server_name}/${window.Laravel.locale}/panel/contracts/court-contract-print-form/${contract_id}" id="courtContractAnchor" target="_blank">Скачать договор</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-enforcement-agency/${contract_id}" target="_blank">Скачать Сопроводительное письмо в МИБ</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" target="_blank" id="letter-home">Шаблон письмо домой</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency-2/${contract_id}" target="_blank" id="letter-home-2">Скачать письмо домой 2 ( 61 + дней )</a><br>
        <br>
        <div class="form-group">
            <label>Дата возбуждения дела</label>
            <input id="date" type="date" class="form-control" placeholder="" onchange="setValue(this)" required>
        </div>
        <div class="form-group">
            <label>Номер дела</label>
            <input id="delo" type="text" class="form-control" placeholder="" onchange="setValue(this)" required>
        </div>
        <div class="form-group text-left">
            <label>Ф.И.О исполнителя</label>
            <input id="name" type="text" class="form-control" placeholder="" onchange="setValue(this)" required>
        </div>
        <div class="form-group text-left">
            <label>Телефон исполнителя</label>
            <input id="phone" type="text" class="form-control" placeholder=" " onchange="setValue(this)" required>
        </div>
        <input type="hidden">
    `;
    }

    function transferToMIBMyId(my_id) {
        return `
        <a href="//${server_name}/uz/panel/contracts/letter-to-enforcement-agency/${contract_id}" target="_blank">Скачать Сопроводительное письмо в МИБ</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" target="_blank" id="letter-home">Шаблон письмо домой</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency-2/${contract_id}" target="_blank" id="letter-home-2">Скачать письмо домой 2 ( 61 + дней )</a><br>
        <a href="//${server_name}/uz/panel/contracts/myid/form-1/` + my_id + `/${contract_id}" target="_blank">MyID Анкета</a><br>
        <br>
        <div class="form-group">
            <label>Дата возбуждения дела</label>
            <input id="date" type="date" class="form-control" placeholder="" onchange="setValue(this)" required>
        </div>
        <div class="form-group">
            <label>Номер дела</label>
            <input id="delo" type="text" class="form-control" placeholder="" onchange="setValue(this)" required>
        </div>
        <div class="form-group text-left">
            <label>Ф.И.О исполнителя</label>
            <input id="name" type="text" class="form-control" placeholder="" onchange="setValue(this)" required>
        </div>
        <div class="form-group text-left">
            <label>Телефон исполнителя</label>
            <input id="phone" type="text" class="form-control" placeholder=" " onchange="setValue(this)" required>
        </div>
        <input type="hidden">
    `;
    }

    // recovery 6
    function transferToComplete(recovery) {
        return `
        <a href="//${server_name}/${window.Laravel.locale}/panel/contracts/court-contract-print-form/${contract_id}" id="courtContractAnchor" target="_blank">Скачать договор</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" target="_blank">Скачать письмо домой</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency-2/${contract_id}" target="_blank">Скачать письмо домой 2 ( 61 + дней )</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-enforcement-agency/${contract_id}" target="_blank">Скачать Сопроводительное письмо в МИБ</a>
        <br>
        Подтвердить закрытие договора!
        <input type="hidden" name="confirm-contract">
    `;
    }

    function transferToCompleteMyId(my_id) {
        return `
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" target="_blank">Скачать письмо домой</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-residency-2/${contract_id}" target="_blank">Скачать письмо домой 2 ( 61 + дней )</a><br>
        <a href="//${server_name}/uz/panel/contracts/letter-to-enforcement-agency/${contract_id}" target="_blank">Скачать Сопроводительное письмо в МИБ</a><br>
        <a href="//${server_name}/uz/panel/contracts/myid/form-1/` + my_id + `/${contract_id}" target="_blank">MyID Анкета</a><br>
        <br>
        Подтвердить закрытие договора!
        <input type="hidden" name="confirm-contract">
    `;
    }

    const openModal = debounce_leading(asyncOpenModal);

    async function asyncOpenModal(button, contractId, recovery, userId) {

        contract_id = contractId;
        user_id = userId;
        const $this = $(button);
        const $modal = $('#modalLawsuit');
        const $label = $('#modalLawsuitLabel');
        const $modalBody = $('#modalLawsuit .modal-body');
        let myId = await checkMyId()
        let notaryList, contractNotary;


        const title = $this.text();

        $label.text('');
        $modalBody.html('');
        $label.text(title);

        let modalBody;

        switch (recovery) {
            case 0:
                const formData = new FormData();
                formData.append('api_token', api_token);
                formData.append('user_id', user_id);
                formData.append('contract_id', contract_id);
                axios.post('/api/v1/recovery/buyer-comment', formData)
                    .then(response => {
                        if (response.data.status === 'success') {
                            $('textarea#comment_call').text(response.data.comment);
                        } else {
                            alert('Ошибка сервера');
                        }
                    })
                    .catch(e => {
                        alert(e);
                    });

                if ($('#contractRecovery a:nth-child(1)').hasClass('active')) {

                    appendedLinks = false;

                    if (title === 'Обзвон юристами') {
                        appendedLinks = `
                        <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" id="letter-home" target="_blank">Скачать письмо домой</a><br>
                        <br>
                    `;
                    //     appendedLinks = `
                    //     <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" id="letter-home" target="_blank">Скачать письмо домой</a><br>
                    //     <a href="//${server_name}/uz/panel/contracts/letter-to-residency-2/${contract_id}" target="_blank">Скачать письмо домой 2 ( 61 + дней )</a><br>
                    //     <br>
                    // `;
                        if (myId.status === 'success') {
                            appendedLinks = `
                        <a href="//${server_name}/uz/panel/contracts/letter-to-residency/${contract_id}" id="letter-home" target="_blank">Скачать письмо домой</a><br>
                        <a href="//${server_name}/uz/panel/contracts/myid/form-1/${myId?.my_id}/${contract_id}" target="_blank">Анкета MyId(Форма №1)</a>
                    `;
                        }
                    }

                    modalBody = all(recovery, appendedLinks);
                }
                break;

            case 2:
                modalBody = lettersToSent(recovery);

                if (myId.status === 'success') {
                    modalBody = lettersToSentMyId(myId.my_id);
                }
                break;

            case 4:
                loading(true);
                modalBody = '';
                let invoice_number_fix;
                let invoice_number_percent;

                try {

                    const response = await axios.get(`/api/v1/lawsuit/get-notaries-list?contract_id=${contract_id}`, {headers: {Authorization: `Bearer ${api_token}`}})
                    if (response.data.status === 'success') {
                        contractNotary = await response.data.data.contract_notary;
                        notaryUniqueNumber = await contractNotary?.letter_base_unique_number ?? '';
                        notaryList = response.data.data.notaries_list
                      
                        // if (!contractNotary) {
                        //     notaryList = response.data.data.notaries_list
                        // }

                    } else {
                        console.log('fail', response.data.status);
                        alert(response.data.data.message);
                    }

                    const {data} = await axios.post('/api/v1/lawsuit/check-can-save-invoice-number', {
                        api_token,
                        contract_id_invoice: contractId,
                    }, {
                        headers: {
                            'Content-Language': '{{app()->getLocale()}}',
                        },
                    })

                    if (data.status === 'success') {
                        can_inv_number = true;

                        if (myId.status === 'success') {
                            console.log('first myID', myId)
                            modalBody = transferToNotaryMyId({
                                with_invoice_number_inputs: true,
                                notaryId: contractNotary?.id,
                                executiveTemplateNumber: contractNotary.template_number,
                                myId: myId?.my_id
                            })
                        } else {
                            modalBody = transferToNotary({
                                with_invoice_number_inputs: true,
                                notaryId: contractNotary?.id,
                                executiveTemplateNumber: contractNotary.template_number,
                            })
                        }

                    } else if (data.status === 'error') {
                        can_inv_number = false;
                        const resp = data.data;
                        if (resp.invoice_number_fix && resp.invoice_number_percent) {
                            invoice_number_fix = resp.invoice_number_fix;
                            invoice_number_percent = resp.invoice_number_percent;

                            if (myId.status === 'success') {
                                modalBody = transferToNotaryMyId({
                                    invoice_number_fix,
                                    invoice_number_percent,
                                    notaryId: contractNotary?.id,
                                    executiveTemplateNumber: contractNotary.template_number,
                                    myId: myId?.my_id
                                })
                            } else {
                                modalBody = transferToNotary({
                                    invoice_number_fix,
                                    invoice_number_percent,
                                    notaryId: contractNotary?.id,
                                    executiveTemplateNumber: contractNotary.template_number,
                                });
                            }
                        }
                        data.response.message.forEach(
                            (item, index, arr) => {
                                createAlerter(item.text, item.type);
                            },
                        );
                    }

                } catch (e) {

                    createAlerter(e);

                } finally {
                    loading(false)
                }

                break;

            case 5:
                modalBody = transferToMIB(recovery);

                if (myId.status === 'success') {
                    modalBody = transferToMIBMyId(myId.my_id);
                }
                break;

            case 6:
                modalBody = transferToComplete(recovery);

                if (myId.status === 'success') {
                    modalBody = transferToCompleteMyId(myId.my_id);
                }
                break;

            default:
                break;
        }

        $modalBody.append(modalBody);
        $modal.modal('show');

        if (notaryList) {
            notaryList.forEach(notary => {
                const optionEl = `<option id="${notary.id}" data-template="${notary.template_number}"  value="${notary.id}">${notary.surname + ' ' + notary.name + ' ' + notary.patronymic}</option>`;
                $('#select').append(optionEl)
            });
           
            if (contractNotary) {
                $('#select').val(contractNotary.id);
                $('#select').trigger("change")
            }else {
                $('#courtContractAnchor').css('pointer-events', 'none');
                $('#courtContractAnchor').addClass('text-muted');

                $('#anchor1').css('pointer-events', 'none');
                $('#anchor1').addClass('text-muted');
            }
            $('#select').css('display', 'block');
           
        }

        document.querySelectorAll('[data-dismiss=\'modal\']').forEach(
            (item, index, arr) => {
                item.addEventListener('click', (event) => {
                    let alerter = document.getElementById('alerter');
                    if (alerter && alerter.children) {
                        alerter.innerHTML = '';
                    }
                });
            },
        );


        if (can_inv_number) {

            if (!document.querySelector('button[type=\'submit\']#save_button')) {
                let saveButton = document.createElement('button');
                saveButton.innerHTML = "{{__('app.btn_save')}}";
                saveButton.setAttribute('id', 'save_button');
                saveButton.setAttribute('type', 'submit');
                saveButton.setAttribute('class', 'btn btn-success');
                saveButton.setAttribute('disabled', 'disabled');
                saveButton.setAttribute('onclick', 'saveInvoiceNumber()');
                document.querySelector('div#messagebox').after(saveButton);
            }

            let saveButton = document.querySelector('button[type=\'submit\']#save_button');
            const fix_inv_number = document.querySelector('input[type=\'text\']#invoice_number_fix');
            const fix_error_msg = document.querySelector('div#inv_error_msg_1');
            const percent_inv_number = document.querySelector('input[type=\'text\']#invoice_number_percent');
            const percent_error_msg = document.querySelector('div#inv_error_msg_2');

            fix_inv_number.addEventListener('input', (event) => {
                if (event.target.value.length === 0) {
                    fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_empty')}}";
                    fix_error_msg.style.color = 'red';
                }
                if (event.target.value.length < 14 || event.target.value.length > 20) {
                    fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_must_be_digits_between')}}";
                    fix_error_msg.style.color = 'red';
                    saveButton.setAttribute('disabled', 'disabled');
                } else if (event.target.value.length >= 14 || event.target.value.length <= 20) {
                    fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_valid')}}";
                    fix_error_msg.style.color = 'green';
                    if (percent_inv_number.value && percent_inv_number.value.length >= 14 && percent_inv_number.value.length <= 20) {
                        saveButton.removeAttribute('disabled');
                    }
                }
            });

            percent_inv_number.addEventListener('input', (event) => {
                if (event.target.value.length === 0) {
                    percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_empty')}}";
                    percent_error_msg.style.color = 'red';
                }
                if (event.target.value.length < 14 || event.target.value.length > 20) {
                    percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_must_be_digits_between')}}";
                    percent_error_msg.style.color = 'red';
                    saveButton.setAttribute('disabled', 'disabled');
                } else if (event.target.value.length >= 14 || event.target.value.length <= 20) {
                    percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_valid')}}";
                    percent_error_msg.style.color = 'green';
                    if (fix_inv_number.value && fix_inv_number.value.length >= 14 && fix_inv_number.value.length <= 20) {
                        saveButton.removeAttribute('disabled');
                    }
                }
            });

            const arrows = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
            const letters = ['KeyA', 'KeyC', 'KeyV', 'KeyX', 'KeyZ', 'KeyY', 'KeyR', 'KeyT'];
            const special_keys = [
                'ControlLeft', 'ControlRight', 'ShiftLeft', 'ShiftRight',
                'Home', 'End',
                'Backspace', 'Delete',
            ];
            const regex = new RegExp('[0-9]');

            fix_inv_number.addEventListener('keypress', (event) => {
                if (                                        // Если не стрелки и комбинация клавиш
                    !arrows.includes(event.code) &&
                    !special_keys.includes(event.code) &&
                    !(event.ctrlKey && (letters.includes(event.code))) &&
                    !(event.ctrlKey && event.shiftKey && (event.code === 'KeyT')) // Ctrl+Shift+T
                ) {
                    if (
                        !regex.test(event.key)                  // Если не цифры
                    ) {
                        if ((fix_inv_number.value.length >= 20)) {
                            event.preventDefault();
                            fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_max_number_invalid')}}";
                            fix_error_msg.style.color = 'red';
                        } else {
                            event.preventDefault();
                            fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_invalid')}}";
                            fix_error_msg.style.color = 'red';
                        }
                    } else if (fix_inv_number.value.length >= 20) {
                        if (
                            (fix_inv_number.selectionStart !== 20) ||
                            (fix_inv_number.selectionEnd !== 20)
                        ) {
                            fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_valid')}}";
                            fix_error_msg.style.color = 'green';
                        } else {
                            event.preventDefault();
                            fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_max_number')}}";
                            fix_error_msg.style.color = 'red';
                        }
                    } else {
                        fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_valid')}}";
                        fix_error_msg.style.color = 'green';
                    }
                } else if (fix_inv_number.value.length < 14) {
                    fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_must_be_digits_between')}}";
                    fix_error_msg.style.color = 'red';
                } else {
                    fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_valid')}}";
                    fix_error_msg.style.color = 'green';
                }
            });

            percent_inv_number.addEventListener('keypress', (event) => {
                if (
                    !arrows.includes(event.code) &&
                    !special_keys.includes(event.code) &&
                    !(event.ctrlKey && (letters.includes(event.code))) &&
                    !(event.ctrlKey && event.shiftKey && (event.code === 'KeyT')) // Ctrl+Shift+T
                ) {
                    if (
                        !regex.test(event.key)                  // Если не цифры
                    ) {
                        if ((percent_inv_number.value.length >= 20)) {
                            event.preventDefault();
                            percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_max_number_invalid')}}";
                            percent_error_msg.style.color = 'red';
                        } else {
                            event.preventDefault();
                            percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_invalid')}}";
                            percent_error_msg.style.color = 'red';
                        }
                    } else if (percent_inv_number.value.length >= 20) {
                        if (
                            (percent_inv_number.selectionStart !== 20) ||
                            (percent_inv_number.selectionEnd !== 20)
                        ) {
                            percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_valid')}}";
                            percent_error_msg.style.color = 'green';
                        } else {
                            event.preventDefault();
                            percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_max_number')}}";
                            percent_error_msg.style.color = 'red';
                        }
                    } else {
                        percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_valid')}}";
                        percent_error_msg.style.color = 'green';
                    }
                } else if (percent_inv_number.value.length < 14) {
                    percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_must_be_digits_between')}}";
                    percent_error_msg.style.color = 'red';
                } else {
                    percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_valid')}}";
                    percent_error_msg.style.color = 'green';
                }
            });

            fix_inv_number.addEventListener('paste', (event) => {
                let clipboard_text = event.clipboardData.getData('text');
                event.preventDefault();
                if (clipboard_text) {
                    clipboard_text = clipboard_text.replace(/\s+|\D/g, '');
                    let there_is_number = clipboard_text.match(/\d+/g);

                    if (there_is_number && clipboard_text.length >= 14) {
                        let value = fix_inv_number.value.replace(/\s+|\D/g, '');
                        value += clipboard_text;
                        fix_inv_number.value = value.substring(0, 20);
                        fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_clipboard_valid')}}";
                        fix_error_msg.style.color = 'green';
                        if (percent_inv_number.value && percent_inv_number.value.length >= 14 && percent_inv_number.value.length <= 20) {
                            saveButton.removeAttribute('disabled');
                        }
                    } else if (there_is_number) {
                        let value = fix_inv_number.value.replace(/\s+|\D/g, '');
                        value += clipboard_text;
                        fix_inv_number.value = value.substring(0, 20);
                        fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_must_be_digits_between')}}";
                        fix_error_msg.style.color = 'red';
                    } else {
                        fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_clipboard_doesnt_contain_number')}}";
                        fix_error_msg.style.color = 'red';
                    }
                } else {
                    fix_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_clipboard_empty')}}";
                    fix_error_msg.style.color = 'red';
                }
            });

            percent_inv_number.addEventListener('paste', (event) => {
                let clipboard_text = event.clipboardData.getData('text');

                event.preventDefault();
                if (clipboard_text) {
                    clipboard_text = clipboard_text.replace(/\s+|\D/g, '');
                    let there_is_number = clipboard_text.match(/\d+/g);

                    if (there_is_number && clipboard_text.length >= 14) {
                        let value = percent_inv_number.value.replace(/\s+|\D/g, '');
                        value += clipboard_text;
                        percent_inv_number.value = value.substring(0, 20);
                        percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_clipboard_valid')}}";
                        percent_error_msg.style.color = 'green';
                        if (fix_inv_number.value && fix_inv_number.value.length >= 14 && fix_inv_number.value.length <= 20) {
                            saveButton.removeAttribute('disabled');
                        }
                    } else if (there_is_number) {
                        let value = percent_inv_number.value.replace(/\s+|\D/g, '');
                        value += clipboard_text;
                        percent_inv_number.value = value.substring(0, 20);
                        percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_must_be_digits_between')}}";
                        percent_error_msg.style.color = 'red';
                    } else {
                        percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_clipboard_doesnt_contain_number')}}";
                        percent_error_msg.style.color = 'red';
                    }
                } else {
                    percent_error_msg.innerHTML = "{{__('panel/contract_invoice.invoice_number_clipboard_empty')}}";
                    percent_error_msg.style.color = 'red';
                }
            });
        } else if (document.querySelector('button[type=\'submit\']#save_button')) {
            document.querySelector('button[type=\'submit\']#save_button').remove();
        }

    }

    function setValue(tag) {
        data[$(tag).attr('id')] = $(tag).val();
    }

    function setNewValue() {
        const selectEl = document.getElementById('select')
        const selectedOption = selectEl?.options[selectEl.selectedIndex];
        const linkToExecutiveEl = document.getElementById('anchor1');
        const linkToCourtContractAnchor = document.getElementById('courtContractAnchor');
        if (selectedOption?.value) {
            linkToExecutiveEl.style.pointerEvents = 'unset';
            $(linkToExecutiveEl).removeClass('text-muted')
            linkToExecutiveEl.setAttribute('href', `//${server_name}/uz/panel/contracts/executive-letter-${selectedOption.dataset.template}/${contract_id}/${selectedOption.value}`);
            
            if (selectedOption.dataset.template != 'fourth') {
                linkToCourtContractAnchor.style.pointerEvents = 'unset';
                $(linkToCourtContractAnchor).removeClass('text-muted')
                linkToCourtContractAnchor.setAttribute('href', `//${server_name}/${window.Laravel.locale}/panel/contracts/court-contract-print-form/${contract_id}`);
            }
        }
    }

    function setImage(tag) {
        images[$(tag).attr('id')] = tag.files[0];
    }

    function save() {
        if (recovery == 2 && !letter_send) {
            alert('Необходимо отредактировать Шаблон письмо домой');
            return false;
        }


        const formData = new FormData();
        formData.append('api_token', api_token);
        formData.append('contract_id', contract_id);
        fields_count = 0;
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
            fields_count++;
        });
        img_count = 0;
        Object.entries(images).forEach(([key, value]) => {
            formData.append(key, value);
            img_count++;
        });

        if (recovery === 4 && img_count !== 2) {
            alert('Загрузите файлы!');
            return false;
        }

        if (recovery === 5 && fields_count !== 4) {
            alert('Заполните все поля!');
            return false;
        }


        axios.post('/api/v1/recovery/recovery-step', formData, {
            headers: {'Content-Type': 'multipart/form-data'},
        })
            .then(response => {
                if (response.data.status === 'success') {
                    //$(`#contractRecovery a[data-status=${recovery}]`).click();
                    action = parseInt($(`#contractRecovery a.active`).data('action'));
                    url = window.location.href.split('?');
                    window.location.href = url[0]; // + '?recovery-tab='+(recovery+action);
                } else if (response.data.status === 'not_allowed') {
                    alert('Невозможно закрыть договор! У клиента имеются непогашенные задолженности.');
                } else {

                    //this.errors.phone = '{{__('cabinet/err')}}';
                    alert('Ошибка сервера');
                }
            })
            .catch(e => {
                alert(e);
            });
        data = {};
        images = [];

    }

    $('#modalLawsuitSave').click(save);

    //Show hide loader
    function loading(show = false) {
        if (show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

    function checkMyId() {
        let checkMyStatus = new FormData()
        checkMyStatus.append('api_token', api_token);
        checkMyStatus.append('user_id', user_id);

        return axios.post('/api/v1/recovery/myid-status', checkMyStatus)
            .then(response => response.data)
    }

</script>
