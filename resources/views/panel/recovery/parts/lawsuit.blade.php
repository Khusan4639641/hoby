<div class="lawsuit mt-3" id="lawsuit">

    <div v-if="message.length > 0" v-for="item in message" :class="'alert alert-' + item.type">@{{ item.text }}</div>


    <div v-if="lawsuit.id != ''">
        <div class="lead">{{__('panel/lawsuit.header_lawsuit')}}</div>
        <table>
            <tr>
                <td>{{__('panel/lawsuit.status')}}</td>
                <td>@{{ lawsuit.status?lawsuit.status_caption:'&mdash;' }}</td>
            </tr>
            <tr>
                <td>{{__('panel/lawsuit.number')}}</td>
                <td>@{{ lawsuit.number?lawsuit.number:'&mdash;' }}</td>
            </tr>
            <tr>
                <td>{{__('panel/lawsuit.date_filling')}}</td>
                <td>@{{ lawsuit.date_filling?lawsuit.date_filling:'&mdash;' }}</td>
            </tr>
            <tr>
                <td>{{__('panel/lawsuit.date_review')}}</td>
                <td>@{{ lawsuit.date_review?lawsuit.date_review:'&mdash;' }}</td>
            </tr>
            <tr>
                <td>{{__('panel/lawsuit.judical_authority')}}</td>
                <td>@{{ lawsuit.judical_authority?lawsuit.judical_authority:'&mdash;' }}</td>
            </tr>
            <tr>
                <td>{{__('panel/lawsuit.date_decision')}}</td>
                <td>@{{ lawsuit.date_decision?lawsuit.date_decision:'&mdash;' }}</td>
            </tr>
            <tr>
                <td>{{__('panel/lawsuit.date_effective')}}</td>
                <td>@{{ lawsuit.date_effective?lawsuit.date_effective:'&mdash;' }}</td>
            </tr>
        </table>
    </div>

    <div class="modal fade" id="modalLawsuit" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{__('panel/lawsuit.btn_create')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{__('panel/lawsuit.status')}}</label>
                        <select v-model="lawsuit.status" class="form-control">
                            @for($i = 0; $i <=9; $i ++)
                                <option value="{{$i}}">{{__('lawsuit.status_'.$i)}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{__('panel/lawsuit.date_filling')}}</label>
                        <date-picker v-model="lawsuit.date_filling" value-type="format" type="date"
                                     format="DD.MM.YYYY"></date-picker>
                    </div>
                    <div class="form-group">
                        <label>{{__('panel/lawsuit.date_review')}}</label>
                        <date-picker v-model="lawsuit.date_review" value-type="format" type="date"
                                     format="DD.MM.YYYY"></date-picker>
                    </div>
                    <div class="form-group">
                        <label>{{__('panel/lawsuit.number')}}</label>
                        <input type="text" v-model="lawsuit.number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{__('panel/lawsuit.judical_authority')}}</label>
                        <input type="text" v-model="lawsuit.judical_authority" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{__('panel/lawsuit.date_decision')}}</label>
                        <date-picker v-model="lawsuit.date_decision" value-type="format" type="date"
                                     format="DD.MM.YYYY"></date-picker>
                    </div>
                    <div class="form-group">
                        <label>{{__('panel/lawsuit.date_effective')}}</label>
                        <date-picker v-model="lawsuit.date_effective" value-type="format" type="date"
                                     format="DD.MM.YYYY"></date-picker>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                    <button @click="save" type="button" data-dismiss="modal" class="btn btn-primary">{{__('app.btn_save')}}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="form-controls">
        <button data-toggle="modal" data-target="#modalLawsuit" v-if="lawsuit.id == ''" class="btn btn-success">{{__('panel/lawsuit.btn_create')}}</button>
        <button data-toggle="modal" data-target="#modalLawsuit" v-else class="btn btn-outline-primary">{{__('panel/lawsuit.btn_edit')}}</button>
    </div>
    <!-- /.form-controls -->

    <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>

</div><!-- /#lawsuit -->

<script>
    var lawsuitVue = new Vue({
        el: '#lawsuit',
        data: {
            lawsuit: {
                contract_id: {{$contract->id}},
                user_id: {{$contract->buyer->id}},
                insurance_id: '{{$contract->insurance->id??null}}',
                id: '{{$contract->lawsuit->id??null}}',
                date_filling: '{{$contract->lawsuit->date_filling??null}}',
                date_review: '{{$contract->lawsuit->date_review??null}}',
                number: '{{$contract->lawsuit->number??null}}',
                judical_authority: '{{$contract->lawsuit->judical_authority??null}}',
                date_decision: '{{$contract->lawsuit->date_decision??null}}',
                date_effective : '{{$contract->lawsuit->date_effective??null}}',
                status: {{$contract->lawsuit->status??0}},
                status_caption: '{{isset($contract->lawsuit->status)?__('lawsuit.status_'.$contract->lawsuit->status):null}}',
            },
            message: [],
            loading: false,
        },
        methods: {
            save(){
                if(this.lawsuit.id == '')
                    this.create();
                else
                    this.update();

            },

            create(){
                if (!this.loading) {
                    this.message = [];
                    this.loading = true;

                    axios.post('/api/v1/lawsuit/add',
                        {
                            contract_id:        {{$contract->id}},
                            user_id:            {{$contract->buyer->id}},
                            insurance_id:       '{{$contract->insurance->id??null}}',
                            date_filling:       this.lawsuit.date_filling,
                            date_review:        this.lawsuit.date_review,
                            number:             this.lawsuit.number,
                            judical_authority:  this.lawsuit.judical_authority,
                            date_decision:      this.lawsuit.date_decision,
                            date_effective :    this.lawsuit.date_effective,
                            status:             this.lawsuit.status,
                            api_token:          Cookies.get('api_token')
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.lawsuit.id = response.data.data.id;
                            this.lawsuit.status_caption = response.data.data.status_caption;
                            lawsuitVue.$forceUpdate()
                        }

                        this.message = response.data.response.message;

                        this.loading = false;
                    });
                }
            },

            update(){
                if (!this.loading) {
                    this.message = [];
                    this.loading = true;

                    axios.post('/api/v1/lawsuit/modify',
                        {
                            id:                 this.lawsuit.id,
                            date_filling:       this.lawsuit.date_filling,
                            date_review:        this.lawsuit.date_review,
                            number:             this.lawsuit.number,
                            judical_authority:  this.lawsuit.judical_authority,
                            date_decision:      this.lawsuit.date_decision,
                            date_effective :    this.lawsuit.date_effective,
                            status:             this.lawsuit.status,
                            api_token:          Cookies.get('api_token')
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.lawsuit.status_caption = response.data.data.status_caption;
                            lawsuitVue.$forceUpdate()
                        }


                        this.message = response.data.response.message;

                        this.loading = false;
                    });
                }
            }
        }
    });
</script>
