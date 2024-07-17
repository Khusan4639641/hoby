@extends('templates.panel.app')

@section('title', 'Добавить')
@section('class', 'mfo create')

@section('content')
    <style>
        /* The switch - the box around the slider */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        /* Hide default HTML checkbox */
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        /* The slider */
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary);
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>
    <style>
        .fade-scale {
            transform: scale(.8) translateY(10px);
            opacity: 0;
            -webkit-transition: all .15s linear;
            -o-transition: all .15s linear;
            transition: all .15s linear;
        }

        .form-control.modified + .invalid-feedback {
            animation: slide-down .35s ease-in-out
        }

        @keyframes slide-down {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-control.modified.is-invalid {
            background-color: #ffd7d736;
        }

        .form-control.modified.is-invalid:focus {
            border-color: #ff7885;
        }

        .fade-scale.show {
            opacity: 1;
            transform: scale(1) translateY(0px);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn.processing {
            opacity: .3;
        }

        .btn .spinner-border {
            display: none;
            width: 1rem;
            height: 1rem;
            border: 2px solid currentColor;
            border-right-color: transparent;
        }

        .btn.processing .spinner-border {
            display: inline-block;
        }

        .btn:focus {
            border: 1px solid transparent;
        }

        button:focus {
            outline: none;
            box-shadow: none;
        }

        .form-group label {
            font-style: normal;
            font-weight: 400;
            font-size: 15px;
            line-height: 24px;
            letter-spacing: 0.01em;
            color: #2A2A2A;
        }

        .spinner-border {
            width: 20px;
            height: 20px;
            border: 1px solid currentColor;
            border-right-color: transparent;
        }

        .btn.disabled {
            cursor: not-allowed;
            pointer-events: none;
            opacity: .65;
        }

        .btn.btn-orange-light {
            background-color: #6610F51A;
        }

        .btn.btn-orange-light:hover {
            background-color: #6610f533;
        }

        .form-control.modified {
            border-radius: 14px;
            height: 56px;
            line-height: 56px;
            padding: 16px !important;
        }

        .form-control.modified.is-invalid {
            box-shadow: none !important;
            border: 1px solid #ff97a1 !important;
        }

        section {
            margin-bottom: 1rem;
        }

        section hr {
            margin-left: -2rem;
            margin-right: -2rem;
        }

        section .section-title {
            font-family: 'Gilroy';
            font-style: normal;
            font-weight: 700;
            font-size: 24px;
            line-height: 30px;
            color: #1E1E1E;
            margin-bottom: 1.5rem;
        }

        .form-group {
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .btn {
            border-radius: 14px !important;
            padding: 15px 42px !important;
        }
    </style>

    <section class="mfo" id="mfo-create">
        <validation-observer v-slot="{ invalid, handleSubmit }">
            <form @submit.prevent="handleSubmit(onCreateMfo)">
                <div class="form-row">
                    <validation-provider class="form-group col-md-6" rules="required|min:20" v-slot="{ errors }"
                                         name="mfoData.mfo_account_number">
                        <label for="accountMfo">Cчет МФО</label>
                        <input type="text" class="form-control modified" id="accountMfo"
                               :class="{'is-invalid': errors[0]}" v-mask="'####################'"
                               name="mfoData.mfo_account_number" v-model="mfoData.mfo_account_number">
                        <p class="pl-2 invalid-feedback">@{{ errors[0] ?? 'Минимальное значение 20' }}</p>
                    </validation-provider>

                    <validation-provider class="form-group col-md-6" v-slot="{ errors }" rules="required|max:6"
                                         name="mfoData.account_1c_number">
                        <label for="account1c">Счет 1С</label>
                        <input type="tel" class="form-control modified" :class="{'is-invalid': errors[0]}"
                               id="account1c" name="mfoData.account_1c_number" v-model="mfoData.account_1c_number">
                        <p class="pl-2 invalid-feedback">@{{ errors[0] }}</p>
                    </validation-provider>
                </div>

                <validation-provider class="form-group" rules="required" v-slot="{ errors }"
                                     name="mfoData.account_1c_name" tag="div">
                    <label for="accountName1c">Наименование 1С счета</label>
                    <input type="text" class="form-control modified" id="nameAccount1c"
                           :class="{'is-invalid': errors[0]}" name="mfoData.account_1c_name"
                           v-model="mfoData.account_1c_name">
                    <p class="pl-2 invalid-feedback">@{{ errors[0] }}</p>
                </validation-provider>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="accountName1c">Тип учетной карточки</label>
                        <input type="text" v-mask="'#'" class="form-control modified" v-model="mfoData.account_type">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="accountName1c">Системный номер учетной карточки</label>
                        <input type="text" v-mask="'##########'" class="form-control modified"
                               v-model="mfoData.account_system_number">
                    </div>
                </div>

                <div class="form-group">
                    <label for="">Субконто</label>
                    <div class="form-check d-flex align-items-center p-0">
                        <label class="switch mr-2">
                            <input type="checkbox" id="gridCheck" v-model="mfoData.is_subconto">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <div v-if="mfoData.is_subconto"  class="form-row align-items-center" name="mfoData.subconto_number">
                    <div class="form-group col-md-6 p-0">
                        <label for="subcontoNumber">Номер субконто</label>
                        <input type="number" class="form-control modified" id="subcontoNumber" required v-model="mfoData.subconto_number">
                    </div>

                    <div class="form-group col-md-3 ml-4">
                        <label for="">Субконто без остатков</label>
                        <div class="form-check d-flex align-items-center p-0">
                            <label class="switch mr-2">
                                <input type="checkbox" id="gridCheck" v-model="mfoData.is_subconto_without_remainder">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" :disabled="invalid" class="btn btn-primary">Добавить</button>
                <button type="button" class="btn btn-secondary" @click="onCancelCreate">Отменить</button>
            </form>
        </validation-observer>
    </section>

    <script>
        const app = new Vue({
            el: "#mfo-create",
            components: {
                ValidationObserver: VeeValidate.ValidationObserver,
                ValidationProvider: VeeValidate.ValidationProvider
            },
            data: {
                mfoData: {
                    mfo_account_number: undefined,
                    account_1c_number: undefined,
                    account_1c_name: undefined,
                    is_subconto: false,
                    subconto_number: undefined,
                    account_type: undefined,
                    account_system_number: undefined,
                    is_subconto_without_remainder: undefined,
                },
            },
            methods: {
                onCreateMfo() {
                    axios.post('/api/v3/accounts', this.mfoData, {
                        headers: {
                            Authorization: `Bearer ${globalApiToken}`,
                            'Content-Language': '{{app()->getLocale()}}'
                        },
                    }).then(response => {
                        if (response.data.status == 'success') {
                            polipop.add({title: `Успешно`, type: 'success'})
                            window.location.href = "{{ route('panel.accounts.index', [app()->getLocale()]) }}"
                        }
                    }).catch(err => {
                        console.error(err.response)
                        err.response?.data?.error?.forEach((error) => polipop.add({
                            content: `Ошибка: ${error.text}`,
                            title: `Ошибка`,
                            type: 'error'
                        }))
                    })
                },
                onCancelCreate() {
                    window.location.href = "{{ localeRoute('panel.accounts.index') }}"
                }
            },
        })
    </script>
@endsection
