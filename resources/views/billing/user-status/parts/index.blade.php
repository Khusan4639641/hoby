<script>

const app = new Vue({
    el: '#app',
    data: {
        status: null,

        loading: false,
        processing_user: false,
        calculating: false,

        errors: {},
        phone_empty: null,
        userExist: false,
        message: [],
        deposit_message: [],
        buyers: [],
        lastBuyers: [],

        strSearchPhone: '+998',

        buyer: null,
        products: [],
        seller_phone: null,
    },

    methods: {
        checkUser() {
            if (this.strSearchPhone != null && this.strSearchPhone.length >= 10) {
                this.processing_user = true;
                axios.post('/api/v1/buyers/list', {
                    api_token: '{{$user->api_token}}',
                    phone__like: this.strSearchPhone,
                    // status: 4,
                }).then(response => {
                    if (response.data.status === 'success') {
                        this.buyers = response.data.data;

                        if (response.data.data.length === 0) {
                            this.userExist = true;
                        }
                    }
                    this.processing_user = false;
                });
            } else {
                this.phone_empty = '{{ __('auth.error_phone_is_empty') }}';
            }
        },

        setBuyer(index) {
            this.buyer = this.buyers[index];
            this.strSearchPhone = null;
            this.buyers = [];
        },

        unsetBuyer() {
            this.buyer = null;
        },

        async getBuyers() {
            try {

                const { data: response } = await axios.post('/api/v1/buyers/list', {
                    api_token: '{{ $user->api_token }}',
                    orderByDesc: 'id',
                    limit: 5,
                    // status: 4,
                    created_by: '{{$user->company_id}}',
                });

                if (response.status === 'success') {
                    this.lastBuyers = response.data;
                } else {
                    console.log(response.error);
                }


            } catch (e) {
                console.log(e);
            }
        },
    },

    watch: {
        strSearchPhone: function () {
            this.phone_empty = null;

            if (this.strSearchPhone != null && this.strSearchPhone.length >= 10) {
                this.processing_user = true;
                axios.post('/api/v1/buyers/list', {
                    api_token: '{{$user->api_token}}',
                    phone__like: this.strSearchPhone,
                    // status: 4,
                }).then(response => {
                    if (response.data.status === 'success') {
                        this.buyers = response.data.data;
                    }
                    this.processing_user = false;
                });
            }
        },

    },

    created() {
        this.getBuyers();
    },
});
</script>


