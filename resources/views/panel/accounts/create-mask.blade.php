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
        .form-control.modified+.invalid-feedback {
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
        .btn .spinner-border{
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
            background-color: #6610F51A ;
        }
        .btn.btn-orange-light:hover {
            background-color: #6610f533 ;
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
        <div>
            <form @submit.prevent="onCreateMfo">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="accountMfo">Маска МФО</label>
                        <input type="number" class="form-control modified" id="accountMfo" v-model="mfoData.mfo_mask" required>
                    </div>

                   <div class="form-group col-md-6">
                        <label for="account1c">Номер 1С счета</label>
                        <input type="tel" class="form-control modified" id="account1c" v-model="mfoData.one_c_mask" required>
                   </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="accountName1c">Parent ID</label>
                        <input type="number" class="form-control modified" id="nameAccount1c"  name="mfoData.parent_id" v-model="mfoData.parent_id" required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="subkontoNumber">Номер субконто</label>
                        <input type="number" v-number-only class="form-control modified" id="subkontoNumber" v-model="mfoData.number" required>
                    </div>
                </div>

                <div class="form-group col-md-6 p-0">
                    <label for="subkontoNumber">Название счета</label>
                    <input class="form-control modified" id="subkontoNumber" v-model="mfoData.mfo_account_name" required>
                </div>

                <button type="submit" class="btn btn-primary">Добавить</button>
                <button type="button" class="btn btn-secondary" @click="onCancelCreate">Отменить</button>
            </form>
        </div>
    </section>

    <script>
        const app = new Vue({
            el: "#mfo-create",
            data: {
                mfoData: {
                    mfo_mask: undefined,
                    one_c_mask: undefined,
                    parent_id: undefined,
                    mfo_account_name: undefined ,
                    number:undefined
                },
            },
            methods: {
                onCreateMfo(){
                    axios.post('/api/v3/admin/account-match/insert', {
                        ...this.mfoData,
                        parent_id: +this.mfoData.parent_id
                    }, {
                        headers: {
                            Authorization: `Bearer ${globalApiToken}` ,
                        },
                    }).then(response => {
                        if (response.data.status === 'success'){
                            polipop.add({title: `Успешно`, type: 'success'})
                            window.location.href = "{{ route('panel.accounts.index', [app()->getLocale()]) }}"
                        }
                    }).catch(err => {
                        console.error(err.response)
                        err.response?.data?.error?.forEach((error) => polipop.add({content: `Ошибка: ${error.text}`, title: `Ошибка`, type: 'error'}))
                    })
                },
                onCancelCreate(){
                    window.location.href = "{{ localeRoute('panel.accounts.index') }}"
                }
            },
        })
    </script>
@endsection
