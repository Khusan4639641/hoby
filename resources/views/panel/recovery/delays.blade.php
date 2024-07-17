@extends('templates.panel.app')

@section('title', __('panel/contract.header_contracts_recovery'))
@section('class', 'contracts list')


@section('center-header-control')
    <button class="btn btn-primary " onclick="openExportModal(this)">
        Экспорт в Excel
    </button>
@endsection
@section('content')
<style>
    input::-webkit-datetime-edit-day-field:focus,
    input::-webkit-datetime-edit-month-field:focus,
    input::-webkit-datetime-edit-year-field:focus {
        background-color: var(--orange);
        color: white;
        outline: none;
    }
    .input-with-prepend{
        display: flex;
        align-items: center;
    }
    .input-with-prepend input.form-control.modified{
        padding-left: 40px;
    }
    .input-with-prepend::before{
        content: attr(data-prefix);
        position: absolute;
        padding: 0 12px;
        display: inline-flex;
        align-items: center;
        transition: 0.4s;
        color: #b1b1b1;
    }
    .input-with-prepend:focus-within:before{
        color: var(--orange)
    }
    .modal {
        top: 0;
    }
</style>
<style>
    .nav-link:not(.active) a {
        color: #787878;
    }
    a {
        color: var(--orange);
        outline: none;
    }
    a:hover, a:focus, a:visited {
        color: #4807b0;
        outline: none;
    }
    .first.paginate_button, .last.paginate_button {
        display: none !important;
    }
    .previous.paginate_button, .next.paginate_button {
        height: 40px;
        background: #F6F6F6;
        border-radius: 8px;
        border: 1px solid transparent;
        transition: 0.4s;
        font-size: 16px;
        display: inline-flex !important;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        border-radius: 8px !important;
    }
    .previous.paginate_button{
        background-position: left 4px center !important;
        padding: 0.15rem 1rem 0.15rem 2rem !important;
        margin-left: 0 !important;
    }
    .next.paginate_button {
        background-position: right 4px center !important;
        padding: 0.15rem 2rem 0.15rem 1rem !important;
    }
    .previous.paginate_button:hover, .next.paginate_button:hover {
        border-color: transparent !important;
        background-color: var(--peach) !important;
    }
    .previous.paginate_button:active, .next.paginate_button:active {
        border-color: transparent !important;
        background-color: #6610f530 !important;
        box-shadow: none !important;
    }

    .paginate_button.disabled{
        filter: grayscale(1);
        opacity: .5;
        cursor: not-allowed !important;
    }
    input.paginate_input {
        max-width: 100px;
        padding: 8px 12px;
        margin: 0 8px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        font-size: 16px;
        line-height: 24px;
        letter-spacing: 0.01em;
        color: #1e1e1e;
        box-sizing: border-box;
        background: #F6F6F6;
        border-radius: 8px;
        border: 1px solid transparent;
        transition: 0.4s;
    }
    input.paginate_input:hover {
        border: 1px solid #d1d1d1;
    }
    input.paginate_input:focus {
        border: 1px solid var(--orange);
        outline: none;
        color: #1e1e1e;
        box-shadow: none;
    }
</style>
<div id="list-delays">

    <ul class="nav nav-tabs" id="contractRecovery" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="for-send" data-status="0" data-action="0" data-toggle="tab" href="#" role="tab"
                aria-selected="true">{{__('panel/contract.tab_recover_30')}} ({{$counter["all_30"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="for-send" data-status="0" data-action="1" data-toggle="tab" href="#" role="tab"
                aria-selected="true">{{__('panel/contract.tab_recover_call')}} ({{$counter["call"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="call_wait" data-status="1" data-toggle="tab" href="#" role="tab"
                aria-selected="true">{{__('panel/contract.tab_recover_1')}} ({{$counter["call_wait"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="letter" data-status="2" data-toggle="tab" href="#" role="tab"
                aria-selected="false">{{__('panel/contract.tab_recover_2')}} ({{$counter["letter"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="letter_wait" data-status="3" data-toggle="tab" href="#" role="tab"
                aria-selected="false">{{__('panel/contract.tab_recover_3')}} ({{$counter["letter_wait"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="notarius" data-status="4" data-toggle="tab" href="#" role="tab"
                aria-selected="false">{{__('panel/contract.tab_recover_4')}} ({{$counter["notarius"]}})</a>

        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="mib" data-status="5" data-toggle="tab" href="#" role="tab"
                aria-selected="false">{{__('panel/contract.tab_recover_5')}} ({{$counter["mib"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="control" data-status="6" data-toggle="tab" href="#" role="tab"
                aria-selected="false">{{__('panel/contract.tab_recover_6')}} ({{$counter["control"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="complete" data-status="[1,2,3,4,5,6,7]" data-toggle="tab" href="#" role="tab"
                aria-selected="false">{{__('panel/contract.tab_recover_7')}} ({{$counter["complete"]}})</a>
        </li>
    </ul>

    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="row">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="ID" id="contract_id">
                    <div class="input-group-append">
                        <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" placeholder="{{ __('validation.attributes.passport_number') }}"
                        class="form-control" id="passport_number">
                    <div class="input-group-append">
                        <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" placeholder="{{ __('cabinet/profile.fio') }}" class="form-control"
                        id="buyer_fio">
                    <div class="input-group-append">
                        <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <table class="table contract-list">
        <thead>
            <tr>
                <th>{{__('panel/contract.date')}}</th>
                <th>{{__('panel/contract.contract_id')}}</th>
                <th></th>
                <th>{{__('panel/contract.client')}}</th>
                <th>{{__('cabinet/profile.gender_title')}}</th>
                <th>{{__('cabinet/profile.birthday')}}</th>
                <th>{{__('panel/contract.phone')}}</th>
                <th>{{__('panel/contract.sum')}}</th>
                <th>{{__('panel/contract.paid_off')}}</th>
                <th>{{__('panel/contract.debt')}}</th>
                <th>{{__('panel/contract.day')}}</th>
                <th>{{__('panel/contract.status')}}</th>
                <th>{{__('panel/contract.header_contracts_recovery_status')}}</th>
                <th>{{__('panel/contract.trade_company')}}</th>
                <th></th>
                <th>{{__('panel/lawsuit.btn_сollection_costs')}}</th>
                <th>Акт</th>
                <th>IMEI</th>
                <th>Фото</th>

            </tr>
        </thead>
        <tbody>
        </tbody>
        {!! '<b>Всего сумма просрочки:</b> <span id="all_debts"> '. $debts .'</span>'!!}

    </table><!-- /.contract-list -->

    <div class="modal fade" id="modalLawsuit" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLawsuitLabel">{{__('panel/lawsuit.btn_create')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    modal body
                </div>

                <div class="modal-footer">
                    <div id="messagebox">
                        <div id="alerter"></div>
                    </div>
                    <button id="save_button" onclick="saveInvoiceNumber()" type="submit"
                        class="btn btn-success">{{__('app.btn_save')}}</button>
                    <button type="button" class="btn btn-secondary"
                        data-dismiss="modal">{{__('app.btn_close')}}</button>
                    <button id="modalLawsuitSave" type="button" data-dismiss="modal"
                        class="btn btn-primary">{{__('app.btn_next')}}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="collection_costs mt-3" id="collection_costs">

        {{-- <div v-if="message.length > 0" v-for="item in message" :class="'alert alert-' + item.type">@{{ item.text }}
        </div> --}}

        <div class="modal" id="modalCosts" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <input type="hidden" id="contractID">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCosts">{{__('panel/lawsuit.btn_create_сollection_costs')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{__('panel/lawsuit.debt')}}</label>
                            <input type="text" id="debt" name="debt" class="form-control" value="" readonly>

                        </div>
                        <div class="form-group">
                            <label>{{__('panel/lawsuit.persent')}}</label>
                            <input type="text" id="persent" name="persent" class="form-control" value="" readonly>

                        </div>
                        <div class="form-group">
                            <label for="fix">{{__('panel/lawsuit.fix')}}</label>
                            {{-- <input type="select" id="fix" name="fix" value="" readonly>--}}
                            <select id="fix" name="fix" onchange="setNewValue(this)" class="form-control" required>
                                <option disabled selected value>{{__('panel/lawsuit.select')}}</option>
                                {{-- <option value="27000" selected>27 000</option>--}}
                                {{-- <option value="30000">30 000</option>--}}
                                {{-- <option value="33000">33 000</option>--}}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{__('panel/lawsuit.amount')}}</label>
                            <input type="text" id="amount" name="amount" class="form-control" value="" readonly>
                        </div>

                        <div class="form-group">
                            <label>{{__('panel/lawsuit.total_amount')}}</label>
                            <input type="text" id="total_amount" name="total_amount" class="form-control" value=""
                                readonly>

                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                        <button disabled id="submitButton" onclick="createCost()" type="submit"
                            class="btn btn-primary">{{__('app.btn_create')}}</button>
                    </div>

                </div>
            </div>
        </div>


        <!-- /.form-controls -->


        </div>
        <div class="modal fade" id="modalExports" tabindex="-1" aria-labelledby="modalExportsLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header px-4">
                        <h5 class="modal-title" id="modalExportsLabel">Экспорт отчета</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="excelForm" action="{{ localeRoute('panel.reports.debtCollectorsFilteredExport') }}" method="post">
                        @csrf
                        <div class="modal-body p-4">
                                <div class="form-group">
                                    <label>Статус договора <span class="text-red">*</span></label>
                                    <select name="recovery" required class="form-control modified">
                                        <option value="2">Письма к отправке</option>
                                        <option value="3">Ожидание по письму</option>
                                        <option value="4">Передача нотариусу</option>
                                        <option value="5">Передача миб</option>
                                        <option value="6">Контроль</option>
                                        <option value="7">Закрытые</option>
                                        <option value="[2,3,4,5,6,7]">ВСЕ</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Дата договора</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label  class="input-with-prepend"  data-prefix="от">
                                                <input name="contract_date_from" id="contract_date_from" type="date" oninput="setDateMin()" min="{{date('Y-m-d', mktime(0, 0, 0, 1, 1, 2020))}}" max="{{date('Y-m-d')}}" class="form-control modified">
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label  class="input-with-prepend" data-prefix="до">
                                                <input name="contract_date_to" id="contract_date_to" type="date" oninput="setDateMax()" min="{{date('Y-m-d', mktime(0, 0, 0, 1, 2, 2020))}}" max="{{date('Y-m-d')}}" class="form-control modified">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Срок просрочки <span class="text-muted">(дней)</span></label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="input-with-prepend" data-prefix="от">
                                                <input name="delay_days_from"  id="delayed_day_from" type="tel" min="0" max="1000" class="form-control delay-mask modified">
                                                <span class="text-red " style="display: none;">мин. 0 макс. 1000</span>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="input-with-prepend" data-prefix="до">
                                                <input name="delay_days_to" id="delayed_day_to" type="tel"  min="0" max="1000"  class="form-control delay-mask modified">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Регион</label>
                                    <select id="recovery_regions" name="katm_region" required class="form-control modified">
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Сумма рассрочки</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="input-with-prepend" data-prefix="от">
                                                <input id="contract_balance_from" name="contract_balance_from" type="tel"  class="currency-mask form-control modified">
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="input-with-prepend" data-prefix="до">
                                                <input id="contract_balance_to" name="contract_balance_to" type="tel"  class="currency-mask form-control modified">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                        </div>

                        <div class="modal-footer px-4">

                            <button  type="submit"
                                    class="btn btn-primary">{{__('app.btn_download')}}</button>
                            <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{__('app.btn_close')}}</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        <div class="loading"><img src="{{ asset('images/media/loader.svg') }}"></div>
    </div>

<script>
    var changedVar = {
        idSelect: null,
    };

    //Confirm
    function confirmCost(contract_id) {
        getAmount(contract_id);
        $('#modalCosts').modal('show');
        // $('#fix').val(null);
        $('#fix')
            .find('option')
            .remove()
            .end()
            .append(`<option disabled selected value>{{__('panel/lawsuit.select')}}</option>`)
            ;
    }

    // get amount
    function getAmount(contract_id) {
        $('#contractID').val(contract_id);
        axios.post('/api/v1/lawsuit/get-collection-amount' + '?api_token=' + Cookies.get('api_token'), {
            contract_id: contract_id,
        }).then(response => {
            if (response.data.status === 'success') {
                const notaries = response.data.data.notaries;
                const debt = response.data.data.debt;
                const valDebt = response.data.data.persent;
                // assign value to select options
                notaries.forEach(fix => {
                    const option = `<option id="${fix.id}" tax="${fix.tax}"  value="${fix.fee}">${fix.surname + ' ' + fix.name + ' ' + fix.patronymic + ' ' + fix.fee}</option>`;
                    $('#fix').append(option);
                });
                // set percent to 0 default value
                $('#persent').val(0);
                $('#debt').val(debt);
                $('#amount').val(response.data.data.amount);
                $('#total_amount').val(response.data.data.total_amount);

                // select option change
                $(document).ready(function () {
                    $('#fix').change(function () /**/ {
                    let selectValue = $(this).val();
                    let sumOfAll = Number(selectValue) + Number(debt);
                    let sumOfvalueDebt = Number(selectValue);
                    let optionTaxField = $("#fix option:selected").attr('tax');

                        // calculate the total amount based on tax exist or not, 1 or 0
                        if(optionTaxField !== '0') {
                            $('#persent').val(response.data.data.persent);
                            sumOfAll = Number(selectValue) + Number(valDebt) + Number(debt);
                            sumOfvalueDebt = Number(selectValue) + Number(valDebt);
                        }else {
                            $('#persent').val(0);
                        }

                        if (selectValue) {
                            $('#submitButton').prop('disabled', false);
                        }

                        changedVar.idSelect = $(this).find('option:selected').attr('id');
                        $('#amount').val(sumOfvalueDebt);
                        $('#total_amount').val(sumOfAll);
                    });
                });
            } else {
                alert(response.data.response.message[0].text);
            }

            });
        }

        // create
        function createCost() {

            let contract_id = $('#modalCosts #contractID').val();
            axios.post('/api/v1/lawsuit/add-collection-cost' + '?api_token=' + Cookies.get('api_token'), {
                contract_id: contract_id,
                amount: $('#modalCosts #amount').val(),
                total_amount: $('#modalCosts #total_amount').val(),
                persent: $('#modalCosts #persent').val(),
                fix: $('#modalCosts #fix').val(),
                notary_id: changedVar.idSelect,

            }).then(response => {
                console.log('response.data', response.data);
                if (response.data.status === 'success') {

                } else {

                }
                alert(response.data.response.message[0].text);
                $('#modalCosts').modal('hide');
                $('#collect_cost' + contract_id).prop('disabled', true);
                //window.location.reload();
                $('#contractRecovery a#notarius').click();
            });
        }

        const createAlerter = (text, type = 'error') => {
            let alerter = document.getElementById('alerter');
            // console.dir(alerter.children);

            for (let i = 0; i < alerter.children.length; i++) {
                if (alerter.children[i].getAttribute('style') === 'opacity: 0;') {
                    alerter.children[i].remove();
                }
            }

            let message = document.createElement('div');
            message.addEventListener('transitionend', () => message.remove());
            message.setAttribute('id', type); // "success" | "error"
            message.innerHTML = text;

            alerter.appendChild(message);
            setTimeout(() => {
                message.style.opacity = '0';
            }, 3000);
        };

        function debounce_leading(func, timeout = 400) {
            let timer;
            return (...args) => {
                if (!timer) {
                    func.apply(this, args);
                }
                clearTimeout(timer);
                timer = setTimeout(() => {
                    timer = undefined;
                }, timeout);
            };
        }

        const saveInvoiceNumber = debounce_leading(modalLawsuitInvoiceNumberSave);
        let save_button = document.querySelector('button[type=\'submit\']#save_button');
        save_button.setAttribute('disabled', 'disabled');

        function modalLawsuitInvoiceNumberSave() {
            let save_button = document.querySelector('button[type=\'submit\']#save_button');
            const fix_inv_number = document.querySelector('input[type=\'text\']#invoice_number_fix');
            const percent_inv_number = document.querySelector('input[type=\'text\']#invoice_number_percent');
            const user_id = fix_inv_number.dataset.user_id;
            const contract_id = fix_inv_number.dataset.contract_id;
            const registrationNumberValue = document.getElementById('registration_number').value

        if (!registrationNumberValue) {
            alert('Необходимо заполнить поле номер испольнителной надписи');
            return false;
        }

        if (!save_button.getAttribute('disabled')) {
            axios.post('/api/v1/lawsuit/save-invoice-number' + '?api_token=' + Cookies.get('api_token'), {
                user_id_invoice: user_id,
                contract_id_invoice: contract_id,
                fix_inv_number: fix_inv_number.value,
                percent_inv_number: percent_inv_number.value,
            }, {
                headers: {
                    'Content-Language': '{{app()->getLocale()}}',
                },
            })
                .then(response => {
                    if (response.data.status === 'success') {
                        axios.post('/api/v1/lawsuit/store-executive-writing', {
                            api_token,
                            contract_id,
                            registration_number: registrationNumberValue
                        })
                        response.data.response.message.forEach(
                            (item, index, arr) => {
                                polipop.add({ content: item.text, title: item.type, type: 'success' });
                            },
                        );
                    } else {
                        response.data.response.message.forEach(
                            (item, index, arr) => {
                                polipop.add({ content: item.text, title: item.type, type: 'error' });
                            },
                        );
                    }
                    loading(false)
                })
                .catch(e => {
                    polipop.add({ content: e, title: 'Ошибка', type: 'error' });
                    loading(false);
                });
        }
    }
</script>

@include('panel.recovery.parts.list-delays')

@endsection
