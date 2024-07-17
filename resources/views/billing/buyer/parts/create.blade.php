<script>
    const userFiles = {
        passport_selfie: {
            old: '',
            new: null,
            preview: '',
        },
        passport_first_page: {
            old: '',
            new: null,
            preview: '',
        },
        passport_with_address: {
            old: '',
            new: null,
            preview: '',
        },
        id_selfie: {
            old: '',
            new: null,
            preview: '',
        },
        id_first_page: {
            old: '',
            new: null,
            preview: '',
        },
        id_second_page: {
            old: '',
            new: null,
            preview: '',
        },
        id_with_address: {
            old: '',
            new: null,
            preview: '',
        },
    };
    var newBuyer = new Vue({
        el: '#newBuyer',
        components: {
            ValidationObserver: VeeValidate.ValidationObserver,
        },
        data: {
            passportData: [
                {
                    name: 'passport_selfie',
                    label:  i18n.buyer.validations.passport_selfie,
                },
                {
                    name: 'passport_first_page',
                    label:  i18n.buyer.validations.passport_first_page,
                },
                {
                    name: 'passport_with_address',
                    label: i18n.buyer.validations.passport_with_address,
                },
            ],
            idCardData: [
                {
                    name: 'id_selfie',
                    label:  i18n.buyer.validations.id_selfie,
                },
                {
                    name: 'id_first_page',
                    label:  i18n.buyer.validations.id_first_page
                },
                {
                    name: 'id_second_page',
                    label: i18n.buyer.validations.id_second_page
                },
                {
                    name: 'id_with_address',
                    label:  i18n.buyer.validations.id_with_address
                },
            ],
            cardChange: true,
            registerByPassport: true,
            errors: {},
            messages: [],
            orderCreated: false,
            files_to_delete: [],
            guarantPhoneNumber1: '',
            guarantPhoneNumber2: '',
            guarantName1: '',
            guarantName2: '',
            step: 1,
            formComplete: false,
            loading: false,
            cardAdded: false,
            phoneAdded: false,
            countdownInterval: null,
            countdownTimer: 0,

            locale: '{{ucfirst(app()->getLocale())}}',
            region: {
                list: [],
                disabled: false,
            },
            area: {
                list: [],
                disabled: true,
            },
            city: {
                list: [],
                disabled: true,
            },
            buyer: null,
            user: {
                api_token: '{{Auth::user()->api_token}}',
                buyer_id: null,
                phone: {
                    number: '',
                    smsCode: '',
                    hashedSmsCode: '',
                    showInputSMSCode: false,
                },
                card: {
                    type: '',
                    number: '',
                    exp: '',
                    smsCode: '',
                    hashedSmsCode: '',
                    showInputSMSCode: false,
                },
                name: '',
                surname: '',
                patronymic: '',
                birthday: '',
                work_company: '',
                work_phone: '',
                home_phone: '',
                pinfl: '',
                address_region: '',
                address_area: '',
                address_city: '',
                address: '',
                files: JSON.parse(JSON.stringify(userFiles)),
            },
            userStatus: null,
        },
        computed: {
            userCardValidate() {
                return {
                    number: this.user.card.number.replaceAll(' ','').length === 16,
                    exp: this.user.card.exp.replace('/','').length === 4
                };
            },
            firstValid() {
                return this.guarantName1 !== '' && this.guarantPhoneNumber1 !== '';
            },
            formattedCountdown() {
                let seconds,s,m
                if (this.countdownTimer > 0 && this.countdownTimer < 1000) this.countdownTimer = 0
                seconds = Math.trunc(this.countdownTimer/1000)
                s = seconds%60
                m = Math.trunc(seconds/60)
                return {m, s: s <= 9 ? `0${s}` : s}
            },
            cardValidation() {
                const cardBlogs = this.user.card.number?.split(' '); // [ ####, ####, ####, ####]
                const firstBlog = cardBlogs[0];

                const lastTwoNumbers = cardBlogs[1]?.slice(2, 4);
                // Проверка на корпоративную карту
                if (firstBlog === '8600') {
                    if (lastTwoNumbers === '32' || lastTwoNumbers === '08') {
                        return i18n.buyer.validations.c_card_error;
                    }
                }

                return false;
            },
        },
        methods: {
            isPhoneValid(phone) {
                // TODO: Create phone validation helper
                let regex = (/^[\+]{0,1}(?:998)?[\s]*[\(]{0,1}([0-9]{2})[\)]{0,1}[\s]*([0-9]{3})[-]*([0-9]{2})[-]*([0-9]{2})$/g)
                return regex.test(phone)
            },
            checkRegisterPhoto(condition){
                this.registerByPassport = condition
                this.user.files = JSON.parse(JSON.stringify(userFiles));
            },
            startTimer: function (end) {

                let endtime = moment(end)
                let now = moment()
                let diff = endtime.diff(now)
                ms = diff < 0 ? 0 : diff
                clearInterval(this.countdownInterval);
                this.countdownTimer = ms
                this.countdownInterval = setInterval(()=>{
                    this.countdownTimer -= 1000
                    if (this.countdownTimer <= 0) clearInterval(this.countdownInterval);
                }, 1000)
            },

            checkPhone: function (e) {
                let isPhoneValid = this.isPhoneValid(this.user.phone.number)
                this.errors = {};
                this.messages = [];

                if (this.user.phone.number && isPhoneValid) {
                    this.loading = true;
                    axios.post('/api/v1/partner/buyers/validate-form', {
                        phone: this.user.phone.number,
                        api_token: this.user.api_token,
                        step: this.step,
                    }).then(response => {
                            if (response.data.status === 'success') {
                                // this.user.phone.showInputSMSCode = true;
                                this.sendPhoneSmsCode();
                            } else {
                                this.user.phone.showInputSMSCode = false;
                                if (response.data.response.errors == 'you_blocked') {
                                    this.errors.phone = '{{__('cabinet/cabinet.you_blocked')}}';
                                }
                                newBuyer.$forceUpdate();
                                this.loading = false;
                            }

                        })
                        .catch(e => {
                            this.loading = false;
                            this.errors.system = [];
                            this.errors.system.push(e);
                        });




                } else {
                    this.errors.phone = '{{__('auth.error_phone_is_empty')}}';
                }

            },

            isLetter(e) {
                let char = String.fromCharCode(e.keyCode); // Get the character
                if (/^[A-Za-z, ^А-ЯЁа-яё]+$/.test(char)) return true; // Match with regex
                else e.preventDefault(); // If not match, don't add to input text
            },
            // отправляем смс для подтверждения номера телефона из вендора
            sendPhoneSmsCode: function (e) {
                this.errors = {};
                this.errors.sms = [];
                this.loading = true;
                axios.post('/api/v1/partner/buyers/send-sms-code', {
                    phone: this.user.phone.number,
                    api_token: this.user.api_token,
                })
                    .then(response => {
                        if (!response || !response.data) { this.loading = false;return }
                        if (response.data.hash) {
                            this.user.phone.hashedSmsCode = response.data.hash;
                            this.user.phone.showInputSMSCode = true;

                            setTimeout(() => {
                                this.$refs.phoneSMSCodeRef.focus()
                            }, 0);
                        }
                        if (response.data.status === 'error' && response.data.info === 'sms_not_sended')
                            this.errors.sms.push('{{__('panel/buyer.sms_not_sended')}}');
                        if (response.data.status === 'error_scoring')  this.errors.sms.push('{{__('panel/buyer.error_scoring')}}');
                        this.loading = false;
                    })
                    .catch(e => {
                        this.loading = false
                        this.errors.sms = [];
                        this.errors.sms.push(e);
                    });
                //newBuyer.$forceUpdate();
            },

            checkPhoneSmsCode: function (e) {
                this.errors = {};
                this.messages = [];
                if (String(this.user.phone.smsCode).length != 4) return
                this.loading = true;
                if (this.user.phone.smsCode) {
                    axios.post('/api/v1/partner/buyers/check-sms-code', {
                            api_token: this.user.api_token,
                            code: this.user.phone.smsCode,
                            hashedCode: this.user.phone.hashedSmsCode,
                            phone: this.user.phone.number,
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    )
                    .then(async response => {
                        if (response.data.status === 'success') {
                            this.loading = false;
                            await this.add();
                            this.step = 2;
                            this.phoneAdded = true;
                        } else {
                            this.loading = false;
                            this.errors.sms = [];
                            response.data.response.message.forEach(element => this.errors.sms.push(element.text));
                        }
                        newBuyer.$forceUpdate();
                    })
                    .catch(e => {
                        console.log(e)
                        this.errors.sms = [];
                        this.errors.sms.push(e);
                    });
                }
            },
            // добавление пользователя и активация блока для ввода данных
            add: function (e) {
                this.errors = {};
                this.messages = [];
                this.loading = true;

                axios.post('/api/v1/partner/buyers/add', {
                    phone: this.user.phone.number,
                    api_token: this.user.api_token,
                    step: this.step,
                })
                    .then(response => {
                        if (response.data.status === 'success') {
                            const data = response.data.data
                            this.user.buyer_id = data.id;
                            this.userStatus = data.status
                            setTimeout(() => {
                                    this.$refs.inputCardNumberRef.focus()
                            }, 0);
                            if (data.guarants && data.guarants.length > 0) {
                                this.guarantName1 = data.guarants[0].name || '';
                                this.guarantPhoneNumber1 = data.guarants[0].phone || '';

                                if (data.guarants.length > 1) {
                                    this.guarantName2 = data.guarants[1].name || '';
                                    this.guarantPhoneNumber2 = data.guarants[1].phone || '';
                                }
                            }

                            this.cardAdded = data.ce == 1 ? true : false;
                            this.user.phone.showInputSMSCode = false;

                            if (this.cardAdded) {
                                this.cardAdded = true;
                            }

                            if (data.status == 4 || data.status == 8) {
                                this.errors.system = [];
                                this.errors.system.push(('{{__('auth.user_was_registered')}}'));
                            } else {
                                this.step = 2;
                                this.formComplete = true;
                            }

                        } else {
                            this.errors = response.data.response.errors;
                        }
                        newBuyer.$forceUpdate();
                        this.loading = false;
                    })
                    .catch(e => {
                        this.loading = false;
                        this.errors.system = [];
                        this.errors.system.push(e);
                    });
            },

            beforeSendCardSmsCode: function () {
                if (!this.userCardValidate.number) return
                if (!this.userCardValidate.exp) return this.$refs.inputCardExpRef.focus()
                this.sendCardSmsCode()
            },
            // отправка смс кода для otp uzcard
            sendCardSmsCode: function (e) {
                this.errors = {};
                this.messages = [];
                this.errors.card = [];
                if (!this.cardValidation) {
                    if (!this.userCardValidate.number) {
                        this.errors.card_number = [];
                        this.errors.card_number.push(i18n.buyer.validations.error_card_number_empty);
                        return
                    }
                    if (!this.userCardValidate.exp) {
                        this.errors.card_exp = [];
                        this.errors.card_exp.push(i18n.buyer.validations.error_card_exp_empty);
                        return
                    }
                    this.loading = true;
                    this.cardChange = true;
                    const userData = {
                        phone: this.user.phone.number,
                        card: this.user.card.number,
                        exp: this.user.card.exp,
                        api_token: this.user.api_token,
                        buyer: this.user,
                    };
                    axios.post('/api/v1/buyer/send-sms-code-uz', userData, { headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            let err = false;

                            if (response.data.status == 'success') {
                                this.cardChange = false;
                            }
                            if (response.data.info === 'error_card_equal') {
                                this.errors.card = [];
                                this.errors.card = [
                                    i18n.buyer.validations.error_card_equal,
                                    response.data.card_data?.card_owner,
                                    response.data.card_data?.card_phone,
                                    response.data.card_data.total_debt,
                                ];
                                err = true;
                            }
                            if (response.data.info === 'sms_not_sended') {
                                this.errors.card = [];
                                err = true;
                                this.errors.card.push(i18n.buyer.validations.sms_not_sended);
                            }
                            if (response.data.info === 'error_phone_not_equals') {
                                this.errors.card = [];
                                err = true;
                                this.errors.card.push(i18n.buyer.validations.error_phone_not_equals);
                            }

                            if (response.data.info === 'empty_balance') {
                                this.errors.card = [];
                                this.errors.card.push(i18n.buyer.validations.empty_balance);
                                err = true;
                            }

                            if (response.data.info === 'error_card_exp') {
                                this.errors.card = [];
                                this.errors.card.push(i18n.buyer.validations.error_card_exp);
                                err = true;
                            }

                            if (response.data.info === 'error_card_sms_off') {
                                this.errors.card = [];
                                this.errors.card.push(i18n.buyer.validations.error_card_sms_off);
                                err = true;
                            }

                            if (response.data.status === 'error_card_scoring') {
                                this.errors.card = [];
                                this.errors.card.push(i18n.buyer.validations.error_card_scoring);
                                err = true;
                            }
                            if (response.data.status === 'error' && response.data.info === 'card_sms_limit') {
                                this.errors.card = [];
                                this.errors.card.push(response.data.message);
                                this.cardChange = false;
                                this.startTimer(response.data.access_after)
                            }
                            if (Array.isArray(response.data.info)){
                                this.errors.card = [];
                                response.data.info.forEach((value) => {
                                    err = true;
                                    this.errors.card.push(value);
                                })
                            }

                            if (!err) {
                                this.user.card.hashedSmsCode = response.data.hash;
                                this.user.card.showInputSMSCode = true;
                                this.startTimer(response.data.access_after)
                                setTimeout(() => {
                                    this.$refs.cardSMSCodeRef.focus()
                                }, 0);
                            }
                            this.loading = false;
                            newBuyer.$forceUpdate();

                        })
                        .catch(e => {
                            this.errors.smsCard = [];
                            this.errors.smsCard.push(e);
                            this.loading = false;
                        });


                } else {
                    console.log(
                        '%cdont add corporate or invalid card (only gives uzcard or humo)',
                        'background-color: orange; color: white; border-radius: 8px; padding: 8px; text-transform: uppercase',
                    );
                }

            },

            checkCardSmsCode: function (e) {
                this.errors = {};
                this.messages = [];
                if (this.user.card.smsCode) {
                    this.loading = true;
                    axios.post('/api/v1/buyer/check-sms-code-uz', {
                            card_number: this.user.card.number,
                            card_valid_date: this.user.card.exp,
                            code: this.user.card.smsCode,
                            hashedCode: this.user.card.hashedSmsCode,
                            phone: this.user.phone.number,
                            buyer_id: this.user.buyer_id,
                            api_token: this.user.api_token,
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    )
                        .then(response => {
                            if (response.data.status === 'success') {
                                this.cardAdded = true;
                                this.formComplete = true;
                                this.userStatus = 5;
                                this.loading = false;
                            } else {
                                this.errors.smsCard = [];
                                response.data.response.message.forEach(element => this.errors.smsCard.push(element.text));
                                this.loading = false;
                            }

                        })
                        .catch(e => {
                            this.errors.smsCard = {};
                            this.errors.smsCard.push(e);
                            this.loading = false;
                        })

                } else {
                    this.errors.smsCard = [];
                    this.errors.smsCard.push('{{__('auth.error_code_is_empty')}}');
                    this.loading = false;
                }

            },

            async addBuyerPhotos() {
                let formData = new FormData()

                if(!this.registerByPassport){
                    formData.append('id_selfie', this.user.files.id_selfie.new)
                    formData.append('id_first_page', this.user.files.id_first_page.new)
                    formData.append('id_second_page', this.user.files.id_second_page.new)
                    formData.append('id_with_address', this.user.files.id_with_address.new)
                    formData.append('passport_type', 0)
                }else{
                    formData.append('passport_selfie', this.user.files.passport_selfie.new)
                    formData.append('passport_first_page', this.user.files.passport_first_page.new)
                    formData.append('passport_with_address', this.user.files.passport_with_address.new)
                    formData.append('passport_type', 6)
                }

                formData.append('step', this.step)
                formData.append('api_token', this.user.api_token)
                formData.append('buyer_id', this.user.buyer_id)

                this.loading = true
                try {
                    const response = await axios.post('/api/v1/partner/buyers/modify', formData)
                    if (response.data.status === 'success') {
                        this.userStatus = 12;
                        return
                    }
                } catch(err){
                    console.log(err)
                }
                finally {
                    this.loading = false
                }
            },

            async addBuyerGuarants() {
                try {
                    this.loading = true
                    const response = await axios.post('/api/v1/buyer/add-guarant', {
                        name: this.guarantName1,
                        phone: this.guarantPhoneNumber1,
                        buyer_id: this.user.buyer_id,
                        api_token: this.user.api_token,
                    })
                    if (response.data.status === 'success') {
                        const response2 = await axios.post('/api/v1/buyer/add-guarant', {
                            name: this.guarantName2,
                            phone: this.guarantPhoneNumber2,
                            buyer_id: this.user.buyer_id,
                            api_token: this.user.api_token,
                        })
                            if (response2.data.status === 'success') {
                                this.loading = false;
                                this.orderCreated = true;
                            }
                    }
                } catch(error) {
                    console.log(error)
                }
                finally {
                    this.loading = false
                }
            },

            async updateFiles (e, validate) {
                const { valid } = await validate(e)
                if (!valid) return
                let inputName = e.target.name

                let files = e.target.files
                if (files.length > 0) {
                    this.user.files[inputName].new = files[0];
                    this.user.files[inputName].preview = URL.createObjectURL(files[0]);
                }

                if (this.user.files[inputName].old) {
                    this.files_to_delete.push(this.user.files[inputName].old);
                }
            },

            resetFiles (name = null) {
                if (name != null) {
                    this.user.files[name].new = null
                    this.user.files[name].preview = null
                }
            },

            openFormAddCard: function (e) {
                this.showFormAddCards = true;
            },

            getRegionList: function () {
                axios.post('/api/v1/regions/list', {
                        api_token: this.user.api_token,
                        orderBy: 'name{{ucfirst(app()->getLocale())}}',
                    },
                ).then(response => {
                    if (response.data.status === 'success') {
                        this.region.list = response.data.data;

                        if (this.area.list.length > 0)
                            this.area.disabled = false;
                        if (this.city.list.length > 0)
                            this.city.disabled = false;
                    }
                    this.loading = false;
                });
            },
        },
        created: function () {
            this.getRegionList();
        },
    });
</script>
