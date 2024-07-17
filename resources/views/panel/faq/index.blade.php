@extends('templates.panel.app')

@section('title', 'F.A.Q')

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
        .content .center .center-body {
            padding: 0;
        }

        .faq {
            padding: 20px;
            position: relative;
        }

        .faq__title {
            font-size: 22px;
            font-weight: 700;
        }
        .faq__item {
            padding: 21px;
            box-shadow: 0 0 3px #ccc;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .faq__header {
            margin-bottom: 10px;
        }

        .faq__header .faq__buttons button {
            background: transparent;
            border: none;
            outline: none !important;
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-left: 10px;
        }

        .faq__header .faq__buttons button svg {
            margin-right: 5px;
        }

        .faq__text {
            color: #696969;
            font-size: 14px;
            font-weight: 400;
        }

        .faq__status {
            display: flex;
            padding: 8px 10px;
            width: 135px;
            height: 38px;
            background: rgba(15, 190, 123, 0.05);
            border-radius: 6px;
            color: #0FBE7B;
        }

        .faq__buttons_arrow {
            background: #F6F6F6 !important;
            border-radius: 8px;
            padding: 5px;
        }

        .faq__body {
            transition: all 300ms linear;
        }

        .faq__arrow {
            padding: 5px;
            background: #7000FF;
            border-radius: 8px;
            color: #fff;
            border: none;
            outline: none !important;
        }

        .faq__text {
            transition: all 300ms linear;
        }

        .disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .modal {
            padding: 38px 24px;
        }

        hr {
            height: 2px;
            border-top: 2px solid #f8f8f8;
        }

        .show-half {
            height: 50px;
            overflow: hidden;
        }

    </style>
    <style>
        .form-control.modified {
            border-radius: 14px;
            height: 56px;
            padding: 16px !important;
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

    <div class="faq" id="faq">
        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-primary" @click="onModalOpen">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="d-inline-block mr-2">
                    <path
                        d="M12 9.016V14.984M9.016 12H14.984M18.332 21.332H5.66797C4.01097 21.332 2.66797 19.989 2.66797 18.332V5.66797C2.66797 4.01097 4.01097 2.66797 5.66797 2.66797H18.332C19.989 2.66797 21.332 4.01097 21.332 5.66797V18.332C21.332 19.989 19.989 21.332 18.332 21.332Z"
                        stroke="#FFFF" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Добавить
            </button>
        </div>

        <template v-if="faqs.length">
            <div class="faq__item" v-for="(faq, index) in faqs">
                <div class="faq__header d-flex align-items-center justify-content-between">
                    <div :class="{'faq__status' : faq.isNew}">
                        @{{ faq.isNew ? 'Новый вопрос' : '' }}
                    </div>

                    <div class="faq__buttons d-flex">
                        <button @click="onChangeFaqStatus(faq.id)">
                            <img :src="faq.status ? '{{ asset('assets/icons/eye-hide.svg') }}' :
                                '{{ asset('assets/icons/eye.svg') }}'"
                                 alt="" style="margin-right: 5px">
                            @{{ faq.status ? 'Скрыть' : 'Показать' }}
                        </button>
                        <button @click="openFaqToEdit(faq)">
                            <svg width="23" height="23" viewBox="0 0 25 25" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12.6593 7.46428L15.1447 9.94963M4.18585 15.9369L15.6384 4.48438L20.2179 9.06393L8.76541 20.5165H4.18463L4.18585 15.9369Z"
                                    stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Изменить
                        </button>
                        <button class="faq__buttons_arrow" :class="{ 'disabled': index === 0 }"
                                @click="onMoveFaq(faq, index, index - 1)">
                            <img src="{{ asset('assets/icons/icon_arrow_up.svg') }}" alt="icon-arrow-up" />
                        </button>
                        <button class="faq__buttons_arrow" :class="{ 'disabled': index === faqs.length - 1 }"
                                @click="onMoveFaq(faq, index, index + 1)">
                            <img src="{{ asset('assets/icons/icon_arrow_up.svg') }}" alt="icon-arrow-down"
                                 style="transform: rotate(180deg)" />
                        </button>
                    </div>
                </div>

                <div class="faq__body" :class="{ 'disabled': !faq.status }">
                    <div class="faq__subheader d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="faq__title">@{{ faq.question_ru }}</h2>
                        </div>
                        <button @click="faq.isOpened = !faq.isOpened" class="faq__arrow d-flex justify-content-end ml-auto">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                 xmlns="http://www.w3.org/2000/svg"
                                 :style="faq.isOpened ? 'transform: rotate(180deg); transition: 300ms linear' :
                                'transition: 300ms linear'">
                                <path d="M18.433 9.44922L12.004 15.8792L5.57397 9.44922" stroke="#ffff" stroke-miterlimit="10"
                                      stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>

                    <p v-show="faq.status == 0 || faq.isOpened" class="faq__text text-justify" :class="{'show-half': faq.status == 0}"> @{{ faq.answer_ru }} </p>

                    <div v-show="faq.isOpened">
                        <hr>
                        <h2 class="faq__title">@{{ faq.question_uz }}</h2>
                        <p class="faq__text text-justify"> @{{ faq.answer_uz }} </p>
                    </div>
                </div>
            </div>
        </template>

        <h1 v-else-if="isLoading" class="text-center text-gray-500">Загрузка...</h1>

        <h1 v-else class="text-center text-gray-500">Данные не найдены!</h1>

        <div class="modal fade-scale" id="modalConfirm" tabindex="-1" role="dialog" style="top: 0">
            <form class="modal-dialog" role="document" style="max-width:1000px;">
                <div class="modal-content">
                    <div class="modal-header border-0 pl-4 pr-4 pt-4">
                        <h3 v-if="isEditing">Редактировать</h3>
                        <h3 v-else>Создать новый вопрос/ответ</h3>

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none">
                                    <path d="M6.66699 6.646L17.333 17.31M6.66699 17.31L17.333 6.646" stroke="#1E1E1E"
                                          stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round"
                                          stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                    <div class="modal-body px-4 pt-0 text-left pb-0">
                        <h4>Русский</h4>
                        <div class="form-group" v-if="!isEditing">
                            <label class="text-left">Введите вопрос на русском языке</label>
                            <input required type="search" class="form-control modified" v-model.trim="faq.question_ru">
                        </div>

                        <div class="form-group">
                            <label class="text-left">Ответ на вопрос на русском языке</label>
                            <textarea required type="search" class="form-control modified" style="min-height: 100px" v-model.trim="faq.answer_ru"></textarea>
                        </div>

                    </div>

                    <div class="px-4">
                        <hr>
                    </div>

                    <div class="modal-body px-4 pt-0 pb-0 text-left">
                        <h4>Узбекский</h4>
                        <div class="form-group" v-if="!isEditing">
                            <label class="text-left">Введите вопрос на узбекском языке</label>
                            <input :readonly="isEditing" required type="search" class="form-control modified" v-model.trim="faq.question_uz">
                        </div>

                        <div class="form-group">
                            <label class="text-left">Ответ на вопрос на узбекском языке</label>
                            <textarea required type="search" class="form-control modified" style="min-height: 100px" v-model.trim="faq.answer_uz"></textarea>
                        </div>


                        <div class="form-check d-flex align-items-center p-0">
                            <label class="switch mr-2">
                                <input type="checkbox" v-model="faq.isFirst" id="gridCheck">
                                <span class="slider round"></span>
                            </label>
                            <label class="ml-2 mt-2">Вывести вопрос первым в списке</label>
                        </div>

                    </div>

                    <div class="modal-footer border-0 justify-content-center">
                        <button v-if="!isEditing" class="btn btn-primary" type="button" @click="onFaqCreate" :disabled="isBtnDisabled">Сохранить</button>
                        <button v-else class="btn btn-primary" type="button" @click="onFaqEdit" :disabled="isBtnDisabled">Изменить</button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    <script>
        const app = new Vue({
            el: "#faq",
            data: {
                isLoading: false,
                faq: {
                    answer_uz: '',
                    question_uz: '',
                    answer_ru: '',
                    question_ru: '',
                    isFirst: false,
                },
                faqs: [],
                isEditing: false,
            },
            computed: {
                isBtnDisabled() {
                    return !this.faq.answer_uz.length || !this.faq.question_uz.length || !this.faq.answer_ru.length || !this.faq.question_ru.length
                }
            },
            methods: {
                onModalOpen() {
                    this.isEditing = false
                    this.faq =  {
                        answer_uz: '',
                        question_uz: '',
                        answer_ru: '',
                        question_ru: '',
                        isFirst: false,
                    }
                    $('#modalConfirm').modal('show');
                },
                async onMoveFaq(faq,fromIndex, toIndex) {
                    try {
                        const { data: response } = await axios.post(`/api/v3/admin/faq-info/update/${faq.id}`,{
                            sort: this.faqs[toIndex].sort,
                        }, {
                            headers: {
                                Authorization: `Bearer ${globalApiToken}`
                            }
                        })
                        if (response.status === 'success') {
                            this.faqs.splice(fromIndex, 1);
                            this.faqs.splice(toIndex, 0, faq);
                            polipop.add({ content: 'Местоположение элемента успешно изменено', title: `Успешно`, type: 'success' })
                            await this.getFaqList();
                        }
                    }catch(err){
                        console.error(err)
                    }
                },
                async onChangeFaqStatus(faqId) {
                    const faqIndex = this.faqs.findIndex(faq => faq.id === faqId)
                    this.faqs[faqIndex].status = !this.faqs[faqIndex].status
                    try {
                        const {data: response} = await axios.post(`/api/v3/admin/faq-info/update/${faqId}`, {
                            status: this.faqs[faqIndex].status === true ?  1 : 0
                        }, {
                            headers: {
                                Authorization: `Bearer ${globalApiToken}`
                            }
                        })
                        if(response.status === 'success') {
                            polipop.add({ content: 'Статус изменен', title: `Успешно`, type: 'success' })
                        }
                    }catch(err){
                        console.error(err)
                        polipop.add({ title: `Что-то пошло не так`, type: 'error' })
                    }
                },
                async onFaqCreate(){
                    try {
                        const {data: response} = await axios.post('/api/v3/admin/faq-info/insert', {
                                ...this.faq,
                                sort: this.faq.isFirst ? 1 : undefined,
                            },
                            {
                                headers: {
                                    Authorization: `Bearer ${globalApiToken}`
                                }
                            })
                        if (response.status === 'success'){
                            polipop.add({ title: `Успешно`, type: 'success' })
                            await this.getFaqList()
                        }
                    }catch(err){
                        console.error(err)
                    }
                    finally {
                        $('#modalConfirm').modal('hide');
                    }
                },
                async getFaqList() {
                    this.isLoading = true
                    try {
                        const { data: response } = await axios.get('/api/v3/admin/faq-info/list?page=1&limit=50', {
                            headers: {
                                Authorization: `Bearer ${globalApiToken}`
                            }
                        })
                        const today = new Date()
                        this.faqs = response.data.data.map(faq => {
                            faq.isOpened = false
                            faq.status === 1 ? faq.status = true : faq.status = false
                            faq.isNew = moment(faq.created_at).format('DD.MM.YYYY') === moment(today).format('DD.MM.YYYY')
                            return faq
                        })
                    }catch(err){
                        console.error(err)
                    }
                    finally {
                        this.isLoading = false
                    }
                },
                openFaqToEdit(faq) {
                    this.isEditing = true
                    this.faq = {...faq}
                    $('#modalConfirm').modal('show');
                },
                async onFaqEdit(){
                    try {
                        const {data: response} = await axios.post(`/api/v3/admin/faq-info/update/${this.faq.id}`, {
                                answer_uz: this.faq.answer_uz,
                                answer_ru: this.faq.answer_ru,
                                question_uz: this.faq.question_uz,
                                question_ru: this.faq.question_ru,
                                sort: this.faq.isFirst ? 1 : undefined
                            },
                            {
                                headers: {
                                    Authorization: `Bearer ${globalApiToken}`
                                }
                            })
                        if (response.status === 'success'){
                            polipop.add({ content: "Данные изменены", title: `Успешно`, type: 'success' })
                            await this.getFaqList()
                        }
                    }catch(err){
                        console.error(err)
                    }
                    finally {
                        $('#modalConfirm').modal('hide');
                    }
                }
            },
            async mounted(){
                await this.getFaqList()
            }
        })
    </script>

@endsection
