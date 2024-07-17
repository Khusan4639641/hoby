
@if($type == 'direct')

    <section class="payment-method">
        <div class="lead">{{__('frontend/order.header_payment')}}</div>

        <div class="select-payment">
            <div class="form-check form-check-inline">
                <input class="form-check-input" id="paymentMethodCard" type="radio" v-model="payment.typePayment" name="type_payment" @click="setPaymentMethod('card')" value="card" checked/>
                <label class="form-check-label" for="paymentMethodCard">{{__('frontend/order.payment_card')}}</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" id="paymentMethodAccount" type="radio" v-model="payment.typePayment" name="type_payment" @click="setPaymentMethod('account')" value="account"/>
                <label class="form-check-label" for="paymentMethodAccount">
                    {{__('frontend/order.payment_account')}} ({{$personal_account}} {{__('app.currency')}})
                </label>
            </div>
        </div>

        <div class="row" v-if="payment.isPaymentCard">
            @if(count($cards) > 0)
                <div class="col-12 col-md-6">
                    <div class="title">{{__('cabinet/index.my_cards')}}</div>
                    <table class="table cards-list">
                        @foreach($cards as $index => $item)
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input v-model="payment.card_id" name="refill_card" @if($index == 0) checked @endif value="{{$item->id}}" class="form-check-input refill-card" type="radio" id="radioCard{{$index}}">
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
            @else
                <div class="col-12">
                    <a class="btn btn-sm btn-outline-primary" href="{{localeRoute('cabinet.cards.index')}}">{{__('frontend/order.btn_add_cart')}}</a>
                </div>
            @endif
        </div><!-- /.row -->
    </section><!-- /.payment-method -->

@elseif($type == 'credit')

    <section class="contract">
        <div class="lead">{{__('frontend/order.header_contract')}}</div>
        <div class="form-row order-total">
            <div class="form-group col-12 col-md">
                <label>{{__('billing/order.lbl_period')}}</label>
                <div class="">
                    <select v-model="payment.period" ref="selectPeriod" name="period" :class="'form-control' + (errors.period?' is-invalid':'')" @change="saveSettings()">
                        <option v-for="(item, index) in payment.config_plans" :value="index">@{{index}} {{__('app.months')}}</option>
                    </select>
                </div>
                <div class="error" v-if="'period' in errors">
                    @{{ errors.period }}
                </div>
            </div>
            <div class="form-group col-6 offset-md-1 col-md">
                <label>{{__('billing/order.lbl_payment_monthly')}}</label>
                <div class="value monthly">@{{payment.paymentMonthly}} {{__('app.currency')}}</div>
            </div>

            <div v-if="payment.calculating" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>
        </div>
    </section><!-- /.contract -->
@endif
