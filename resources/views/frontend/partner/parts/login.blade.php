
<script>
    function initPartnerData(){
        return {
            errors: [],
            messages: [],
            //showInputSMSCode: false,
            //showInputPassword: true,
            showNextBtn: false,
            hashedSmsCode: null,
            api_format: 'object',
            user: {
                partnerId: '',
                password: '',
                smsCode: '',
                token: '{{ csrf_token() }}',
                passwordResend: false,
            },
            loading: false
        }
    }

    new Vue({
        el: '#partner-auth',
        data: initPartnerData,
        methods: {
            checkPartnerId: function () {
                this.errors = [];
                this.messages = [];


                if (this.user.partnerId) {
                    this.loading = true;
                    axios.post('/api/v1/login/validate-form', {
                        partner_id: this.user.partnerId,
                        role: 'partner'
                    },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                    .then(response => {
                        if(response.data.status === 'success') {
                            //console.dir(response.data.response);
                            //if(response.data.response.auth_type == 'sms'){
                                //this.showInputSMSCode = true;
                                //this.showInputPassword = false;
                                //this.sendSmsCode();
                            //}else{
                                //this.showInputSMSCode = false;
                                //this.showInputPassword = true;
                                this.checkPassword();
                            //}
                            this.showNextBtn = false;
                        } else {
                            this.errors = parseErrors(response);
                        }
                        this.loading = false;
                    })
                    .catch(e => {
                        this.errors.push(e);
                    })
                }

                if (!this.user.partnerId) {
                    this.errors.push('{{__('auth.error_id_empty')}}');
                }

            },

            checkPassword: function(){
                this.errors = [];
                this.messages = [];

                if (this.user.password){
                    this.loading = true;
                    axios.post('/api/v1/login/check-password',{
                        partner_id: this.user.partnerId,
                        role: 'partner',
                        password: this.user.password,
                        api_format: this.api_format
                    },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        if(response.data.status === 'success') {
                            this.login();
                        } else {
                            response.data.response.message.forEach(element => this.errors.push(element.text));
                        }
                        this.loading = false;
                    }).catch(e => {
                        if(e.response.status === 302) {
                            window.location.href = e.response.data
                        }
                        this.errors.push(e);
                    })
                }
                if (!this.user.password){
                    this.errors.push('{{__('auth.error_password_is_empty')}}');
                }
            },

            checkForm: function(e){
                e.preventDefault();
                if(this.user.partnerId !== '' && this.user.password !== ''){
                    return true;
                }
                if(this.user.partnerId !== '' && this.user.code !== ''){
                    return true;
                }
                e.preventDefault();
            },

            resend: function () {
                this.errors = [];
                this.messages = [];

                if(this.user.partnerId > 0){
                    axios.post('/api/v1/login/partners/resend-password', {
                        partner_id: this.user.partnerId
                    },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        if (response.data.status === 'success') {
                            response.data.response.message.forEach(element => this.messages.push(element.text));
                            this.user.passwordResend = true;
                        } else {
                            response.data.response.message.forEach(element => this.errors.push(element.text));
                        }
                    })
                } else {
                    this.errors.push('{{__('auth.error_company_is_empty')}}');
                }


            },

            login: function () {
                this.errors = [];
                this.messages = [];
                let data = {};
                data.partner_id = this.user.partnerId;
                data.api_format = this.api_format;
                data.role = 'partner';
                data._token = this.user.token;
                /*if(this.showInputSMSCode){
                    data.hashedCode = this.hashedSmsCode;
                    data.code = this.user.smsCode;
                }*/
                //if(this.showInputPassword)
                    data.password = this.user.password;
                this.loading = true;

                axios.post('{{localeRoute('auth')}}', data,
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                    .then(response => {
                        if(response.data.status === 'success') {
                            location.href = `{{localeRoute('billing.orders.index')}}`;
                        }else {
                            response.data.response.message.forEach(element => this.errors.push(element.text));
                        }
                        this.loading = false;
                    }).catch(e => {
                    this.errors.push(e);
                });
            }
        }
    })
</script>
