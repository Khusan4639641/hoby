<script>
    var verify = new Vue({
        el: '#verify',
        data: {
            errors: {},
            messages: [],
            files_to_delete: [],
            step: 1,
            filevalidate:[],
            showInputSMSCode: false,
            showFormAddCards: true,
            verifyComplete: false,
            hashedSmsCode: '',
            api_format: 'object',
            loading: false,
            waitValidate: false,
            locale: '{{ucfirst(app()->getLocale())}}',
            region: {
                list: [],
                disabled: false
            },
            area: {
                list: JSON.parse('{!! $address->areaList !!}'),
                disabled: true
            },
            city: {
                list: JSON.parse('{!! $address->cityList !!}'),
                disabled: true
            },
            user: {
                api_token: '{{Auth::user()->api_token}}',
                buyer_id: '{{$buyer->id}}',
                phone: '{{$buyer->phone}}',
                name: '{{$buyer->name}}',
                name_humo: '',
                surname: '{{$buyer->surname}}',
                patronymic: '{{$buyer->patronymic}}',
                birthday: '{{ $personals['birthday']}}',
                work_company: '{{ $personals['work_company']}}',
                work_phone: '{{$personals['work_phone']}}',
                home_phone: '{{$personals['home_phone']}}',
                address_region: '{{$address->region}}',
                address_area: '{{$address->area}}',
                address_city: '{{$address->city}}',
                address: '{{$address->address}}',
                verify: null,
                files: {
                    passport_selfie: {
                        old: '{{@$personals['passport_selfie']['id']}}',
                        new: null,
                        preview: '{{@$personals['passport_selfie']['preview']}}'
                    },
                    passport_first_page: {
                        old: '{{@$personals['passport_first_page']['id']}}',
                        new: null,
                        preview: '{{@$personals['passport_first_page']['preview']}}'
                    }

                },
                cards: null,
                card: {
                    number: '',
                    exp: ''
                },
            },
            required: [
               /*'name',
                'surname',
                'patronymic',
                'address_region',
                'address_area',
                'address' */
            ],
            timer: 120
        },
        computed: {
            timers: function(){
                return new Date(this.timer * 1000).toISOString().substr(14, 5);
            }
        },
        methods: {
            checkRequiredFields: function (){
                this.errors = {};
                this.messages = [];
                let result = true;

                for (let i = 0; i < this.required.length; i++){
                    let value = this.required[i];

                    if(this.user[value] == ''){
                        this.errors[value] = [];
                        this.errors[value].push('{{__('cabinet/verify.field_not_empty')}}');
                        result = false;
                        this.waitValidate = false;
                        this.loading = false;
                    }
                }

                return result;
            },
            validateStep: function (e) {
                this.errors = {};
                this.messages = [];
                this.waitValidate = true;
                this.loading = true;
                let formData;
                //this.step++;
                console.log('step validate: ' + this.step)
                if(this.checkRequiredFields()){
                    switch (this.step) {
                        case 2:
                            formData = new FormData();

                            /* formData.append('name', this.user.name);
                            formData.append('surname', this.user.surname);
                            formData.append('patronymic', this.user.patronymic);
                            formData.append('birthday', this.user.birthday);
                            formData.append('work_company', this.user.work_company);
                            formData.append('work_phone', this.user.work_phone);
                            formData.append('home_phone', this.user.home_phone);
                            //formData.append('pinfl', this.user.pinfl);
                            //formData.append('address_country', this.user.address_country);
                            formData.append('address_region', this.user.address_region);
                            formData.append('address_area', this.user.address_area);
                            formData.append('address_city', this.user.address_city);
                            //formData.append('address_postcode', this.user.address_postcode);
                            formData.append('address', this.user.address);*/
                            //console.log(this.user.files.passport_selfie.new)
                            if (this.user.files.passport_selfie.new) {
                                formData.append('passport_selfie', this.user.files.passport_selfie.new);
                            }

                            if (this.user.files.passport_first_page.new) {
                                formData.append('passport_first_page', this.user.files.passport_first_page.new);
                            }



                            break;

                    }

                    formData.append('step', this.step);
                    formData.append('api_token', this.user.api_token);
                    formData.append('buyer_id', this.user.buyer_id);
                    console.log('validateStep before')


                    // отправка формы на сохранение файлов и данных
                    axios.post('/api/v1/buyer/verify/validateForm', formData,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            if (response.data.status === 'success') {
                                this.modify();
                                console.log('validateStep modify')
                            } else {
                                this.waitValidate = false;
                                this.errors = response.data.response.errors;
                            }
                            verify.$forceUpdate();
                            this.loading = false;
                        })
                        .catch(e => {
                            this.errors.push(e);
                        })
                }

            },
            modify: function (e) {
                this.errors = {};
                this.messages = [];
                this.loading = true;
                let formData;

                console.log('modify step: '+this.step); // 2 = OK

                formData = new FormData();

                /* formData.append('name', this.user.name);
                formData.append('surname', this.user.surname);
                formData.append('patronymic', this.user.patronymic);
                formData.append('birthday', this.user.birthday);
                formData.append('work_company', this.user.work_company);
                formData.append('work_phone', this.user.work_phone);
                formData.append('home_phone', this.user.home_phone);
                //formData.append('pinfl', this.user.pinfl);
                //formData.append('address_country', this.user.address_country);
                formData.append('address_region', this.user.address_region);
                formData.append('address_area', this.user.address_area);
                formData.append('address_city', this.user.address_city);
                //formData.append('address_postcode', this.user.address_postcode);
                formData.append('address', this.user.address); */

                if (this.user.files.passport_selfie.new) {
                    formData.append('passport_selfie', this.user.files.passport_selfie.new);
                }

                if (this.user.files.passport_first_page.new) {
                    formData.append('passport_first_page', this.user.files.passport_first_page.new);
                }

                /* if (this.user.files.passport_with_address.new) {
                    formData.append('passport_with_address', this.user.files.passport_with_address.new);
                } */

                formData.append('files_to_delete', this.files_to_delete.join(','));

                formData.append('card_number', this.user.card.number);
                formData.append('card_valid_date', this.user.card.exp);

                formData.append('step', this.step);
                formData.append('api_token', this.user.api_token);
                formData.append('buyer_id', this.user.buyer_id);

                axios.post('/api/v1/buyer/verify/modify', formData,
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                    .then(response => {
                        if (response.data.status === 'success') {
                            this.errors = {};
                            this.messages = [];
                            this.step++;
                            //this.updateList();
                            console.log('before verify')
                            // this.verificationComplete();
                            this.waitValidate = false;
                            this.verifyComplete = true;

                            this.verificationComplete();


                        } else {
                            console.log('NOT verify')
                            this.errors = response.data.response.errors;
                            this.loading = false;
                        }
                        verify.$forceUpdate();

                    })
                    .catch(e => {
                        this.errors.push(e);
                    })
            },
            updateFiles: function (e) {
                let input = e.target.name,
                    files = e.target.files;

                if (files.length > 0) {
                    this.user.files[input].new = files[0];
                    this.user.files[input].preview = URL.createObjectURL(files[0]);
                    this.filevalidate.push(files[0])
                }
                if (this.user.files[input].old) {
                    this.files_to_delete.push(this.user.files[input].old);
                }
                if ( this.filevalidate.length >= 2 ){
                    this.waitValidate = false
                }
                else{
                    this.waitValidate = true
                }

            },
            resetFiles: function (e) {

                let input = e.name;

                this.user.files[input].file = null;
                this.user.files[input].preview = null;
            },
            cardAdd: function(){
                let formData = new FormData();

                formData.append('card_number', this.user.card.number);
                formData.append('card_valid_date', this.user.card.exp);

                formData.append('api_token', this.user.api_token);
                formData.append('buyer_id', this.user.buyer_id);
                if(this.user.verify !== null)
                    formData.append('verify', this.user.verify);


                //console.log(this.user.card)

                //return false;



                console.log('uid: ' + this.user.buyer_id);
                console.log('card add step ' + this.step );

                // if( this.user.verifyHumo !== null )
                //     formData.append('verify_humo', this.user.verifyHumo);


                console.log(this.user.card.number + ' ' + this.user.card.exp);


                axios.post('/api/v1/buyer/card/add', formData,
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                    .then(response => {
                        if (response.data.status === 'success') {
                            this.errors = {};
                            this.messages = [];
                            // this.updateList();
                            // this.verifyComplete = true;
                            this.messages.push('{{__('panel/buyer.txt_card_added')}}');
                            this.resetStep();
                        } else {
                            this.errors = response.data.response.errors;

                        }
                        verify.$forceUpdate();
                    })
                    .catch(e => {
                        this.errors.system = [];
                        this.errors.system.push(e);
                    })

            },
            openFormAddCard: function (e) {
                this.showFormAddCards = true;
            },
            sendSmsCode: function (e) {
                this.errors = {};
                this.messages = [];
                this.errors.phone = [];
                let type = 2;
                let url = '/api/v1/buyer/send-sms-code-humo',
                    obj = {
                        phone: this.user.phone,
                        api_token: this.user.api_token,
                        card: this.user.card.number,
                        exp: this.user.card.exp
                    };
                console.log(obj);
                if(/^8600/.test(this.user.card.number)) {
                    url = '/api/v1/buyer/send-sms-code-uz';
                    obj = {
                        phone: this.user.phone,
                        api_token: this.user.api_token,
                        card: this.user.card.number,
                        exp: this.user.card.exp
                    };
                    /*phone: this.user.phone,
                    api_token: this.user.api_token,
                    obj.card = this.user.card.number;
                    obj.exp = this.user.card.exp;*/
                    type = 1;
                }


                if (this.user.card.number && this.user.card.exp) {
                    axios.post(url, obj,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            err = false;
                            if (response.data.status === 'error_phone_not_equals') {
                                this.errors.phone.push('{{__('panel/buyer.error_phone_not_equals_')}}');
                                err=true;
                            }
                            if (response.data.status === 'error_card_equal') {
                                this.errors.phone.push('{{__('panel/buyer.error_card_equal')}}');
                                err=true;
                            }
                            if (response.data.status === 'error_card_exp') {
                                this.errors.phone.push('{{__('panel/buyer.error_card_exp')}}');
                                err=true;
                            }
                            if (response.data.status === 'error_card_scoring') {
                                this.errors.phone.push('{{__('panel/buyer.error_card_scoring')}}');
                                err=true;
                            }

                            if(!err) {
                                this.hashedSmsCode = response.data.hash;

                                /* if(type === 1) {
                                     this.hashedSmsCode = response.data.hash;
                                     this.user.phone = response.data.phone;
                                     this.user.name_humo = response.data.name
                                 }else if(type == 2){
                                     this.hashedSmsCode = response.data.hash;
                                     this.user.phone = response.data.phone;
                                     this.user.name_humo = response.data.name;
                                 }*/

                                this.showInputSMSCode = true;
                                this.timer = 120;
                                this.countDownTimer();
                            }

                            verify.$forceUpdate();
                        })
                        .catch(e => {
                            this.errors.smsCard = [];
                            this.errors.smsCard.push(e);
                        })

                }
                if (!this.user.card.number) {
                    this.errors.cardNumber = [];
                    this.errors.cardNumber.push('{{__('panel/buyer.error_card_number_empty')}}');
                }
                if (!this.user.card.exp) {
                    this.errors.exp = [];
                    this.errors.exp.push('{{__('panel/buyer.error_card_exp_empty')}}');
                }


            },
            checkSmsCode: function (e) {
                this.errors = {};
                this.messages = [];
                let url = '/api/v1/buyer/check-sms-code-humo';
                if(/^8600/.test(this.user.card.number))
                    url = '/api/v1/buyer/check-sms-code-uz';
                if (this.user.smsCode) {
                    axios.post(url, {
                        code: this.user.smsCode,
                        hashedCode: this.hashedSmsCode,
                        phone: this.user.phone,
                        name: this.user.name_humo,
                        card_number: this.user.card.number, // 11.05.2021 - добавил поля карты чтобы добавить карут в беке
                        card_valid_date: this.user.card.exp, //
                        api_format: this.api_format,
                        api_token: this.user.api_token,
                        buyer_id: this.user.buyer_id
                    },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            if (response.data.status === 'success') {
                                this.user.verify = response.data.data;
                                //this.user.verify = response.data.data;
                                this.cardAdd();
                                console.log('check-sms card add: '+this.step);
                                //this.step++ // убрать вызвать метод nextStep для увеличения step
                                this.waitValidate = true
                                this.verifyComplete = true; // для активации кнопки Продолжить
                                this.showFormAddCards = false;
                            } else {
                                this.errors.smsCode = [];
                                response.data.response.message.forEach(element => this.errors.smsCode.push(element.text));
                            }
                            verify.$forceUpdate();
                        })
                        .catch(e => {
                            this.errors.system = [];
                            this.errors.system.push(e);
                        })
                }

                if (!this.user.smsCode) {
                    this.errors.smsCode = [];
                    this.errors.smsCode.push('{{__('auth.error_code_empty')}}');
                }

            },
            updateList: function () {
                if (!this.loading)
                    this.loading = true;

                axios.post('/api/v1/buyer/card/list', {
                    api_token: this.user.api_token,
                    user_id: this.user.buyer_id
                },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                    if (response.data.status === 'success') {
                        this.user.cards = response.data.data;
                        console.log(this.user.cards)
                        if(Object.keys(this.user.cards).length > 0){
                            this.showFormAddCards = false;
                            // this.verifyComplete = true;
                        } else {
                            this.showFormAddCards = true;
                            // this.verifyComplete = false;
                        }
                        // this.verifyComplete = true;
                    }

                    if ( this.filevalidate.length >= 2 ){
                        this.waitValidate = false
                    }
                    else{
                        this.waitValidate = true
                    }

                    verify.$forceUpdate();
                    this.loading = false;
                })
            },
            resetStep: function (){
               // this.user.card.number = null;
                //this.user.card.exp = null;
                this.user.smsCode = null;
                this.showInputSMSCode = false;
            },
            verificationComplete: function () {
                this.errors = {};
                this.messages = [];

                console.log('verificationComplete');

                axios.post('/api/v1/buyer/verify/send', {
                        api_token: this.user.api_token,
                        buyer_id: this.user.buyer_id
                    },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(response => {
                    if (response.data.status === 'success') {
                        // this.step++;

                         /// автоперенаправление в кабинет.index
                         location.href = `{{localeRoute('cabinet.index')}}`;

                    }
                    verify.$forceUpdate();
                })
            },
            nextStep: function(){
                this.messages = [];
                this.step++;
                console.log('step: '+this.step)
            },
            getRegionList: function () {
                this.loading = true;
                axios.post('/api/v1/regions/list', {
                    api_token: this.user.api_token,
                    orderBy: 'name{{ucfirst(app()->getLocale())}}'
                    },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(response => {
                    if (response.data.status === 'success') {
                        this.region.list = response.data.data;

                        if(this.area.list.length > 0)
                            this.area.disabled = false;
                        else
                            this.area.disabled = true;

                        if(this.city.list.length > 0)
                            this.city.disabled = false;
                        else
                            this.city.disabled = true;
                    }
                    this.loading = false;
                })
            },
            getAreaList: function () {
                this.loading = true;
                axios.post('/api/v1/areas/list', {
                        api_token: this.user.api_token,
                        regionid: this.user.address_region,
                        orderBy: 'name{{ucfirst(app()->getLocale())}}'
                    },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(response => {
                    if (response.data.status === 'success') {
                        this.area.list = response.data.data;
                        if(response.data.data.length === 0)
                            $(this.$refs.selectArea).prop('disabled', true);
                        else
                            $(this.$refs.selectArea).prop('disabled', false);
                    }
                    this.loading = false;
                })
            },
            getCityList: function () {
                this.loading = true;
                axios.post('/api/v1/cities/list', {
                        api_token: this.user.api_token,
                        regionid: this.user.address_region,
                        areaid: this.user.address_area,
                        orderBy: 'name{{ucfirst(app()->getLocale())}}'
                    },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(response => {
                    if (response.data.status === 'success') {
                        this.city.list = response.data.data;

                        if(response.data.data.length === 0)
                            $(this.$refs.selectCity).prop('disabled', true);
                        else
                            $(this.$refs.selectCity).prop('disabled', false);

                    }
                    this.loading = false;
                })
            },
            changeRegion: function () {

                this.errors.address_region = null;

                this.area.list = null;
                this.user.address_area = '';

                this.user.address_city = '';
                this.city.list = null;
                this.city.disabled = true;

                if(this.user.address_region !== ''){
                    this.getAreaList();
                    this.area.disabled = false;
                } else {
                    this.area.disabled = true;
                }

            },
            changeArea: function () {

                this.errors.address_area = null;

                this.user.address_city = '';
                this.city.list = null;

                if(this.user.address_area !== ''){
                    this.getCityList();
                    this.city.disabled = false;
                } else {
                    this.city.disabled = true;
                }
            },

            changeCity: function () {

                this.errors.address_city = null;
            },

            countDownTimer: function(){
                if(this.timer > 0) {
                    setTimeout(() => {
                        this.timer -= 1
                        this.countDownTimer()
                    }, 1000)
                }else if(this.timer == 0){
                    this.showInputSMSCode = false;
                }
            }

        },
        mounted () {
            console.log(this.step);
        },
        created: function () {
            this.getRegionList();
        },
    })
</script>
