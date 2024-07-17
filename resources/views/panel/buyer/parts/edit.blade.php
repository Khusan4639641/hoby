<script>
    var buyer = new Vue({
        el: '#buyer',
        data: {
            errors: {},
            messages: [],
            locale: '{{ucfirst(app()->getLocale())}}',

            inputs: {
                address_residential: {
                    region: {
                        list: [],
                        disabled: false
                    },
                    area: {
                        list: JSON.parse('{!! $addressResidential->areaList !!}'),
                        disabled: true
                    },
                    city: {
                        list: JSON.parse('{!! $addressResidential->cityList !!}'),
                        disabled: true
                    },
                },
                address_registration: {
                    region: {
                        list: [],
                        disabled: false
                    },
                    area: {
                        list: JSON.parse('{!! $addressRegistration->areaList !!}'),
                        disabled: true
                    },
                    city: {
                        list: JSON.parse('{!! $addressRegistration->cityList !!}'),
                        disabled: true
                    },
                },
            },

            statuses: [
                @foreach($statuses as $status)
                {{$status}},
                @endforeach
            ],

            user: {
                api_token: '{{Auth::user()->api_token}}',

                status: {{$buyer->status}},
                verify_message: '{{$buyer->verify_message??null}}',


                buyer_id: '{{$buyer->id}}',
                name: '{{$buyer->name}}',
                surname: '{{$buyer->surname}}',
                patronymic: '{{$buyer->patronymic}}',

                birthday: '{{ \App\Helpers\EncryptHelper::decryptData($buyer->personals['birthday'])}}',
                home_phone: '{{$buyer->personals['home_phone']}}',

                pinfl: '{{$buyer->personals['pinfl']}}',
                inn: '{{$buyer->personals['inn']}}',
                passport_number: '{{$buyer->personals['passport_number']}}',
                passport_issued_by: '{{$buyer->personals['passport_issued_by']}}',
                passport_date_issue: '{{$buyer->personals['passport_date_issue']}}',
                city_birth: '{{$buyer->personals['city_birth']}}',

                limit: '{{@$buyer->settings->limit}}',

                work_company: '{{$buyer->personals['work_company']}}',
                work_phone: '{{$buyer->personals['work_phone']}}',


                address_residential_region: '{{$addressResidential->region}}',
                address_residential_area: '{{$addressResidential->area}}',
                address_residential_city: '{{$addressResidential->city}}',
                address_residential_address: '{{$addressResidential->address}}',


                address_registration_region: '{{$addressRegistration->region}}',
                address_registration_area: '{{$addressRegistration->area}}',
                address_registration_city: '{{$addressRegistration->city}}',
                address_registration_address: '{{$addressRegistration->address}}',

                files: {
                    passport_selfie: {
                        old: '{{$personals['passport_selfie']['id']}}',
                        new: null,
                        preview: '{{$personals['passport_selfie']['preview']}}',
                        path: '{{$personals['passport_selfie']['path']}}',
                        delete: false
                    },
                    passport_first_page: {
                        old: '{{$personals['passport_first_page']['id']}}',
                        new: null,
                        preview: '{{$personals['passport_first_page']['preview']}}',
                        path: '{{$personals['passport_first_page']['path']}}',
                        delete: false
                    }
                },
                files_to_delete: null
            }
        },
        methods: {
            validatePersonals: function (e) {
                this.errors = [];
                this.messages = [];

                axios.post('/api/v1/employee/buyers/validate-form', this.user,
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                    .then(response => {
                        if (response.data.status === 'success') {
                            this.modifyPersonals()
                        } else {
                            this.errors = response.data.response.errors;
                            this.messages = response.data.response.message;
                            window.scrollTo(0, 0);
                        }

                    })
                    .catch(e => {
                        this.errors.push(e);
                    })

            },

            checkPinfl: function (e) {
                this.errors = [];
                this.messages = [];

                if(this.user.pinfl != ''){
                    axios.post('/api/v1/employee/buyers/check-pinfl',
                        {
                            api_token: this.user.api_token,
                            buyer_id: this.user.buyer_id,
                            pinfl: this.user.pinfl
                        }, {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            this.messages = response.data.response.message;
                            window.scrollTo(0, 0);

                        })
                        .catch(e => {
                            this.errors.push(e);
                        })
                }
            },

            modifyPersonals: function (e) {
                this.errors = [];
                this.messages = [];

                let formData = new FormData();

                formData.append('api_token', this.user.api_token);
                formData.append('buyer_id', this.user.buyer_id);

                formData.append('name', this.user.name);
                formData.append('surname', this.user.surname);
                formData.append('patronymic', this.user.patronymic);
                formData.append('birthday', (this.user.birthday==="" ? document.querySelector("input[name='birthday']").value : this.user.birthday));
                formData.append('home_phone', this.user.home_phone);
                formData.append('pinfl', this.user.pinfl);
                formData.append('inn', this.user.inn);
                formData.append('limit', this.user.limit);

                formData.append('verify_message', this.user.verify_message);
                formData.append('status', this.user.status);

                //formData.append('address_registration_postcode', this.user.address_registration_postcode);
                //formData.append('address_registration_country', this.user.address_registration_country);
                formData.append('address_registration_region', this.user.address_registration_region);
                formData.append('address_registration_area', this.user.address_registration_area);
                formData.append('address_registration_city', this.user.address_registration_city);
                formData.append('address_registration_address', this.user.address_registration_address);

                //formData.append('address_residential_postcode', this.user.address_residential_postcode);
                //formData.append('address_residential_country', this.user.address_residential_country);
                formData.append('address_residential_region', this.user.address_residential_region);
                formData.append('address_residential_area', this.user.address_residential_area);
                formData.append('address_residential_city', this.user.address_residential_city);
                formData.append('address_residential_address', this.user.address_residential_address);

                formData.append('passport_number', this.user.passport_number);
                formData.append('passport_issued_by', this.user.passport_issued_by);
                formData.append('passport_date_issue', this.user.passport_date_issue);
                formData.append('city_birth', this.user.city_birth);

                formData.append('work_company', this.user.work_company);
                formData.append('work_phone', this.user.work_phone);

                formData.append('passport_selfie', this.user.files.passport_selfie.new);
                formData.append('passport_first_page', this.user.files.passport_first_page.new);
                formData.append('files_to_delete', this.user.files_to_delete);

                axios.post('/api/v1/employee/buyers/modify', formData, {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                    .then(response => {
                        const urlParams = new URLSearchParams(window.location.search);
                        const pretensionPage = urlParams.get('from');
                        const contractId = urlParams.get('contract_id');

                        this.messages = response.data.response.message;
                        this.errors = response.data.response.errors;
                        window.scrollTo(0, 0);

                        // if (pretensionPage && pretensionPage === 'pretension') {
                        //     window.location.href = makeRoute('panel.pretension', contractId)
                        // }
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

                    if (this.user.files[input].old.length > 0){
                        this.user.files[input].delete = true;
                    }
                }
                this.setFilesToDelete();
            },

            setFilesToDelete: function () {
                let _arr = [],
                    files = this.user.files;
                Object.keys(files).map(function(objectKey, index) {
                    let element = files[objectKey];

                    if(element.delete) {
                        _arr.push(element.old);
                    }
                });
                this.user.files_to_delete = _arr.join(',');
            },

            getRegionList: function (type) {
                axios.post('/api/v1/regions/list', {
                        api_token: this.user.api_token,
                        orderBy: 'name{{ucfirst(app()->getLocale())}}'
                    },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(response => {
                    if (response.data.status === 'success') {
                        this.inputs[type].region.list = response.data.data;

                        if(this.inputs[type].area.list.length > 0)
                            this.inputs[type].area.disabled = false;
                        else
                            this.inputs[type].area.disabled = true;

                        if(this.inputs[type].city.list.length > 0)
                            this.inputs[type].city.disabled = false;
                        else
                            this.inputs[type].city.disabled = true;
                    }
                    this.loading = false;
                })
            },

            getAreaList: function (type) {
                axios.post('/api/v1/areas/list', {
                        api_token: this.user.api_token,
                        regionid: this.user[type + '_region'],
                        orderBy: 'name{{ucfirst(app()->getLocale())}}'
                    },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(response => {
                    if (response.data.status === 'success') {
                        this.inputs[type].area.list = response.data.data;

                        if(response.data.data.length === 0)
                            $(this.$refs[type + '_selectArea']).prop('disabled', true);
                        else
                            $(this.$refs[type + '_selectArea']).prop('disabled', false);
                    }
                    this.loading = false;
                })
            },

            getCityList: function (type) {
                axios.post('/api/v1/cities/list', {
                        api_token: this.user.api_token,
                        regionid: this.user[type + '_region'],
                        areaid: this.user[type + '_area'],
                        orderBy: 'name{{ucfirst(app()->getLocale())}}'
                    },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(response => {
                    if (response.data.status === 'success') {
                        this.inputs[type].city.list = response.data.data;
                        if(response.data.data.length === 0)
                            $(this.$refs[type + '_selectCity']).prop('disabled', true);
                        else
                            $(this.$refs[type + '_selectCity']).prop('disabled', false);

                    }
                    this.loading = false;
                })
            },

            changeRegion: function (type) {

                this.errors[type + '_region'] = null;

                this.inputs[type].area.list = null;
                this.user[type + '_area'] = '';

                this.inputs[type].city.list = null;
                this.inputs[type].city.disabled = true;
                this.user[type + '_city'] = '';

                if(this.user[type + '_region'] !== ''){
                    this.getAreaList(type);
                    this.inputs[type].area.disabled = false;
                } else {
                    this.inputs[type].area.disabled = true;
                    console.log(this.inputs[type]);
                }
            },

            changeArea: function (type) {

                this.errors[type + '_area'] = null;

                this.user[type + '_city'] = '';
                this.inputs[type].city.list = null;

                if(this.user[type + '_area'] !== ''){
                    this.getCityList(type);
                    this.inputs[type].city.disabled = false;
                } else {
                    this.inputs[type].city.disabled = true;
                }
            },

            changeCity: function (type) {

                this.errors[type + '_city'] = null;
            },

            setVerified: function () {
                this.errors = [];
                this.messages = [];

                axios.post('/api/v1/employee/buyers/action/verify', {
                    api_token: this.user.api_token,
                    buyer_id: this.user.buyer_id
                },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                    if (response.data.status === 'success') {
                        response.data.response.message.forEach(element => this.messages.push(element.text));
                    }
                })
            }
        },
        mounted: function() {
        },
        created: function () {
            this.getRegionList('address_residential');
            this.getRegionList('address_registration');
           // console.log('birthday: ' + this.user.birthday);
        },
    });
</script>
