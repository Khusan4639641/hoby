<div id="send_message">
    <div class="lead">{{__('panel/buyer.send_message')}}</div>
    <div v-if="messages.length">
        <div :class="'alert alert-' + message.type" v-for="message in messages">@{{ message.text }}</div>
    </div>
    <div v-if="errors.length">
        <div class="small alert alert-danger" v-for="error in errors">@{{ error }}</div>
    </div>
    <div class="form-group">
        <textarea v-model="text" required class="form-control" rows="3"></textarea>
    </div>
    <button v-on:click="send" class="btn btn-primary">{{__('app.btn_send')}}</button>
</div>


<script>
    var send_message = new Vue({
        el: '#send_message',
        data: {
            errors: [],
            messages: [],
            showResults: false,
            api_token: '{{Auth::user()->api_token}}',
            buyer_id: '{{$buyer->id}}',
            text: ''
        },
        methods: {
            send: function () {
                this.messages = [];
                this.errors = [];

                let post = {
                    api_token: this.api_token,
                    buyer_id: this.buyer_id,
                    text: this.text
                };

                axios.post('/api/v1/employee/buyers/send-sms', post, { headers: { 'Content-Language': '{{app()->getLocale()}}' }}).then(response =>{

                    //this.scorings = response.data.data;
                    this.errors = response.data.response.errors;
                    this.messages = response.data.response.message;

                }).catch(e => {
                    //response.data.response.message.forEach(element => this.messages.push(element.text));
                });
            }
        }

    })
</script>
