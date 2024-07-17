@extends('templates.cabinet.app')

@section('title', __('cabinet/zpay.header_zpay'))
@section('class', 'zpay')

@section('center-header-custom')
    <div class="zcoin-info">
        {!! __('cabinet/zpay.zcoin_balance')!!}
        <div class="amount">{{$balance}}</div>
    </div>
@endsection

@section('content')

    @if(count($services) > 0)
        @foreach($services as $type => $list)
            <div class="lead">{{__('cabinet/zpay.type_'.$type)}}</div>
            <div class="list">
                @foreach($list as $item)
                    <div data-img="{{$item['img']}}" data-name="{{$item['name']}}" data-type="{{$type}}" data-service_id="{{$item['service_id']}}" data-id="{{$item['id']}}" class="item">
                        <img src="{{$item['img']}}">
                        <div class="name">{{$item['name']}}</div>
                    </div><!-- /.item -->
                @endforeach
            </div><!-- /.list -->
        @endforeach
    @endif

    <!-- Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h5>{{__('cabinet/zpay.service_payment')}}</h5>
                    <img class="logo" src="">
                    <div class="name"></div>

                    <div class="messages"></div>

                    <form action="">
                        <input type="hidden" name="id">
                        <input type="hidden" name="service_id">


                        <div class="input-group type mobile">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{__('cabinet/zpay.phone')}}</span>
                            </div>
                            <input type="text" class="form-control" name="account">
                        </div>

                        <div class="input-group type internet">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{__('cabinet/zpay.login')}}</span>
                            </div>
                            <input type="text" class="form-control" name="account">
                        </div>

                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{__('cabinet/zpay.payment_amount')}}</span>
                            </div>
                            <input type="text" required class="form-control"  name="amount">
                        </div>

                        <div class="controls">
                            <button type="submit" class="btn btn-lg btn-success">{{__('app.btn_pay')}}</button>
                            <br>
                            <button type="button" class="btn btn-link" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                        </div>

                    </form>
                    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>
                </div><!-- /.modal-body -->
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->


    <script>

        $(document).ready(function(){
            $('.list .item').on('click', function(){

                let id = $(this).data('id'),
                    service_id = $(this).data('service_id'),
                    type = $(this).data('type'),
                    name = $(this).data('name'),
                    img = $(this).data('img');


                $('#serviceModal .input-group.type').hide();
                $('#serviceModal .input-group.type input').attr('required', false);
                $('#serviceModal .messages').html('');

                $('#serviceModal .input-group.type.' + type).show();
                $('#serviceModal .input-group.type.' + type + ' input').attr('required', true);

                $('#serviceModal input[name=amount]').val('0');
                $('#serviceModal input[name=id]').val(id);
                $('#serviceModal input[name=phone]').val('');
                $('#serviceModal input[name=login]').val('');
                $('#serviceModal input[name=service_id]').val(service_id);

                $('#serviceModal img.logo').attr('src', img);
                $('#serviceModal .name').html(name);



                $('#serviceModal').modal();
            })

            $('.modal form').on('submit', function(){
                loading(true);
                let account = '';
                $('#serviceModal input[name=account]').each(function(){
                    if($(this).val() != '')
                        account = $(this).val();
                });
                axios.post('/api/v1/pay/payment',
                    {
                        api_token: Cookies.get('api_token'),
                        id: $('#serviceModal input[name=id]').val(),
                        service_id: $('#serviceModal input[name=service_id]').val(),
                        amount: $('#serviceModal input[name=amount]').val(),
                        account: account,
                        //login: $('#serviceModal input[name=login]').val(),
                        user_id: {{Auth::user()->id}}
                    },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(response => {
                    loading(false);

                    let messages = response.data.response.message;
                    $('#serviceModal .messages').html('');

                    for(let i = 0; i < messages.length; i++){
                        let message = '<div class="alert alert-' + messages[i].type + '">' + messages[i].text +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n' +
                            '    <span aria-hidden="true">&times;</span>\n' +
                            '  </button></div>';
                        $('#serviceModal .messages').append(message);
                    }

                })

                return false;
            });
        })

        function loading(show = false){
            if(show)
                $('.loading').addClass('active');
            else
                $('.loading').removeClass('active');
        }

    </script>

@endsection
