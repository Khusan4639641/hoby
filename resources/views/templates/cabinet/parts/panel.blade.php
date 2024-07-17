
<div class="user-panel">
    <div class="row">
        <div class="col-12 col-md-9 pr-md-0">
            <div class="row mobile-panel">

                <div class="col-12 col-md-4 item-wrapper pr-sm-1">
                    <div class="rating">
                        <div class="caption">{{__('cabinet/profile.personal_account')}}</div>
                        <div class="value" id="buyerPersonalAccount">{{$info['personal_account']}}</div>
                        <div class="d-lg-none caption mt-1"><a href="{{localeRoute('cabinet.account.refill')}}">{{__('app.btn_refill')}}</a></div>
                    </div>
                </div>
                <div class="col-12 col-md-8 pl-md-0 d-flex align-items-stretch">
                    <div class="row stats">
                        {{--<div class="d-none d-md-block col item-wrapper">
                            <div class="limit">
                                <div class="caption">{{__('cabinet/profile.period')}}</div>
                                <div class="value">{{$info['period']}}</div>
                            </div>
                        </div>--}}
                        <div class="col-12 col-md item-wrapper">
                            <div class="period">
                                <div class="caption">{{__('cabinet/profile.balance')}}</div>
                                <div class="value">{{$info['balance']}} {{__('app.currency')}}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md item-wrapper">
                            <div class="debt">
                                <div class="caption">{{__('cabinet/profile.debt')}}</div>
                                <div class="value {{$info['debt'] > 0?'text-danger':''}}">{{$info['debt']}} {{__('app.currency')}}</div>
                                @if($info['debt'] > 0)
                                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#delayModal">{{__('cabinet/order.btn_delay')}}</button>
                                    @include('cabinet.order.parts.delay')
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.stats -->
        </div>
        <div class="col-12 col-md-3 pl-md-1 item-wrapper">
            <a href="{{localeRoute('cabinet.pay.index')}}" class="zcoin">
                <div class="caption">zCoin</div>
                <div class="value">{{$info['zcoin']}}</div>
                <div class="link d-lg-none">{{__('cabinet/index.txt_spend')}}</div>
            </a>
        </div>
    </div><!-- /.row -->
</div><!-- /.user-panel -->


