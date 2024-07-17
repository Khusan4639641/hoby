@extends('templates.panel.app')

@section('title', __('panel/menu.report_soliq_title'))


@section('content')
<style>
    .btn {
        border: none !important;
    }
    .btn.processing {
        cursor: disabled;
        position: relative;
        padding-right: 2rem;
        pointer-events: none;
    }
    .btn .spinner {
        width: 1rem;
        height: 1rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        display: none;
        position: absolute;
        top: calc(50% - 0.5rem);
    }
    .btn.processing .spinner {
        display: inline-block;
    }
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
    table.table td, table.table th {
        vertical-align: middle;
    }
    .bg-danger {
        background-color: #F84343 !important;
    }
    .border-danger {
        border-color: #F84343 !important;
    }
    .bg-success {
        background-color: #53DB8C !important;
    }
    .border-success {
        border-color: #53DB8C !important;
    }
    .status-toggle{
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        cursor: pointer;
    }
    .status-toggle svg path, .status-toggle svg circle{
        stroke: #b3b3b3;
        
    }
    .status-toggle.error {
        background-color: #F84343 !important;
        border-color: #F84343 !important;
        color: #ffffff;
    }
    .status-toggle.active {
        background-color: #53DB8C !important;
        border-color: #53DB8C !important;
        color: #ffffff;
    }
    .status-toggle.disabled {
        pointer-events: none !important;
    }
    .status-toggle.active svg path, .status-toggle.active svg circle, .status-toggle.error svg path, .status-toggle.error svg circle{
        stroke: #ffffff !important;
    }
</style>
    <div id="report_mko" class="p-4">
        <form @submit.prevent="fetchReport()" action="">
            <div class="row">
                <div class="col-sm-8 col-md-10 col-xl-5">
                    <div class="form-group row align-items-center">
                        <div class="col-md-2">
                            <label >Период:</label>
                        </div>
                        <div class="col-md-10">
                            <div class="row">
                                <div class="col-md-6">
                                    <label  class="input-with-prepend"  data-prefix="с">
                                        <input v-model="date_from" name="mko_date_from" id="mko_date_from" type="date" oninput="setDateMin()" value="{{date('Y-m-d', mktime(0, 0, 0, date("m")-1, 1))}}" min="{{date('Y-m-d', mktime(0, 0, 0, 1, 1, 2020))}}" max="{{date('Y-m-d', mktime(0, 0, 0, date("m"), 0))}}" class="form-control modified">
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label  class="input-with-prepend" data-prefix="по">
                                        <input v-model="date_to" name="mko_date_to" id="mko_date_to" type="date" oninput="setDateMax()" value="{{date('Y-m-d', mktime(0, 0, 0, date("m"), 0))}}" min="{{date('Y-m-d', mktime(0, 0, 0, date("m")-1, 1))}}" max="{{date('Y-m-d')}}" class="form-control modified">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row align-items-center">
                        <div class="col-md-2">
                            <label>Номер рейса: </label>
                        </div>
                        
                        <div class="col-md-10">
                            <label class="input-with-prepend" data-prefix="рейс" >
                                <input ref="dispatchNumberRef" @focus="$refs.dispatchNumberRef.select()" name="dispatch_number" v-mask="'#X'" placeholder="XX" v-model="dispatch_number" @keyup="uppercase($event)" id="dispatch_number" style="padding-left: 60px" class="form-control modified">
                            </label>
                        </div>
                    </div>
                    <div class="form-group  row align-items-center">
                        <div class="col-md-2">
                            <label>МКО:</label>
                        </div>
                        <div class="col-md-10">
                            <select id="mko_id" name="mko_id" v-model="mko_id" required class="form-control modified">
                                <option disabled value="">Выберите организацию</option>
                                @foreach ($companies as $company)
                                    <option {{$company['id'] == 3 ? 'selected':''}} value="{{$company['id']}}">{{$company['name_ru']}}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>

                <div class="alert alert-danger alert-dismissible fade show" v-for="error in errors">
                    @{{ error }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="col-md-12">
                    <button type="submit" :disabled="loading" :class="{'processing pr-5':loading}" class="btn btn-orange " style="margin-top:20px;">Сформировать отчет
                        <div class="spinner-border spinner ml-3" role="status">
                            <span class="sr-only">Загрузка...</span>
                        </div>
                    </button>
                </div>

            </div>
        </form>

            <div v-if="rowdata.length" class="table-responsive mt-5">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>C</th>
                            <th>По</th>
                            <th>Номер рейса</th>
                            <th>Организация</th>
                            <th>Статус отчета</th>
                            <th>Статус отправки</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(data, i) in rowdata" :key="i">
                            <td>@{{data.from}}</td>
                            <td>@{{data.to}}</td>
                            <td>@{{data.dispatch_number}}</td>
                            <td>@{{getCompanyName(data.mko_id)}}</td>
                            <td width="190px">
                                <label @click.stop="toggleIsSent(!data.is_sent, i)" class="status-toggle px-2 py-1 rounded border" :class="{'active' : data.is_sent, 'disabled': data.sent_loading}" :title="data.is_sent ? 'Нажмите чтобы пометить как «Не отправлено»' : 'Нажмите чтобы пометить как «Отправлено»' ">
                                    <input type="checkbox" class="d-none" :checked="data.is_sent">
                                    <svg v-if="data.sent_loading" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin:auto;display:block;" width="24px" height="24px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                                        <circle cx="50" cy="50" fill="none" stroke="#ffffff" stroke-width="6" r="35" stroke-dasharray="164.93361431346415 56.97787143782138">
                                          <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
                                        </circle>
                                    </svg>
                                    <svg  v-if="!data.sent_loading && data.is_sent" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M5.35303 15.9993V18.3803C5.35303 19.1233 6.13503 19.6073 6.80003 19.2743L21.353 11.9993L6.80003 4.72428C6.13503 4.39128 5.35303 4.87528 5.35303 5.61828V11.9993H12.02" stroke="white" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <svg class="mr-2" v-if="!data.sent_loading && !data.is_sent" style="transform: rotate(-45deg);width: 16px;fill: #b3b3b3;" viewBox="64 64 896 896" focusable="false" xmlns="http://www.w3.org/2000/svg"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm234.8 736.5L223.5 277.2c16-19.7 34-37.7 53.7-53.7l523.3 523.3c-16 19.6-34 37.7-53.7 53.7z" /></svg>
                                    <span v-if="data.is_sent">Отправлено</span>
                                    <span v-else>Не отправлено</span>
                                </label>
                                
                            </td>
                            <td width="190px">
                                <label v-if="data.is_sent == 1" @click.stop="toggleIsError(!data.is_error, i)" class="status-toggle px-2 py-1 rounded border" style="height:34px;" :class="{'error' : data.is_error,'active' : !data.is_error, 'disabled': data.error_loading}" :title="data.is_error ? 'Нажмите чтобы пометить как «Ошибка»' : 'Нажмите чтобы пометить как «Успешно»' ">
                                    <input type="checkbox" class="d-none" :checked="data.is_error">
                                    <svg v-if="data.error_loading" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin:auto;display:block;" width="24px" height="24px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                                        <circle cx="50" cy="50" fill="none" stroke="#ffffff" stroke-width="6" r="35" stroke-dasharray="164.93361431346415 56.97787143782138">
                                          <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
                                        </circle>
                                    </svg>
                                    <span v-if="data.is_error">Ошибка</span>
                                    <span v-else>Успешно</span>
                                </label>
                                <label v-else>-</label>
                            </td>
                            <td width="158px" class="p-2">
                                <a :href="data.url" class="btn py-2 px-3 btn-orange" target="_blank">Скачать отчет</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
    </div>


    <script>
        // const rowdata = [
        //     {id: 1, from: '2023-02-01', to: '2023-02-28', companyName: 'OOO ARHAT', sent_loading: false, dispatch_number: '09', is_error: 0, is_sent: 1},
        //     {id: 2, from: '2023-02-01', to: '2023-02-28', companyName: 'OOO MFO', sent_loading: false, dispatch_number: '09', is_error: 1, is_sent: 0},
        //     {id: 3, from: '2023-02-01', to: '2023-02-28', companyName: 'OOO MKO', sent_loading: false, dispatch_number: '09', is_error: 0, is_sent: 1},
        //     {id: 4, from: '2023-02-01', to: '2023-02-28', companyName: 'OOO test', sent_loading: false, dispatch_number: '09', is_error: 0, is_sent: 1},
        // ]
        function setDateMax() {
            //setting max value for mko_date_from
            let dateToValue = $('#mko_date_to').val()
            $('#mko_date_from').attr('max', dateToValue)
        }
        function setDateMin() {
            //setting min value for mko_date_to
            let dateFromValue = $('#mko_date_from').val()
            $('#mko_date_to').attr('min', dateFromValue)
        }
        let report_mko = new Vue({
            el: '#report_mko',
            data: {
                companies: @json($companies),
                mko_id: 3,
                date_from: `{{date('Y-m-d', mktime(0, 0, 0, date("m")-1, 1))}}`,
                date_to: `{{date('Y-m-d', mktime(0, 0, 0, date("m"), 0))}}`,
                dispatch_number: '01',
                errors: [],
                rowdata: [],
                messages: [],
                loading: false,
            },
            methods: {
                getCompanyName(id){
                    let companyName = this.companies.find(company => company.id == id)
                    if (!companyName) return ''
                    return companyName.name_ru
                },
                toggleIsSent(isSent, idx){
                    this.rowdata[idx].sent_loading = true
                    let headers = { 'Content-Language': window.Laravel.locale, 'Authorization': `Bearer ${window.globalApiToken}`}
                    axios.post(`/api/v1/reports/mko-reports/sent/${this.rowdata[idx].id}`,null,{headers}).then( ({data})=> {
                        console.log(data, 'daad');
                        if (!data) { this.rowdata[idx].sent_loading = false;return }
                        
                        if (data.status == 'success') {
                            this.rowdata[idx].is_sent = isSent
                            console.log(this.rowdata[idx].is_sent, 'this.rowdata[idx].is_sent');
                        }
                        this.rowdata[idx].sent_loading = false;
                    })
                    .catch(e => {
                        this.rowdata[idx].sent_loading = false
                        console.error(e);
                    });
                },
                toggleIsError(isError, idx){
                    this.rowdata[idx].error_loading = true
                    let headers = { 'Content-Language': window.Laravel.locale, 'Authorization': `Bearer ${window.globalApiToken}`}
                    axios.post(`/api/v1/reports/mko-reports/error/${this.rowdata[idx].id}`,null,{headers}).then( ({data})=> {
                        console.log(data, 'daad');
                        if (!data) { this.rowdata[idx].error_loading = false;return }
                        
                        if (data.status == 'success') {
                            this.rowdata[idx].is_error = isError
                            console.log(this.rowdata[idx].is_error, 'this.rowdata[idx].is_error');
                        }
                        this.rowdata[idx].error_loading = false;
                    })
                    .catch(e => {
                        this.rowdata[idx].error_loading = false
                        console.error(e);
                    }); 
                },
                fetchReportList(){
                    this.loading = true
                    
                    let data = {
                        from: this.date_from,
                        to: this.date_to,
                        count: 1, 
                        offset: 1
                    }
                    let headers = { 'Content-Language': window.Laravel.locale, 'Authorization': `Bearer ${window.globalApiToken}`}
                    axios.post('/api/v1/reports/mko-reports', data, {headers}).then(response => {
                        if (!response || !response.data) { this.loading = false;return }
                        if (response.data.status == 'success') {
                            let reports = response?.data?.data
                            reports = reports.map(el => {
                                return {...el, sent_loading: false, error_loading: false}
                            });
                            this.rowdata = reports
                        }
                        
                        this.loading = false;
                    })
                    .catch(e => {
                        this.loading = false
                        console.error(e);
                    });

                },
                fetchReport(){

                    this.loading = true
                    
                    let data = {
                        mko_id: this.mko_id,
                        from: this.date_from,
                        to: this.date_to,
                        dispatch_number: this.dispatch_number
                    }
                    let headers = { 'Content-Language': window.Laravel.locale, 'Authorization': `Bearer ${window.globalApiToken}`}
                    
                    axios.post('/api/v1/reports/from-mko', data, {headers}).then(response => {
                        if (!response || !response.data) { this.loading = false;return }
                        if (response.data.status == 'success') {
                            // this.fetchReportList()
                        }
                        this.fetchReportList()
                        
                        this.loading = false;
                    })
                    .catch(e => {
                        this.loading = false
                        console.error(e);
                    });

                    
                },
                uppercase(e){ e.target.value = e.target.value.toUpperCase() }
            },
        });

    </script>

@endsection
