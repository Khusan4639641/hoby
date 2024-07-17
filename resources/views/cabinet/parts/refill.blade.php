<section class="personal-account">
    <div class="lead">{{__('cabinet/index.personal_account')}}</div>
    @if(count($cards) > 0)
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="title">{{__('cabinet/index.my_cards')}}</div>
                <table class="table cards-list">
                    @foreach($cards as $index => $item)
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input name="refill_card" @if($index == 0) checked @endif value="{{$item->id}}" class="form-check-input refill-card" type="radio" id="radioCard{{$index}}">
                                    <label class="form-check-label" for="radioCard{{$index}}">
                                        {{$item->public_number}}
                                    </label>
                                </div>
                            </td>
                            <td>
                                <img src="{{asset('images/icons/icon_'.strtolower($item->type).'_grey.svg')}}" alt="">
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div><!-- /.col-12 col-md-6 -->
            <div class="col-12 col-md-6">
                <div class="title">{{__('cabinet/index.payment_sum')}}</div>
                <div class="row">
                    <div class="col-6 col-sm-7">
                        <input value="0" type="text" class="form-control refill-sum">
                    </div>
                    <div class="col-6 col-sm-5">
                        <div id="refillByCardBtn" class="btn btn-primary">{{__('app.btn_refill')}}</div>
                    </div>
                </div>
            </div><!-- /.col-12 col-md-6 -->
        </div><!-- /.row -->

        <div class="refill-result"></div>
        <hr>
    @else
        <p>{{__('cabinet/index.cards_not_found')}}</p>
        <a class="btn btn-sm btn-outline-primary" href="{{localeRoute('cabinet.cards.index')}}">{{__('cabinet/index.btn_add_card')}}</a>
        <hr>
    @endif

    <div class="title">{{__('cabinet/index.other_payment_methods')}}</div>

    <div class="other-payments">
        <a target="_blank" href="https://my.click.uz/auth" class="item">
            <img src="{{asset('images/payments/click_logo.png')}}" alt="">
        </a>
        <a target="_blank" href="https://payme.uz" class="item">
            <img src="{{asset('images/payments/payme_logo.png')}}" alt="">
        </a>
        <a target="_blank" href="https://myuzcard.uz/" class="item">
            <img src="{{asset('images/payments/myuzcard_logo.svg')}}" alt="">
        </a>
        <a target="_blank" href="https://play.google.com/store/apps/details?id=uz.kapitalbank.android&hl=ru" class="item">
            <img src="{{asset('images/payments/apelsin.png')}}" alt="">
            <span>APELSIN</span>
        </a>
        <?/*<input name="summ" value="">
        <a href="javascript:void(0)" class="btn btn-primary">Пополнить</a>*/?>
    </div>

    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>
</section>

<template id="ps_pay-me">
    <form>
        <input type="hidden" name="merchant" value=""/>
        <input type="hidden" name="account[order_id]" value="0"/>
        <input type="hidden" name="lang" value="{{app()->getLocale()}}"/>
        <input type="hidden" name="currency" value="860"/>
        <input type="hidden" name="callback" value=""/>
        <input type="hidden" name="amount" value=""/> <?php // {сумма чека в ТИИНАХ * 100}  ?>
    </form>
</template>

<template id="ps_click">
    <form>
        <input id="click_amount_field" type="hidden" name="MERCHANT_TRANS_AMOUNT" value="" class="click_input"/>
        <input type="hidden" name="MERCHANT_ID" value=""/>
        <input type="hidden" name="MERCHANT_USER_ID" value=""/>
        <input type="hidden" name="MERCHANT_SERVICE_ID" value=""/>
        <input type="hidden" name="MERCHANT_TRANS_ID" value=""/>
        <input type="hidden" name="MERCHANT_TRANS_NOTE" value="Оплата"/>
        <input type="hidden" name="SIGN_TIME" value=""/>
        <input type="hidden" name="SIGN_STRING" value=""/>
        <input type="hidden" name="RETURN_URL" value=""/>
    </form>
</template>

<template id="ps_uzcard">
    <form>
        <input id="click_amount_field" type="hidden" name="MERCHANT_TRANS_AMOUNT" value="" class="click_input"/>
        <input type="hidden" name="MERCHANT_ID" value=""/>
        <input type="hidden" name="MERCHANT_TERMINAL_ID" value=""/>
        <input type="hidden" name="MERCHANT_TRANS_ID" value=""/>
        <input type="hidden" name="SIGN_TIME" value=""/>
        <input type="hidden" name="SIGN_STRING" value=""/>
        <input type="hidden" name="RETURN_URL" value=""/>
    </form>
</template>

<!-- Click.UZ -->
<div class="modal fade" id="clickModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="payment-method">
            <div class="modal-header">
                <h5 class="modal-title">{{__('cabinet/index.personal_account')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{__('cabinet/index.payment_sum')}}</label>
                    <input type="number" name="amount" value="" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                <button type="button" class="btn btn-success" id="click-pay-button">{{__('app.btn_refill')}}</button>
            </div>
        </div><!-- /.modal-content -->
    </div>
</div>
<!-- End click.uz -->

<script src="//my.click.uz/pay/checkout.js"></script>
<script>
    window.onload = function () {
        var linkEl = document.querySelector("#click-pay-button");
        linkEl.addEventListener("click", function () {
            let amount = $('input[name="amount"]','#clickModal').val();
            axios.post('/api/v1/oclick/create', {
                "amount": amount
            }).then(response => {
                $('#clickModal').modal('hide');
                if(response.data.status == 'success') {
                    let trans_id = response.data.data.transaction_id;
                    createPaymentRequest({
                        merchant_id: <?=config('test.click_merchant_id_test')?>,
                        merchant_user_id: <?=config('test.click_merchant_id_test')?>,
                        service_id: <?=config('test.click_service_id_test')?>,
                        transaction_param: trans_id,
                        amount: amount
                    }, function (data) {
                        if (data && (data.status === 2 || data.status === 0)) {
                            axios.post('/api/v1/oclick/accept', {
                                'transaction_id': trans_id
                            });
                            //window.location.href = '<?=app()->getLocale()?>/cabinet?payment=success';
                        }else if(data.status == null) {
                            axios.post('/api/v1/oclick/delete', {
                                'transaction_id': trans_id
                            });
                        }
                    });
                }
                debugger;
            }).catch(e => {

            });
        });
    };
</script>
<script>
    let refill = {
        card_id: null,
        sum: null,
    };

    $(document).ready(function(){

        $('.item', '.order-payments').click(function(){
            $(this).addClass('active');

        });

        $('#refillByCardBtn').on('click', function () {

            refill.card_id = $('.refill-card:checked').val();
            refill.sum = $('.refill-sum').val();
            $('.refill-result').html('');

            if(refill.card_id != '' && refill.sum != '' && $.isNumeric(refill.card_id) && $.isNumeric(refill.sum)){
                loading(true);

                axios.post('/api/v1/buyer/refill-by-card', {
                        api_token: Cookies.get('api_token'),
                        card_id: refill.card_id,
                        sum: refill.sum,
                    },
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(response => {
                    $(response.data.response.message).each(function(index, item){
                        $('.refill-result').append('<div class="alert alert-' + item.type + '">' + item.text + '</div>');
                    });
                    if(response.data.status == 'success')
                        $("#buyerPersonalAccount").text(response.data.data.balance);
                    loading(false);
                })
            }else{
                $('.refill-result').append('<div class="alert alert-danger">{{__('cabinet/cabinet.error_refill_fields')}}</div>');
            }
        })
    })

    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }
</script>
