@extends('templates.panel.app')

@section('title', __('panel/payment.header_payments'))
@section('class', 'payment list')

@section('content')
<style>
    .nav-link:not(.active) a {
        color: #787878;
    }
    a {
        color: var(--orange);
        outline: none;
    }
    a:hover, a:focus, a:visited {
        color: #4807b0;
        outline: none;
    }
    .first.paginate_button, .last.paginate_button {
        display: none !important;
    }
    .previous.paginate_button, .next.paginate_button {
        height: 40px;
        background: #F6F6F6;
        border-radius: 8px;
        border: 1px solid transparent;
        transition: 0.4s;
        font-size: 16px;
        display: inline-flex !important;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        border-radius: 8px !important;
    }
    .previous.paginate_button{
        background-position: left 4px center !important;
        padding: 0.15rem 1rem 0.15rem 2rem !important;
        margin-left: 0 !important;
    }
    .next.paginate_button {
        background-position: right 4px center !important;
        padding: 0.15rem 2rem 0.15rem 1rem !important;
    }
    .previous.paginate_button:hover, .next.paginate_button:hover {
        border-color: transparent !important;
        background-color: var(--peach) !important;
    }
    .previous.paginate_button:active, .next.paginate_button:active {
        border-color: transparent !important;
        background-color: #6610f530 !important;
        box-shadow: none !important;
    }

    .paginate_button.disabled{
        filter: grayscale(1);
        opacity: .5;
        cursor: not-allowed !important;
    }
    input.paginate_input {
        max-width: 100px;
        padding: 8px 12px;
        margin: 0 8px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        font-size: 16px;
        line-height: 24px;
        letter-spacing: 0.01em;
        color: #1e1e1e;
        box-sizing: border-box;
        background: #F6F6F6;
        border-radius: 8px;
        border: 1px solid transparent;
        transition: 0.4s;
    }
    input.paginate_input:hover {
        border: 1px solid #d1d1d1;
    }
    input.paginate_input:focus {
        border: 1px solid var(--orange);
        outline: none;
        color: #1e1e1e;
        box-shadow: none;
    }
</style>
    <div>
        <div >
            <div class="caption">{{__('panel/finance.upay_balance')}} {{$upay_balance}}</div>
        </div>
    </div>
    <div class="dataTablesSearch" id="dataTablesSearch">
       <div class="row">
           {{--<div class="col">
               <input name="transaction" placeholder="Номер транзакции" type="text" class="form-control" >
           </div>--}}
           <div class="col">
               <input name="contract" placeholder="Номер договора" type="text" class="form-control" >
           </div>
           <div class="col">
               <select id="type" name="type" class="form-control" >
                   <option value="">Все </option>
                   <option value="user">Пополнение</option>
                   <option value="auto">Списание</option>
                   <option value="refund">Возврат</option>
                   <option value="user_auto">Досрочное погашение</option>
                   <option value="reimbursable">Возмещение расходов</option>
                   <option value="upay">Оплаты Upay сервисов</option>
               </select>
           </div>
           <div class="col">
               <select id="payment_system" name="payment_system" class="form-control" >
                   <option value="">Все </option>
                   <option value="DEPOSIT">DEPOSIT</option>
                   <option value="BANK">BANK</option>
                   <option value="MIB">MIB</option>
                   <option value="Autopay">Autopay</option>
                   <option value="UZCARD">UZCARD</option>
                   <option value="HUMO">HUMO</option>
                   <option value="PNFL">PNFL</option>
                   <option value="OCLICK">CLICK</option>
                   <option value="PAYME">PAYME</option>
                   <option value="APELSIN">APELSIN</option>
                   <option value="UPAY">UPAY</option>
                   <option value="PAYNET">PAYNET</option>
                   <option value="ACCOUNT">Лицевой счет</option>
                   <option value="BONUS_ACCOUNT">Бонусный счет</option>
                   <option value="Paycoin">Оплаты Upay сервисов</option>
               </select>
           </div>
           <div class="col">
               <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
           </div>
       </div>


    </div>

    <table class="table payment-list">
        <thead>
        <tr>

            <th>{{__('panel/payment.contract')}}</th>
            <th>{{__('panel/payment.month')}}</th>
            <th>{{__('panel/payment.user')}}</th>
            <th>{{__('panel/payment.amount')}}</th>
            <th>{{__('panel/payment.type')}}</th>
            <th>{{__('panel/payment.payment_system')}}</th>
            <th>{{__('panel/payment.status')}}</th>
            <th>{{__('panel/payment.created_at')}}</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table><!-- /.news-list -->



    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>



    @include('panel.payment.parts.list')
@endsection
