@extends('templates.cabinet.app')


@section('class', 'index mobile')
@section('title', __('cabinet/index.header_index'))
@section('h1', __('cabinet/index.personal_account'))
@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('cabinet.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('content')

       @include('cabinet.parts.payments')

       @include('cabinet.parts.refill')

       <section class="zcoin-promo">
           <div class="row">
               <div class="col-12 col-md-4">
                   <div class="item">
                       <div class="digit">01</div>
                       <div class="name">{{__('cabinet/index.index_zcoin_1_name')}}</div>
                       <div class="text">{{__('cabinet/index.index_zcoin_1_text')}}</div>
                   </div><!-- /.item -->
               </div>

               <div class="col-12 col-md-4">
                   <div class="item">
                       <div class="digit">02</div>
                       <div class="name">{{__('cabinet/index.index_zcoin_2_name')}}</div>
                       <div class="text">{{__('cabinet/index.index_zcoin_2_text')}}</div>
                   </div><!-- /.item -->
               </div>

               <div class="col-12 col-md-4">
                   <div class="item">
                       <div class="digit">03</div>
                       <div class="name">{{__('cabinet/index.index_zcoin_3_name')}}</div>
                       <div class="text">{{__('cabinet/index.index_zcoin_3_text')}}</div>
                   </div><!-- /.item -->
               </div>
           </div><!-- /.row -->

           <div class="text-center mt-5">
               <a href="{{localeRoute('cabinet.pay.index')}}" class="btn btn-outline-light btn-arrow">{{__('cabinet/index.zcoin_bonus')}}</a>
           </div>
       </section>

@endsection
